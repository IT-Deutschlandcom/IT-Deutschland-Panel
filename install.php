<?php

declare(strict_types=1);

require __DIR__ . '/app/Database.php';
require __DIR__ . '/app/Security.php';

$config = require __DIR__ . '/config.php';
date_default_timezone_set($config['app']['timezone']);
Security::startSession((int) $config['app']['session_timeout']);

$error = '';
$installed = false;

try {
    $pdo = Database::connection();
    $installed = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0;
} catch (Throwable $exception) {
    http_response_code(503);
    $error = 'Datenbank nicht erreichbar. Prüfen Sie config.php und importieren Sie database.sql.';
}

if (!$installed && $error === '' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Die Sitzung ist abgelaufen. Bitte laden Sie die Seite neu.';
    } elseif ($name === '' || strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Bitte geben Sie einen gültigen Namen und eine gültige E-Mail-Adresse an.';
    } elseif (strlen($password) < 12) {
        $error = 'Das Passwort muss mindestens 12 Zeichen lang sein.';
    } else {
        try {
            $pdo->beginTransaction();
            $roleId = $pdo->query("SELECT id FROM roles WHERE name = 'superadministrator'")->fetchColumn();
            if (!$roleId) {
                throw new RuntimeException('Die Rollen fehlen. Importieren Sie database.sql erneut.');
            }

            $statement = $pdo->prepare(
                'INSERT INTO admins (role_id, name, email, password_hash, created_at)
                 VALUES (:role_id, :name, :email, :password_hash, UTC_TIMESTAMP())'
            );
            $statement->execute([
                'role_id' => $roleId,
                'name' => $name,
                'email' => strtolower($email),
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            $pdo->commit();
            session_regenerate_id(true);
            $_SESSION = [];
            $installed = true;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Administrator konnte nicht angelegt werden. Prüfen Sie die Angaben und das Datenbankprotokoll.';
        }
    }
}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Installation – <?= Security::escape($config['app']['name']) ?></title>
    <style>
        body{font:16px system-ui,sans-serif;max-width:34rem;margin:8vh auto;padding:0 1rem;color:#172033}
        label{display:block;margin:1rem 0 .3rem}input{box-sizing:border-box;width:100%;padding:.7rem}
        button{margin-top:1.2rem;padding:.7rem 1rem} .error{color:#a00}
    </style>
</head>
<body>
<h1>Administrator einrichten</h1>
<?php if ($installed): ?>
    <p>Die Installation ist abgeschlossen. Rufen Sie jetzt die Anmeldung auf.</p>
<?php elseif ($error !== ''): ?>
    <p class="error"><?= Security::escape($error) ?></p>
<?php endif; ?>
<?php if (!$installed && $error !== 'Datenbank nicht erreichbar. Prüfen Sie config.php und importieren Sie database.sql.'): ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= Security::escape(Security::csrfToken()) ?>">
        <label for="name">Name</label>
        <input id="name" name="name" maxlength="120" required autocomplete="name">
        <label for="email">E-Mail-Adresse</label>
        <input id="email" name="email" type="email" maxlength="254" required autocomplete="email">
        <label for="password">Passwort (mindestens 12 Zeichen)</label>
        <input id="password" name="password" type="password" minlength="12" required autocomplete="new-password">
        <button type="submit">Administrator erstellen</button>
    </form>
<?php endif; ?>
</body>
</html>
