<?php
declare(strict_types=1);

defined('PANEL_RUNNING') || exit;

$config = require dirname(__DIR__) . '/config.php';
date_default_timezone_set($config['timezone']);
ini_set('display_errors', '0');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
$https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' && $_SERVER['HTTPS'] !== '';
session_name('it_panel_session');
session_set_cookie_params([
    'httponly' => true, 'secure' => $https, 'samesite' => 'Strict', 'path' => '/',
]);
session_start();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; object-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'");
if ($https) {
    header('Strict-Transport-Security: max-age=31536000');
}

function db(): PDO
{
    static $pdo;
    global $config;
    if (!$pdo) {
        $c = $config['database'];
        $pdo = new PDO(
            "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset=utf8mb4",
            $c['user'],
            $c['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }
    return $pdo;
}

function query(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function rows(string $sql, array $params = []): array
{
    return query($sql, $params)->fetchAll();
}

function one(string $sql, array $params = []): ?array
{
    return query($sql, $params)->fetch() ?: null;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money(mixed $value): string
{
    return number_format((float) $value, 2, ',', '.') . ' €';
}

function date_de(?string $value): string
{
    return $value ? date('d.m.Y', strtotime($value)) : '–';
}

function url(string $page, array $params = []): string
{
    return 'index.php?' . http_build_query(['page' => $page] + $params);
}

function redirect(string $page, array $params = []): never
{
    header('Location: ' . url($page, $params), true, 303);
    exit;
}

function csrf(): string
{
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}

function verify_csrf(): void
{
    if (!is_string($_POST['csrf'] ?? null)
        || !is_string($_SESSION['csrf'] ?? null)
        || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        throw new InvalidArgumentException('Die Sitzung ist abgelaufen. Bitte die Seite neu laden.');
    }
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flashes'][] = ['message' => $message, 'type' => $type];
}

function text(string $name, int $max = 190, bool $required = true): string
{
    $value = $_POST[$name] ?? '';
    if (!is_string($value)) {
        throw new InvalidArgumentException('Ungültige Eingabe: ' . $name);
    }
    $value = trim($value);
    if (($required && $value === '') || mb_strlen($value) > $max) {
        throw new InvalidArgumentException('Bitte das Feld „' . $name . '“ korrekt ausfüllen (max. ' . $max . ' Zeichen).');
    }
    return $value;
}

function email_input(): string
{
    $value = mb_strtolower(text('email', 190));
    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Bitte eine gültige E-Mail-Adresse eingeben.');
    }
    return $value;
}

function secret_input(string $name, bool $required = true, int $max = 128): string
{
    $value = $_POST[$name] ?? '';
    if (!is_string($value) || ($required && $value === '') || strlen($value) > $max) {
        throw new InvalidArgumentException('Bitte das Geheimnisfeld korrekt ausfüllen.');
    }
    return $value;
}

function integer(string $name, int $min = 1, int $max = 2147483647): int
{
    $value = filter_var($_POST[$name] ?? null, FILTER_VALIDATE_INT);
    if ($value === false || $value === null || $value < $min || $value > $max) {
        throw new InvalidArgumentException('Ungültiger Wert: ' . $name);
    }
    return $value;
}

function decimal(string $name, bool $positive = false, int $maxWhole = 10): string
{
    $value = str_replace(',', '.', text($name, 20));
    if (!preg_match('/^\d{1,' . $maxWhole . '}(?:\.\d{1,2})?$/D', $value)
        || ($positive && (float) $value <= 0)) {
        throw new InvalidArgumentException('Bitte einen gültigen positiven Betrag mit maximal zwei Nachkommastellen eingeben.');
    }
    return number_format((float) $value, 2, '.', '');
}

function date_input(string $name, bool $required = false): ?string
{
    $value = text($name, 10, $required);
    if ($value === '') {
        return null;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value || (int) $date->format('Y') < 1000) {
        throw new InvalidArgumentException('Bitte ein gültiges Datum eingeben.');
    }
    return $value;
}

function require_record(string $table, int $id): array
{
    $allowed = ['customers', 'projects', 'payments', 'products', 'tasks', 'expenses', 'project_items'];
    if (!in_array($table, $allowed, true)) {
        throw new LogicException('Unknown table');
    }
    $record = one("SELECT * FROM $table WHERE id = ?", [$id]);
    if (!$record) {
        throw new InvalidArgumentException('Der Eintrag wurde nicht gefunden.');
    }
    return $record;
}

function authenticated(): bool
{
    if (!isset($_SESSION['admin_id'], $_SESSION['last_activity'])) {
        return false;
    }
    if (time() - (int) $_SESSION['last_activity'] > 1800) {
        unset($_SESSION['admin_id'], $_SESSION['last_activity']);
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

function throttle_auth(): void
{
    $bucket = hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    $now = time();
    db()->beginTransaction();
    try {
        query('DELETE FROM auth_throttles WHERE window_start < ?', [$now - 86400]);
        query('INSERT INTO auth_throttles (bucket, attempts, window_start) VALUES (?, 0, ?) ON DUPLICATE KEY UPDATE bucket = VALUES(bucket)', [$bucket, $now]);
        $row = one('SELECT * FROM auth_throttles WHERE bucket = ? FOR UPDATE', [$bucket]);
        $attempts = $row['window_start'] > $now - 900 ? (int) $row['attempts'] : 0;
        if ($attempts >= 10) {
            throw new InvalidArgumentException('Zu viele Anmeldeversuche. Bitte nach 15 Minuten erneut versuchen.');
        }
        query('UPDATE auth_throttles SET attempts = ?, window_start = ? WHERE bucket = ?', [
            $attempts + 1, $attempts === 0 ? $now : $row['window_start'], $bucket,
        ]);
        db()->commit();
    } catch (Throwable $error) {
        db()->rollBack();
        throw $error;
    }
}

function status_label(string $status): string
{
    return ['active' => 'In Arbeit', 'paused' => 'Pausiert', 'done' => 'Abgeschlossen'][$status] ?? $status;
}
