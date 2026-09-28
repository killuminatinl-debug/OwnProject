<?php

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
        $rows = array();
        $q = query("SELECT * FROM `global_server_data` ORDER BY `sid` ASC");
        while ($s = $q->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $this->normalizeServer($s);
        }
        return $rows;
    }

    public function getServer($id = null) {
        if ($id === null) {
            $s = query("SELECT * FROM `global_server_data` ORDER BY `sid` ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        } else {
            $s = query("SELECT * FROM `global_server_data` WHERE `sid`=? LIMIT 1", array($id))->fetch(PDO::FETCH_ASSOC);
        }
        return $this->normalizeServer($s);
    }
}
