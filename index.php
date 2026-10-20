<?php
declare(strict_types=1);

define('PANEL_RUNNING', true);
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/actions.php';
require __DIR__ . '/app/views.php';

$pages = [
    'dashboard' => 'Übersicht', 'customers' => 'Kunden', 'customer' => 'Kundendetails',
    'customer_edit' => 'Kunde bearbeiten', 'projects' => 'Projekte', 'project' => 'Projektdetails',
    'project_edit' => 'Projekt bearbeiten', 'products' => 'Produkte & Leistungen',
    'product_edit' => 'Produkt bearbeiten', 'tasks' => 'Aufgaben', 'finances' => 'Finanzen',
    'settings' => 'Einstellungen', 'login' => 'Anmelden', 'register' => 'Admin einrichten',
    'export' => 'Zahlungsexport',
];
$requestedPage = $_GET['page'] ?? 'dashboard';
$page = is_string($requestedPage) && isset($pages[$requestedPage]) ? $requestedPage : 'dashboard';
$loggedIn = authenticated();
if (!$loggedIn && !in_array($page, ['login', 'register'], true)) {
    redirect('login');
}

try {
    $hasAdmin = one('SELECT id FROM admins LIMIT 1') !== null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            handle_action(text('action', 40));
        } catch (InvalidArgumentException $error) {
            flash($error->getMessage(), 'danger');
        } catch (Throwable $error) {
            error_log('IT-Panel action: ' . $error->getMessage());
            flash('Die Änderung konnte nicht gespeichert werden. Bitte Eingaben prüfen und erneut versuchen.', 'danger');
        }
    }
    if ($loggedIn && in_array($page, ['login', 'register'], true)) {
        redirect('dashboard');
    }
    if ($page === 'register' && $hasAdmin) {
        flash('Die Registrierung ist geschlossen. Bitte mit dem Admin-Konto anmelden.', 'info');
        redirect('login');
    }
    if ($page === 'export') {
        export_payments();
    }
    if (in_array($page, ['login', 'register'], true)) {
        render_auth($page, $hasAdmin);
    } else {
        $admin = one('SELECT * FROM admins WHERE id = ?', [$_SESSION['admin_id']]);
        if (!$admin) {
            unset($_SESSION['admin_id']);
            redirect('login');
        }
        ob_start();
        try {
            render_page($page);
            $body = ob_get_clean();
        } catch (InvalidArgumentException $error) {
            ob_end_clean();
            http_response_code(404);
            $body = '<div class="empty-state"><h2>Eintrag nicht gefunden</h2><p>' . e($error->getMessage()) . '</p><a href="' . e(url('dashboard')) . '">Zur Übersicht</a></div>';
        }
        render_layout($page, $pages[$page], $admin, $body);
    }
} catch (Throwable $error) {
    error_log('IT-Panel: ' . $error->getMessage());
    http_response_code(503);
    while (ob_get_level()) {
        ob_end_clean();
    }
    ?>
    <!doctype html><html lang="de" data-bs-theme="dark"><meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Einrichtung prüfen · IT-Panel</title>
    <link rel="stylesheet" href="assets/vendor/bootstrap.min.css">
    <main class="container py-5"><h1>Einrichtung prüfen</h1>
    <p>Die Datenbank ist nicht erreichbar oder das Schema fehlt.</p>
    <p>Bitte <code>database/schema.sql</code> importieren und die Datenbankverbindung
    in <code>config.php</code> eintragen. Weitere Hinweise stehen in der mitgelieferten INSTALL.txt.</p>
    <p class="text-body-secondary">Technische Details werden ausschließlich im PHP-Serverlog protokolliert.</p></main></html>
    <?php
}
