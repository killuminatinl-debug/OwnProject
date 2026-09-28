<?php

class Session {

    public $data = null;

    public function MakeLogin() {
        global $engine;
        $q = query("SELECT * FROM `global_user` WHERE `username`=?;", array($_GET['username']));
        if ($q->rowCount() == 1) {
            $q2 = query("SELECT * FROM `" . $engine->server->prefix . "user` WHERE `username`=?;", array($_GET['username']));
            if ($q2->rowCount() == 0) {
                $u = $q->fetch();
                if ($engine->account->Signup($_GET['username'])) {
                    $engine->account->Login($_GET['username']);
                    header("Location: ?token=");
                } else {
                    header("Location: ../../?lobby");
                }
            } else {
                $engine->account->Login($_GET['username']);
                header("Location: ?token=");
            }
        } else {
            header("Location: ../../?lobby");
        }
    }

    public function checkLogin() {
        global $engine;

        // Recover the game-world session from the lobby account when the
        // browser lost the world-specific cookie/session.
        $key = $engine->server->prefix . 'uid';
        if (!isset($_SESSION[$key]) || $_SESSION[$key] === '') {
            $globalUid = (int)($_SESSION['mellon_uid'] ?? $_SESSION['lobby_uid'] ?? 0);
            if ($globalUid > 0) {
                $candidate = query("SELECT * FROM `" . $engine->server->prefix . "user` WHERE `uid`=? LIMIT 1", array($globalUid))->fetch(PDO::FETCH_ASSOC);
                if (!$candidate) {
                    $email = (string)($_SESSION['mellon_email'] ?? $_SESSION['lobby_email'] ?? '');
                    if ($email !== '') {
                        $candidate = query("SELECT * FROM `" . $engine->server->prefix . "user` WHERE `email`=? LIMIT 1", array($email))->fetch(PDO::FETCH_ASSOC);
                    }
                }
                if ($candidate) {
                    $_SESSION[$key] = $candidate['uid'];
                }
            }
        }

        if (isset($_SESSION[$key])) {
            if ($_SESSION[$engine->server->prefix . 'uid'] != "") {
                $u = query("SELECT * FROM `" . $engine->server->prefix . "user` WHERE `uid`=?;", array($_SESSION[$engine->server->prefix . 'uid']))->fetch(PDO::FETCH_ASSOC);
                if (!$u) {
                    unset($_SESSION[$engine->server->prefix . 'uid']);
                    return false;
                }
                $this->data = (object) $u;
                query("UPDATE `" . $engine->server->prefix . "user` SET `online`=1,`lastLogin`=? WHERE `uid`=?", [time(), $u['uid']]);
                // Single-world bootstrap: every authenticated player must have a playable village.
                // Older installs can contain accounts with tribe/tutorial unset and no village; repair
                // those accounts here so the client never gets stuck before the tribe/tutorial screen.
                if ((int)$u['tribe'] < 1 || (int)$u['tribe'] > 7) {
                    query("UPDATE `" . $engine->server->prefix . "user` SET `tribe`=1,`tutorial`=256 WHERE `uid`=?", array($u['uid']));
                    $u['tribe'] = 1;
                    $u['tutorial'] = 256;
                } elseif ((int)$u['tutorial'] < 256) {
                    query("UPDATE `" . $engine->server->prefix . "user` SET `tutorial`=256 WHERE `uid`=?", array($u['uid']));
                    $u['tutorial'] = 256;
                }

                $hasVillage = query(
                    "SELECT `wid` FROM `" . $engine->server->prefix . "village` WHERE `owner`=? ORDER BY `wid` ASC LIMIT 1",
                    array($u['uid'])
                )->fetch(PDO::FETCH_ASSOC);

                if (!$hasVillage) {
                    try {
                        $position = $engine->world->bestPosition();
                        $newWid = is_array($position) ? (int)$position[0] : (int)$position;
                        if ($newWid > 0) {
                            $engine->village->createVillage($u['uid'], $u['username'], $newWid);
                            $hasVillage = array('wid' => $newWid);
                        }
                    } catch (Throwable $bootstrapError) {
                        error_log('[OwnProject] village bootstrap failed: '.$bootstrapError->getMessage().' in '.$bootstrapError->getFile().':'.$bootstrapError->getLine());
                    }
                }

                $_SESSION[$engine->server->prefix . 'uid'] = $u['uid'];
                $_SESSION[$engine->server->prefix . 'username'] = $u['username'];
                $_SESSION[$engine->server->prefix . 'avatar'] = $u['avatar'];
                $_SESSION[$engine->server->prefix . 'gold'] = $u['gold'];
                $_SESSION[$engine->server->prefix . 'tribe'] = $u['tribe'];
                $_SESSION[$engine->server->prefix . 'tutorial'] = $u['tutorial'];

                if ($hasVillage) {
                    setcookie("village", (int)$hasVillage['wid'], 0, "/");
                    $_COOKIE['village'] = (int)$hasVillage['wid'];
                }

                $this->data = (object)$u;
                return true;
            } else {
                return false;
            }
        }
    }

    public function serialNo($uid = null) {
        global $engine;
        if ($uid === null) {
            $uid = $_SESSION[$engine->server->prefix . 'uid'];
        }
        $u = query("SELECT * FROM `" . $engine->server->prefix . "user` WHERE `uid`=?;", array($uid))->fetch();
        $_SESSION[$engine->server->prefix . 'serial'] = ((int)$u['serial']) + 1;
        query("UPDATE `" . $engine->server->prefix . "user` SET `serial`=? WHERE `uid`=?;", array($_SESSION[$engine->server->prefix . 'serial'], $uid));
        return $_SESSION[$engine->server->prefix . 'serial'];
    }

}

?>
