<?php
// Local Travian Kingdoms-compatible account registration endpoint (PHP 7.4+).
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once dirname(__DIR__) . '/mellon/engine/session.php';

if (!isset($_SESSION['registration_csrf'])) {
    $_SESSION['registration_csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(isset($_POST['email']) ? (string)$_POST['email'] : '');
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $confirm = isset($_POST['password_confirm']) ? (string)$_POST['password_confirm'] : '';
    $terms = isset($_POST['terms']) && $_POST['terms'] === '1';
    $newsletter = isset($_POST['newsletter']) && $_POST['newsletter'] === '1';
    $csrf = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';

    if (!hash_equals($_SESSION['registration_csrf'], $csrf)) {
        $error = 'Je formulier is verlopen. Vernieuw de pagina en probeer opnieuw.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $error = 'Vul een geldig e-mailadres in.';
    } elseif (strlen($password) < 8) {
        $error = 'Je wachtwoord moet minimaal 8 tekens bevatten.';
    } elseif ($password !== $confirm) {
        $error = 'De wachtwoorden komen niet overeen.';
    } elseif (!$terms) {
        $error = 'Je moet akkoord gaan met de voorwaarden om een account aan te maken.';
    } else {
        try {
            if ($engine->account->EmailValid($email)) {
                $error = 'Voor dit e-mailadres bestaat al een account. Log in of gebruik een ander e-mailadres.';
            } elseif ($engine->account->Signup($email, $password, $newsletter, $terms)) {
                unset($_SESSION['registration_csrf']);
                header('Location: ' . $game_dir . 'api/login.php?token=' . rawurlencode(md5($_SESSION['mellon_msid'])) . '&msid=' . rawurlencode($_SESSION['mellon_msid']), true, 303);
                exit;
            } else {
                $error = 'Het account kon niet worden aangemaakt. Controleer je gegevens en probeer opnieuw.';
            }
        } catch (Throwable $e) {
            error_log('[OwnProject registration] ' . $e->getMessage());
            $error = 'Registreren is tijdelijk niet gelukt. Controleer de databaseverbinding en probeer opnieuw.';
        }
    }
}
?><!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Account aanmaken · Travian Kingdoms</title>
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;background:linear-gradient(135deg,#182b20,#31452d 52%,#141c17);font:15px/1.5 Arial,Helvetica,sans-serif;color:#f5f0df}
.panel{width:100%;max-width:460px;background:#f4efdf;color:#30291e;border:1px solid #b69a68;border-radius:5px;box-shadow:0 16px 50px #0008;overflow:hidden}.head{padding:22px 28px;background:linear-gradient(#6e4d2c,#3e2d1d);border-bottom:3px solid #a9844f;text-align:center;color:#fff3d4}.head .mark{font-size:12px;letter-spacing:3px;text-transform:uppercase;color:#e2c48c}.head h1{font-family:Georgia,serif;font-size:28px;margin:6px 0 0;font-weight:normal}.body{padding:26px 28px 30px}.intro{margin:0 0 20px;color:#655744}.field{margin:0 0 15px}.field label{display:block;font-weight:bold;font-size:13px;margin-bottom:6px}.field input{width:100%;height:43px;padding:10px 12px;border:1px solid #b7aa91;border-radius:3px;background:#fffdf7;color:#30291e;font-size:15px}.field input:focus{outline:2px solid #b28b4f;outline-offset:1px}.check{display:flex;gap:9px;align-items:flex-start;margin:12px 0;font-size:13px;color:#544a3b}.check input{margin-top:4px}.error{padding:11px 12px;margin-bottom:17px;background:#f6dfd8;border:1px solid #bc6e5e;border-radius:3px;color:#702d21}.submit{width:100%;border:1px solid #63451f;border-radius:3px;padding:13px 16px;margin-top:10px;background:linear-gradient(#b68a45,#86602c);color:white;font-size:16px;font-weight:bold;text-shadow:0 1px #49351d;cursor:pointer}.submit:hover{filter:brightness(1.08)}.foot{text-align:center;margin:19px 0 0;font-size:13px;color:#6d604d}.foot a{color:#67451e;font-weight:bold}.hint{font-size:12px;color:#7a6d5a;margin-top:5px}
</style>
</head>
<body>
<main class="panel">
  <header class="head"><div class="mark">Travian Kingdoms</div><h1>Maak je account</h1></header>
  <section class="body">
    <p class="intro">Maak een account aan om je rijk te beginnen.</p>
    <?php if ($error !== ''): ?><div class="error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <form method="post" action="" autocomplete="on">
      <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['registration_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
      <div class="field"><label for="email">E-mailadres</label><input id="email" name="email" type="email" required maxlength="254" autocomplete="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"></div>
      <div class="field"><label for="password">Wachtwoord</label><input id="password" name="password" type="password" required minlength="8" autocomplete="new-password"><div class="hint">Minimaal 8 tekens.</div></div>
      <div class="field"><label for="password_confirm">Herhaal wachtwoord</label><input id="password_confirm" name="password_confirm" type="password" required minlength="8" autocomplete="new-password"></div>
      <label class="check"><input type="checkbox" name="terms" value="1" required><span>Ik ga akkoord met de spelvoorwaarden.</span></label>
      <label class="check"><input type="checkbox" name="newsletter" value="1"><span>Ik wil updates ontvangen (optioneel).</span></label>
      <button class="submit" type="submit">Account aanmaken</button>
    </form>
    <p class="foot">Heb je al een account? <a href="/mellon/authentication/login/">Inloggen</a></p>
  </section>
</main>
</body>
</html>
