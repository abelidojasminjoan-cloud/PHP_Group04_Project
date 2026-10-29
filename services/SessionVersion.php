<?php
declare(strict_types=1);

function enforcePureViaSessionVersion(mysqli $conn): void
{
    $userId=(int)($_SESSION['user_id']??0);
    if($userId<=0||PHP_SAPI==='cli')return;
    $s=$conn->prepare('SELECT session_version FROM users WHERE id=?');$s->bind_param('i',$userId);$s->execute();$user=$s->get_result()->fetch_assoc();$s->close();
    if(!$user||(int)$user['session_version']!==(int)($_SESSION['session_version']??0)){
        $_SESSION=[];
        if(session_status()===PHP_SESSION_ACTIVE)session_regenerate_id(true);
    }
}
