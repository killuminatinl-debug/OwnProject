<?php
/**
 * Database smoke test for the legacy Travian Kingdom schema.
 * This validates the real MariaDB schema and the columns required by build queues.
 * It does not claim to simulate complete game play.
 */
$host = getenv('DB_HOST') ?: '127.0.0.1';
$name = getenv('DB_NAME') ?: 'travian5_new';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'root';

$pdo = new PDO(
    "mysql:host={$host};dbname={$name};charset=utf8",
    $user,
    $pass,
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC)
);

$required = array('id', 'wid', 'location', 'type', 'sort', 'start', 'duration', 'timestamp', 'queue', 'paid', 'cost', 'level');
$columns = $pdo->query("SHOW COLUMNS FROM s1_building")->fetchAll();
$names = array_column($columns, 'Field');
foreach ($required as $column) {
    if (!in_array($column, $names, true)) {
        throw new RuntimeException("s1_building missing required column: " . $column);
    }
}

$wid = 'ci_' . substr(hash('sha256', uniqid('', true)), 0, 20);
try {
    $insert = $pdo->prepare(
        "INSERT INTO s1_building
         (wid, location, sort, type, start, duration, timestamp, queue, paid, cost, level)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $insert->execute(array($wid, 19, 1, 15, time(), 60, time() + 60, 1, 1, json_encode(array(
        'wood' => 100, 'clay' => 100, 'iron' => 100, 'crop' => 100, 'time' => 60
    )), 1));

    $select = $pdo->prepare("SELECT * FROM s1_building WHERE wid=? AND queue=1");
    $select->execute(array($wid));
    $row = $select->fetch();
    if (!$row || (int)$row['location'] !== 19 || (int)$row['paid'] !== 1 || (int)$row['level'] !== 1) {
        throw new RuntimeException('Build queue row did not round-trip correctly.');
    }

    $cost = json_decode($row['cost'], true);
    if (!is_array($cost) || !isset($cost['wood'], $cost['clay'], $cost['iron'], $cost['crop'])) {
        throw new RuntimeException('Build queue did not preserve its resource cost.');
    }
    echo "PASS: building queue schema, insert/read, and saved resource cost.\n";
} finally {
    $delete = $pdo->prepare("DELETE FROM s1_building WHERE wid=?");
    $delete->execute(array($wid));
}
