<?php

class Account
{
    public function get()
    {
        $uid = (int)($_SESSION['lobby_uid'] ?? $_SESSION['mellon_uid'] ?? 0);
        $username = (string)($_SESSION['lobby_username'] ?? $_SESSION['mellon_username'] ?? '');

        return array(
            'name' => 'Player:' . $uid,
            'data' => array(
                'playerId' => $uid,
                'avatarName' => $username,
                'userAccountIdentifier' => $uid,
                'isInstantAccount' => 0,
                'isActivated' => $uid > 0
            )
        );
    }

    public function Signup($post)
    {
        global $engine;

        $name = trim((string)($post['name'] ?? ''));
        $pw = (string)($post['pw'] ?? '');
        $email = trim((string)($post['email'] ?? ''));

        if ($name === '' || $pw === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $q = $engine->sql->prepare(
            'INSERT INTO global_user (username,password,email,timed,prestige,level) VALUES (?,?,?,?,?,?)'
        );

        return $q->execute(array(
            $name,
            base64_encode($pw),
            $email,
            time(),
            0,
            0
        ));
    }

    public function Login($token)
    {
        global $index_url, $lobby_url, $engine;

        $token = (string)$token;
        if ($token === '') {
            header('Location: ' . $index_url);
            exit;
        }

        $t = query(
            'SELECT * FROM global_msid WHERE token=? LIMIT 1',
            array($token)
        )->fetch(PDO::FETCH_ASSOC);

        if (!$t) {
            header('Location: ' . $index_url);
            exit;
        }

        $u = query(
            'SELECT * FROM global_user WHERE email=? LIMIT 1',
            array($t['email'])
        )->fetch(PDO::FETCH_ASSOC);

        if (!$u) {
            header('Location: ' . $index_url);
            exit;
        }

        $u['islogin'] = true;
        $engine->session->data = (object)$u;

        $_SESSION['lobby_uid'] = (int)$u['uid'];
        $_SESSION['lobby_username'] = (string)$u['username'];
        $_SESSION['lobby_email'] = (string)$u['email'];
        $_SESSION['mellon_uid'] = (int)$u['uid'];
        $_SESSION['mellon_username'] = (string)$u['username'];
        $_SESSION['mellon_email'] = (string)$u['email'];
        $_SESSION['mellon_msid'] = $token;

        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        setcookie(
            'gl5SessionKey',
            json_encode(array('key' => $token, 'id' => (int)$u['uid'])),
            time() + 1209600,
            '/',
            '',
            $secure,
            true
        );

        setcookie(
            'gl5PlayerId',
            (string)$u['uid'],
            time() + 1209600,
            '/',
            '',
            $secure,
            false
        );

        header(
            'Location: ' . $lobby_url .
            '#msid=' . rawurlencode($token)
        );
        exit;
    }

    public function Logout()
    {
        unset(
            $_SESSION['lobby_uid'],
            $_SESSION['lobby_username'],
            $_SESSION['lobby_email'],
            $_SESSION['mellon_uid'],
            $_SESSION['mellon_username'],
            $_SESSION['mellon_email'],
            $_SESSION['mellon_msid']
        );
    }
}
