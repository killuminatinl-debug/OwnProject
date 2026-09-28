<?php

class Account {

    public function check() {
        global $engine;

        if (isset($_SESSION['mellon_msid'])) {
            $msid = query("SELECT * FROM `global_msid` WHERE `token`=?;", [$_SESSION['mellon_msid']])->fetch(PDO::FETCH_ASSOC);
            $q = query("SELECT * FROM `global_user` WHERE `email`=?;", [$msid['email']]);
            $n = $q->rowCount();
            if ($n == 1) {
                $u = $q->fetch(PDO::FETCH_ASSOC);
                $engine->session->data = (object) $u;
                $_SESSION['mellon_uid'] = $u['uid'];
                $_SESSION['mellon_username'] = $u['username'];
                $_SESSION['mellon_email'] = $u['email'];
                return true;
            } else {
                return false;
            }
        } elseif (isset($_SESSION['mellon_email'])) {
            $q = query("SELECT * FROM `global_user` WHERE `email`=?;", [$_SESSION['mellon_email']]);
            $n = $q->rowCount();
            if ($n == 1) {
                $u = $q->fetch(PDO::FETCH_ASSOC);
                $engine->session->data = (object) $u;
                $_SESSION['mellon_uid'] = $u['uid'];
                $_SESSION['mellon_username'] = $u['username'];
                $_SESSION['mellon_email'] = $u['email'];
                return true;
            } else {
                return false;
            }
        }
    }

    public function Signup($email, $password, $newsletter, $term) {
        global $engine;
        if (!$term || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') return false;
        if ($this->EmailValid($email)) return false;
        $username = substr(preg_replace('/[^A-Za-z0-9_-]/', '', explode("@", $email)[0]), 0, 20);
        if ($username === '') $username = 'Player' . random_int(1000, 9999);
        $password = base64_encode($password);
        $ok = query("INSERT INTO `global_user` (`username`,`email`,`password`,`timed`) VALUES (?,?,?,?);", [$username, $email, $password, time()]);
        if (!$ok) return false;
        return $this->Login($email, $password === '' ? '' : base64_decode($password));
    }

    public function EmailValid($email) {
        global $engine;
        $q = query("SELECT * FROM `global_user` WHERE `email`=?;", array($email))->rowCount(PDO::FETCH_ASSOC);
        if ($q == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function Login($email, $pass) {
        global $engine;
        //$post['name'] = mysqli_real_escape_string($post['name']);
        //$pass = base64_encode($pass);
        $q = query("SELECT * FROM `global_user` WHERE `email`=?;", array($email));
        $n = $q->rowCount();
        if ($n == 1) {
            $u = $q->fetch(PDO::FETCH_ASSOC);
            if (base64_decode($u['password']) == $pass) {
                $engine->session->data = (object) $u;
                $_SESSION['mellon_uid'] = $u['uid'];
                $_SESSION['mellon_username'] = $u['username'];
                $_SESSION['mellon_email'] = $u['email'];
                $token = $engine->database->msid($u['email']);
                $_SESSION['mellon_msid'] = $token;
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function Logout() {
        global $engine;
        unset($_SESSION['mellon_uid']);
        unset($_SESSION['mellon_username']);
        unset($_SESSION['mellon_email']);
        unset($_SESSION['mellon_msid']);
    }

    public function WSignup($world) {
        global $engine;
        if (query("SELECT * FROM `" . $engine->server->prefix . "first_play` WHERE `email`=?", array($_SESSION['mellon_email']))->rowCount() == 0) {
            $sql = "INSERT INTO `" . $engine->server->prefix . "first_play` (`email`,`step`) VALUE (?,?);";
            $array = array($_SESSION['mellon_email'], 0);
            if (query($sql, $array)) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

}

?>
