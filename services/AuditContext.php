<?php
declare(strict_types=1);

function pureViaAuditBrowser(?string $agent=null): string
{
    $agent??=(string)($_SERVER['HTTP_USER_AGENT']??'');
    $browser='Unknown';
    foreach(['Edge'=>'~(?:Edg|EdgiOS|EdgA)/([0-9]+)~','Opera'=>'~(?:OPR|Opera)/([0-9]+)~',
        'Firefox'=>'~(?:Firefox|FxiOS)/([0-9]+)~','Chrome'=>'~(?:Chrome|CriOS)/([0-9]+)~',
        'Safari'=>'~Version/([0-9]+).*Safari/~'] as $name=>$pattern) {
        if(preg_match($pattern,$agent,$matches)){$browser=$name.' '.substr($matches[1],0,4);break;}
    }
    $os=match(true){
        (bool)preg_match('/iPhone|iPad|iPod/i',$agent)=>'iOS',
        str_contains($agent,'Android')=>'Android',
        str_contains($agent,'Windows NT')=>'Windows',
        str_contains($agent,'Mac OS X')=>'macOS',
        str_contains($agent,'Linux')=>'Linux',
        default=>null
    };
    return $browser.($os?' · '.$os:'');
}

function recordPureViaAuthAudit(mysqli $conn,int $userId,string $action): void
{
    if(!in_array($action,['LOGIN','LOGOUT'],true))throw new InvalidArgumentException('Invalid authentication audit action.');
    $s=$conn->prepare('SELECT role_id FROM users WHERE id=?');$s->bind_param('i',$userId);$s->execute();$user=$s->get_result()->fetch_assoc();$s->close();
    if(!$user)return;
    $role=match((int)$user['role_id']){1=>'admin',2=>'staff',default=>'customer'};
    $module='Auth';$record=(string)$userId;
    $description=$action==='LOGIN'?'Successful login':'Signed out from the '.$role.' session';
    $ip=filter_var($_SERVER['REMOTE_ADDR']??'',FILTER_VALIDATE_IP)?:null;
    $browser=pureViaAuditBrowser();
    $s=$conn->prepare('INSERT INTO audit_logs(user_id,action,module,record_id,description,ip_address,browser) VALUES(?,?,?,?,?,?,?)');
    $s->bind_param('issssss',$userId,$action,$module,$record,$description,$ip,$browser);$s->execute();$s->close();
}
