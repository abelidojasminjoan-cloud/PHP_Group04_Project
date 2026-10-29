<?php
declare(strict_types=1);
require_once __DIR__.'/AuditContext.php';

final class PasswordReset
{
    private static function limitRequests(mysqli $conn,string $email,string $ip): void
    {
        $hour=(string)intdiv(time(),3600);
        $buckets=[hash('sha256','email:'.$email.':'.$hour)=>5,hash('sha256','ip:'.$ip.':'.$hour)=>15];
        ksort($buckets);
        $conn->query('DELETE FROM password_reset_limits WHERE expires_at<NOW() LIMIT 100');
        $conn->begin_transaction();
        try{
            foreach($buckets as $key=>$max){
                $s=$conn->prepare('INSERT INTO password_reset_limits(key_hash,requests,expires_at) VALUES(?,1,DATE_ADD(NOW(),INTERVAL 2 HOUR)) ON DUPLICATE KEY UPDATE requests=LEAST(requests+1,65535)');$s->bind_param('s',$key);$s->execute();
                $s=$conn->prepare('SELECT requests FROM password_reset_limits WHERE key_hash=?');$s->bind_param('s',$key);$s->execute();
                if((int)$s->get_result()->fetch_assoc()['requests']>$max)throw new DomainException('Too many reset requests. Please try again later.');
            }
            $conn->commit();
        }catch(Throwable $e){$conn->rollback();throw $e;}
    }

    /** The mail callback is injectable for checks that must never send actual email. */
    public static function request(mysqli $conn,string $email,string $ip,?callable $mailer=null): array
    {
        $email=strtolower(trim($email));
        if(strlen($email)>255||!filter_var($email,FILTER_VALIDATE_EMAIL))throw new DomainException('Enter a valid email address.');
        self::limitRequests($conn,$email,$ip);
        $conn->begin_transaction();
        try{
            $s=$conn->prepare('SELECT id,first_name,email,status FROM users WHERE email=? FOR UPDATE');$s->bind_param('s',$email);$s->execute();$user=$s->get_result()->fetch_assoc();
            if(!$user)throw new DomainException('No account was found with that email address.');
            if(!in_array($user['status'],['active','locked'],true))throw new DomainException('This account cannot reset its password yet. Complete activation or contact support.');
            $userId=(int)$user['id'];
            $s=$conn->prepare("SELECT id FROM otp_codes WHERE user_id=? AND purpose='password_reset' AND created_at>DATE_SUB(NOW(),INTERVAL 60 SECOND) LIMIT 1");$s->bind_param('i',$userId);$s->execute();
            if($s->get_result()->fetch_assoc())throw new DomainException('Wait 60 seconds before requesting another code.');
            $code=(string)random_int(100000,999999);$hash=password_hash($code,PASSWORD_DEFAULT);
            $s=$conn->prepare("UPDATE otp_codes SET used_at=NOW() WHERE user_id=? AND purpose='password_reset' AND used_at IS NULL");$s->bind_param('i',$userId);$s->execute();
            $s=$conn->prepare("INSERT INTO otp_codes(user_id,otp_hash,purpose,expires_at) VALUES(?,?,'password_reset',DATE_ADD(NOW(),INTERVAL 10 MINUTE))");$s->bind_param('is',$userId,$hash);$s->execute();$otpId=(int)$conn->insert_id;
            $conn->commit();
        }catch(Throwable $e){$conn->rollback();throw $e;}
        $sent=false;
        try{
            if($mailer===null){require_once __DIR__.'/PasswordResetMail.php';$mailer='sendPureViaPasswordResetCode';}
            $sent=(bool)$mailer($user['email'],$user['first_name'],$code);
        }catch(Throwable $e){error_log('PureVia reset code email failed.');}
        if(!$sent){
            $s=$conn->prepare("UPDATE otp_codes SET used_at=NOW() WHERE id=? AND used_at IS NULL AND purpose='password_reset'");$s->bind_param('i',$otpId);$s->execute();
            throw new RuntimeException('We could not send the reset code. Please try again in a minute.');
        }
        return ['user_id'=>$userId,'otp_id'=>$otpId,'email'=>$user['email'],'requested_at'=>time()];
    }

