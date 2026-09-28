<?php

/*
 * Develop by Phumin Chanthalert from Thailand
 * Facebook : http://fb.com/phoomin2012
 * Tel. : 091-8585234 (Thai mobile)
 * Copy Rigth © Phumin Chanthalert.
 */

class Database {

    private function normalizeServer($s) {
        if (!$s) return false;
        if (!isset($s['ww_position']) || !$s['ww_position']) {
            $s['ww_position'] = array(array(0, 0));
        } elseif (is_string($s['ww_position'])) {
            $decoded = json_decode($s['ww_position'], true);
            $s['ww_position'] = is_array($decoded) ? $decoded : array(array(0, 0));
        }
        return $s;
    }

    public function listServer() {
        $r = array();
        $q = query("SELECT * FROM `global_server_data` ORDER BY `sid` ASC");
        while ($s = $q->fetch(PDO::FETCH_ASSOC)) {
            $r[] = $this->normalizeServer($s);
        }
        return $r;
    }

    public function getServer() {
        $s = query("SELECT * FROM `global_server_data` WHERE `tag`=? LIMIT 1", array(SERVER_TAG))->fetch(PDO::FETCH_ASSOC);
        return $this->normalizeServer($s);
    }

    public function msid($email = "abcdefghijklmnopqrstuvwxyz") {
        $token = rand(0, 1000);
        $token = $token . $email . rand(1000, 10000);
        $token = hash('adler32', $token);
        $token = time() . $token;
        $token = hash('crc32b', $token);
        $token = $token . hash('crc32', $token);

        $q = query("SELECT * FROM `global_msid` WHERE `email`=? AND `ip`=?", array($email, $_SERVER['REMOTE_ADDR']));
        if ($q->rowCount() == 1) {
            $t = $q->fetch();
            $token = $t['token'];
        } else {
            query("DELETE FROM `global_msid` WHERE `email`=? AND `ip`=?", array($email, $_SERVER['REMOTE_ADDR']));
            query("INSERT INTO `global_msid` (`token`,`email`,`ip`) VALUES (?,?,?);", array($token, $email, $_SERVER['REMOTE_ADDR']));
        }
        return $token;
    }

    public function getmsid($token) {
        $q = query("SELECT * FROM `global_msid` WHERE `token`=?", array($token));
        if ($q->rowCount() == 1) {
            $t = $q->fetch();
            $u = query("SELECT * FROM `global_user` WHERE `email`=?;", array($t['email']))->fetch();
            return $u;
        } else {
            return false;
        }
    }

}

?>
