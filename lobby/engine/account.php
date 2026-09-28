<?php

class Account {

    public function get() {
        $uid = (int)($_SESSION['lobby_uid'] ?? 0);
        $username = (string)($_SESSION['lobby_username'] ?? '');
        return ["name"=>"Player:".$uid,"data"=>[
            "playerId"=>$uid,"avatarName"=>$username,"userAccountIdentifier"=>$uid,
            "isInstantAccount"=>0,"isActivated"=>$uid>0
        ]];
    }

    public function Signup($post) {
        global $engine;
        $q=$engine->sql->prepare("INSERT INTO global_user (username,password,email,timed,prestige,level) VALUES (?,?,?,?,?,?)");
        return $q->execute([$post['name'],base64_encode($post['pw']),$post['email'],time(),0,0]);
    }

    public function Login($token) {
        global $index_url,$lobby_url;
        $t=query("SELECT * FROM global_msid WHERE token=? LIMIT 1",[$token])->fetch(PDO::FETCH_ASSOC);
        if(!$t){header("Location: ".$index_url);exit;}
        $u=query("SELECT * FROM global_user WHERE email=? LIMIT 1",[$t['email']])->fetch(PDO::FETCH_ASSOC);
        if(!$u){header("Location: ".$index_url);exit;}
        $GLOBALS['engine']->session->data=(object)$u;
        $_SESSION['lobby_uid']=(int)$u['uid'];
        $_SESSION['lobby_username']=$u['username'];
        $_SESSION['lobby_email']=$u['email'];
        $_SESSION['mellon_uid']=(int)$u['uid'];
        $_SESSION['mellon_username']=$u['username'];
        $_SESSION['mellon_email']=$u['email'];
        $_SESSION['mellon_msid']=$t['token'];
        $secure=!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off';
        $cookie=json_encode(["key"=>$t['token'],"id"=>(int)$u['uid']);
        setcookie("gl5SessionKey",$cookie,time()+1209600,"/","",$secure,true);
        setcookie("gl5PlayerId",(string)$u['uid'],time()+1209600,"/","",$secure,true);
        header("Location: ".$lobby_url."?g_msid=".rawurlencode(md5($t['token']))."&gl5SessionKey=".rawurlencode($t['token']));
        exit;
    }

    public function Logout() {
        unset($_SESSION['lobby_uid'],$_SESSION['lobby_username'],$_SESSION['lobby_email']);
        unset($_SESSION['mellon_uid'],$_SESSION['mellon_username'],$_SESSION['mellon_email'],$_SESSION['mellon_msid']);
    }
}
