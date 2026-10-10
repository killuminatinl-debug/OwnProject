<?php
/*
 * Safe installer for the legacy Travian Kingdoms PHP/MySQL codebase.
 * PHP 7.4 compatible. Never truncates an existing game database.
 */
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

$done = false;
$error = '';
$defaults = array(
    'host' => isset($_POST['host']) ? trim((string)$_POST['host']) : '127.0.0.1',
    'user' => isset($_POST['user']) ? trim((string)$_POST['user']) : 'root',
    'name' => isset($_POST['name']) ? trim((string)$_POST['name']) : 'travian_kingdoms'
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $host = $defaults['host'];
        $user = $defaults['user'];
        $pass = isset($_POST['pass']) ? (string)$_POST['pass'] : '';
        $name = $defaults['name'];

        if ($host === '' || !preg_match('/^[A-Za-z0-9_.:-]+$/', $host)) {
            throw new RuntimeException('Ongeldige MySQL-host. Gebruik bijvoorbeeld 127.0.0.1 of localhost.');
        }
        if ($user === '') {
            throw new RuntimeException('Vul een MySQL-gebruikersnaam in.');
        }
        if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $name)) {
            throw new RuntimeException('Ongeldige databasenaam. Gebruik alleen letters, cijfers en underscores (maximaal 64 tekens).');
        }

        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => true
        );
        if (defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS')) {
            $options[constant('PDO::MYSQL_ATTR_MULTI_STATEMENTS')] = true;
        }

        $pdo = new PDO('mysql:host=' . $host . ';charset=utf8mb4', $user, $pass, $options);
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE `' . $name . '`');

        // Do not ever silently wipe an existing game. A fresh/empty database is required.
        $existingTables = (int)$pdo->query(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()"
        )->fetchColumn();
        if ($existingTables > 0) {
            throw new RuntimeException(
                'Deze database bevat al tabellen. Er is niets verwijderd. Kies een lege database, ' .
                'of maak eerst een back-up en verwijder de oude database handmatig als je echt opnieuw wilt beginnen.'
            );
        }

        $sqlFile = __DIR__ . '/travian5.sql';
        if (!is_file($sqlFile) || !is_readable($sqlFile)) {
            throw new RuntimeException('travian5.sql ontbreekt of is niet leesbaar. Zet dit bestand naast install.php.');
        }
        $sql = file_get_contents($sqlFile);
        if ($sql === false || trim($sql) === '') {
            throw new RuntimeException('Het SQL-bestand is leeg of kon niet worden gelezen.');
        }

        /*
         * This dump contains many individual statements. PDO::exec() is not a
         * reliable way to detect errors in a multi-statement dump; use query()
         * and consume every result set so SQL errors stop the installation.
         */
        $statement = $pdo->query($sql);
        do {
            if ($statement->columnCount() > 0) {
                $statement->fetchAll(PDO::FETCH_ASSOC);
            }
        } while ($statement->nextRowset());
        $statement->closeCursor();

        $serverCheck = $pdo->query("SELECT COUNT(*) FROM `global_server_data` WHERE `sid` = 1");
        if ((int)$serverCheck->fetchColumn() !== 1) {
            throw new RuntimeException('De SQL-import is niet volledig: serverconfiguratie s1 ontbreekt.');
        }

        $now = time();
        $update = $pdo->prepare(
            "UPDATE `global_server_data` SET `start` = ?, `maintenance` = 0, `recommended` = 1 WHERE `sid` = 1"
        );
        $update->execute(array((string)$now));

        $config = "<?php\n" .
            "ini_set('display_errors', '0');\n" .
            "ini_set('log_errors', '1');\n" .
            "error_reporting(E_ALL);\n\n" .
            "define('SQL_HOST', " . var_export($host, true) . ");\n" .
            "define('SQL_USER', " . var_export($user, true) . ");\n" .
            "define('SQL_PASS', " . var_export($pass, true) . ");\n" .
            "define('SQL_DATB', " . var_export($name, true) . ");\n" .
            "define('LANGUAGE', 'en');\n" .
            "define('APP_BASE', '');\n" .
            "$index_url = '/';\n" .
            "$mellon_url = '/mellon/';\n" .
            "$cdn_url = '/cdn/';\n" .
            "$lobby_url = '/lobby/';\n" .
            "$game_dir = '/game/s1/';\n" .
            "$domain = isset($_SERVER['HTTP_HOST']) ? preg_replace('/:\\d+$/', '', $_SERVER['HTTP_HOST']) : 'localhost';\n\n" .
            "function protocalRemove($url) { return preg_replace('#^https?://#i', '', $url); }\n" .
            "function myErrorHandler($severity, $message, $file, $line) { if (!(error_reporting() & $severity)) return false; error_log('[OwnProject] ' . $message . ' ' . $file . ':' . $line); return false; }\n" .
            "function fatalErrorShutdownHandler() { $e = error_get_last(); if ($e && in_array($e['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) error_log('[OwnProject] ' . $e['message'] . ' ' . $e['file'] . ':' . $e['line']); }\n" .
            "set_error_handler('myErrorHandler'); register_shutdown_function('fatalErrorShutdownHandler');\n" .
            "$GLOBALS['db'] = null;\n" .
            "function db() { if ($GLOBALS['db'] instanceof PDO) return $GLOBALS['db']; $GLOBALS['db'] = new PDO('mysql:host=' . SQL_HOST . ';dbname=' . SQL_DATB . ';charset=utf8mb4', SQL_USER, SQL_PASS, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false)); $GLOBALS['db']->exec(\"SET SESSION sql_mode='NO_AUTO_VALUE_ON_ZERO'\"); return $GLOBALS['db']; }\n";

        $configPath = __DIR__ . '/config.php';
        $tempPath = $configPath . '.tmp';
        if (file_put_contents($tempPath, $config, LOCK_EX) === false) {
            throw new RuntimeException('config.php kan niet worden geschreven. Controleer de schrijfrechten van de gamemap.');
        }
        if (!@rename($tempPath, $configPath)) {
            @unlink($tempPath);
            throw new RuntimeException('config.php kon niet veilig worden geplaatst. Controleer de schrijfrechten van de gamemap.');
        }

        $done = true;
    } catch (Throwable $e) {
        error_log('[OwnProject installer] ' . $e->getMessage());
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Travian Kingdoms - Installer</title>
<style>
*{box-sizing:border-box}body{font-family:Arial,sans-serif;background:#e8e5de;color:#29251f;margin:0;padding:24px}
.box{max-width:640px;margin:5vh auto;background:#fff;padding:28px;border-radius:8px;box-shadow:0 4px 18px #0002}
h1{margin-top:0}label{display:block;font-weight:bold;margin:14px 0 5px}
input{width:100%;padding:11px;border:1px solid #bbb;border-radius:4px;font-size:16px}
button{padding:12px 20px;font-weight:bold;border:0;border-radius:4px;background:#6c4a2d;color:white;cursor:pointer;margin-top:18px}
.ok,.err{padding:14px;border-radius:4px;line-height:1.5}.ok{background:#e5f6e5}.err{background:#ffe8e8;white-space:pre-wrap}
small{color:#666}a{color:#634323}
</style>
</head>
<body><main class="box">
<h1>Travian Kingdoms</h1><h2>Serverinstallatie</h2>
<?php if ($done): ?>
<div class="ok"><strong>Database-import en configuratie zijn voltooid.</strong><br>De bestaande tabellen zijn niet gewist. Test nu eerst het inloggen en de spelwereld.<br><a href="/">Open de game</a></div>
<?php else: ?>
<?php if ($error !== ''): ?><div class="err"><strong>Installatie mislukt:</strong><br><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
<form method="post" autocomplete="off">
<label for="host">MySQL-host</label><input id="host" name="host" required value="<?php echo htmlspecialchars($defaults['host'], ENT_QUOTES, 'UTF-8'); ?>">
<label for="user">MySQL-gebruiker</label><input id="user" name="user" required value="<?php echo htmlspecialchars($defaults['user'], ENT_QUOTES, 'UTF-8'); ?>">
<label for="pass">MySQL-wachtwoord</label><input id="pass" type="password" name="pass" autocomplete="new-password">
<label for="name">Databasenaam</label><input id="name" name="name" required value="<?php echo htmlspecialchars($defaults['name'], ENT_QUOTES, 'UTF-8'); ?>">
<button type="submit">Database installeren</button>
</form>
<p><small>PHP 7.4, PDO MySQL en MySQL/MariaDB vereist. Gebruik voor een nieuwe installatie een lege database.</small></p>
<?php endif; ?>
</main></body></html>