    public static function verify(mysqli $conn,array $challenge,string $code): array
    {
        if(!preg_match('/^[0-9]{6}$/D',$code))throw new DomainException('Enter the six-digit code from your email.');
        $userId=(int)($challenge['user_id']??0);$otpId=(int)($challenge['otp_id']??0);
        if(!$userId||!$otpId)throw new DomainException('Request a password reset code first.');
        $conn->begin_transaction();
        try{
            $s=$conn->prepare('SELECT password,status FROM users WHERE id=? FOR UPDATE');$s->bind_param('i',$userId);$s->execute();$user=$s->get_result()->fetch_assoc();
            $s=$conn->prepare("SELECT *,expires_at>NOW() AS unexpired FROM otp_codes WHERE id=? AND user_id=? AND purpose='password_reset' FOR UPDATE");$s->bind_param('ii',$otpId,$userId);$s->execute();$otp=$s->get_result()->fetch_assoc();
            if(!$user||!in_array($user['status'],['active','locked'],true)||!$otp||$otp['used_at']!==null||!(int)$otp['unexpired']||(int)$otp['attempt_count']>=5)throw new DomainException('This code has expired or is no longer valid. Request a new code.');
            if(!password_verify($code,$otp['otp_hash'])){
                $s=$conn->prepare('UPDATE otp_codes SET attempt_count=attempt_count+1,used_at=IF(attempt_count>=5,NOW(),NULL) WHERE id=?');$s->bind_param('i',$otpId);$s->execute();
                $conn->commit();
                throw new DomainException('Incorrect code. Please check your email and try again.');
            }
            $s=$conn->prepare('UPDATE otp_codes SET used_at=NOW() WHERE id=?');$s->bind_param('i',$otpId);$s->execute();
            $s=$conn->prepare('SELECT used_at FROM otp_codes WHERE id=?');$s->bind_param('i',$otpId);$s->execute();$usedAt=$s->get_result()->fetch_assoc()['used_at'];
            $conn->commit();
            return ['user_id'=>$userId,'otp_id'=>$otpId,'verified_at'=>time(),'used_at'=>$usedAt,'password_fingerprint'=>hash('sha256',$user['password'])];
        }catch(Throwable $e){$conn->rollback();throw $e;}
    }

    public static function complete(mysqli $conn,array $grant,string $password,string $confirmation,array $policy): array
    {
        $userId=(int)($grant['user_id']??0);$otpId=(int)($grant['otp_id']??0);$verified=(int)($grant['verified_at']??0);
        if(!$userId||!$otpId||$verified>time()||$verified<time()-600)throw new DomainException('Password reset verification expired. Request a new code.');
        if($password!==$confirmation)throw new DomainException('The passwords do not match.');
        if(!mb_check_encoding($password,'UTF-8'))throw new DomainException('Enter a valid password.');
        if($errors=validatePureViaPassword($password,$policy))throw new DomainException(implode(' ',$errors));
        $conn->begin_transaction();
        try{
            $s=$conn->prepare('SELECT id,first_name,email,password,status FROM users WHERE id=? FOR UPDATE');$s->bind_param('i',$userId);$s->execute();$user=$s->get_result()->fetch_assoc();
            $s=$conn->prepare("SELECT used_at FROM otp_codes WHERE id=? AND user_id=? AND purpose='password_reset' FOR UPDATE");$s->bind_param('ii',$otpId,$userId);$s->execute();$otp=$s->get_result()->fetch_assoc();
            if(!$user||!in_array($user['status'],['active','locked'],true)||!$otp||!$otp['used_at']||$otp['used_at']!==($grant['used_at']??'')||!hash_equals(hash('sha256',$user['password']),(string)($grant['password_fingerprint']??'')))throw new DomainException('This password reset is no longer valid. Request a new code.');
            if(password_verify($password,$user['password']))throw new DomainException('Choose a different password from your current one.');
            $hash=password_hash($password,PASSWORD_DEFAULT);
            $s=$conn->prepare('UPDATE users SET password=?,password_changed_at=NOW(),session_version=session_version+1 WHERE id=?');$s->bind_param('si',$hash,$userId);$s->execute();
            $s=$conn->prepare("UPDATE otp_codes SET used_at=COALESCE(used_at,NOW()) WHERE user_id=? AND purpose='password_reset'");$s->bind_param('i',$userId);$s->execute();
            $action='PASSWORD_RESET';$module='Auth';$record=(string)$userId;$description='Password reset after email OTP verification; existing sessions revoked.';
            $ip=filter_var($_SERVER['REMOTE_ADDR']??'',FILTER_VALIDATE_IP)?:null;$browser=pureViaAuditBrowser();
            $s=$conn->prepare('INSERT INTO audit_logs(user_id,action,module,record_id,description,ip_address,browser) VALUES(?,?,?,?,?,?,?)');$s->bind_param('issssss',$userId,$action,$module,$record,$description,$ip,$browser);$s->execute();
            $conn->commit();
            return ['email'=>$user['email'],'name'=>$user['first_name'],'locked'=>$user['status']==='locked'];
        }catch(Throwable $e){$conn->rollback();throw $e;}
    }
}
