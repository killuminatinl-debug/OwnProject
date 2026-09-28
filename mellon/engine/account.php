<?php

class Account
{
    public function check()
    {
        global $engine;

        if (!empty($_SESSION['mellon_msid'])) {
            $m = query(
                "SELECT * FROM global_msid WHERE token=? LIMIT 1",
                array((string)$_SESSION['mellon_msid'])
            )->fetch(PDO::FETCH_ASSOC);

            if ($m) {
                $u = query(
                    "SELECT * FROM global_user WHERE email=? LIMIT 1",
                    array($m['email'])
                )->fetch(PDO::FETCH_ASSOC);

                if ($u) {
                    $engine->session->data = (object)$u;
                    $_SESSION['mellon_uid'] = (int)$u['uid'];
                    $_SESSION['mellon_username'] = (string)$u['username'];
                    $_SESSION['mellon_email'] = (string)$u['email'];
                    return true;
                }
            }
        }

        if (!empty($_SESSION['mellon_email'])) {
            $u = query(
                "SELECT * FROM global_user WHERE email=? LIMIT 1",
                array((string)$_SESSION['mellon_email'])
            )->fetch(PDO::FETCH_ASSOC);

            if ($u) {
                $engine->session->data = (object)$u;
                $_SESSION['mellon_uid'] = (int)$u['uid'];
                $_SESSION['mellon_username'] = (string)$u['username'];
                return true;
            }
        }

        return false;
    }

    public function Signup($email, $password, $newsletter, $term)
    {
        if (!$term || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            return false;
        }

        if ($this->EmailValid($email)) {
            return false;
        }

        $username = substr(
            preg_replace('/[^A-Za-z0-9_-]/', '', explode('@', $email)[0]),
            0,
            20
        );

        if ($username === '') {
            $username = 'Player' . random_int(1000, 9999);
        }

        query(
            "INSERT INTO global_user (username,email,password,timed,prestige,level) VALUES (?,?,?,?,?,?)",
            array($username, $email, base64_encode($password), time(), 0, 0)
        );

        return $this->Login($email, $password);
    }

    public function EmailValid($email)
    {
        return (bool)query(
            "SELECT 1 FROM global_user WHERE email=? LIMIT 1",
            array($email)
        )->fetchColumn();
    }

    public function Login($login, $pass)
    {
        global $engine;

        $login = trim((string)$login);
        $pass = (string)$pass;

        if ($login === '' || $pass === '') {
            return false;
        }

        $u = query(
            "SELECT * FROM global_user WHERE email=? OR username=? LIMIT 1",
            array($login, $login)
        )->fetch(PDO::FETCH_ASSOC);

        if (!$u) {
            return false;
        }

        $stored = (string)$u['password'];
        $valid = hash_equals($stored, base64_encode($pass));

        if (!$valid && password_verify($pass, $stored)) {
            $valid = true;
        }

        if (!$valid) {
            return false;
        }

        $engine->session->data = (object)$u;

        session_regenerate_id(true);

        $_SESSION['mellon_uid'] = (int)$u['uid'];
        $_SESSION['mellon_username'] = (string)$u['username'];
        $_SESSION['mellon_email'] = (string)$u['email'];

        $msid = $engine->database->msid($u['email']);
        if (!$msid) {
            return false;
        }

        $_SESSION['mellon_msid'] = $msid;

        return true;
    }

    public function Logout()
    {
        unset(
            $_SESSION['mellon_uid'],
            $_SESSION['mellon_username'],
            $_SESSION['mellon_email'],
            $_SESSION['mellon_msid']
        );
    }
}
