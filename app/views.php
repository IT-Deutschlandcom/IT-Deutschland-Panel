<?php
declare(strict_types=1);

defined('PANEL_RUNNING') || exit;

function head(string $title): void
{
    global $config;
    ?>
    <!doctype html>
    <html lang="de" data-bs-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="dark">
        <title><?= e($title) ?> · <?= e($config['name']) ?></title>
        <link rel="stylesheet" href="assets/vendor/bootstrap.min.css">
        <link rel="stylesheet" href="assets/css/panel.css">
        <script src="assets/vendor/bootstrap.bundle.min.js" defer></script>
        <script src="assets/js/panel.js" defer></script>
    </head>
    <?php
}

function alerts(): void
{
    foreach ($_SESSION['flashes'] ?? [] as $flash) {
        ?>
        <div class="alert alert-<?= e($flash['type']) ?> d-flex justify-content-between gap-3" role="alert">
            <span><?= e($flash['message']) ?></span>
            <button type="button" class="btn-close" data-dismiss-alert aria-label="Meldung schließen"></button>
        </div>
        <?php
    }
    unset($_SESSION['flashes']);
}

function form_start(string $action, ?int $id = null, ?string $confirm = null): void
{
    echo '<form method="post" action=""' . ($confirm ? ' data-confirm="' . e($confirm) . '"' : '') . '>';
    echo csrf() . '<input type="hidden" name="action" value="' . e($action) . '">';
    if ($id !== null) {
        echo '<input type="hidden" name="id" value="' . $id . '">';
    }
}

function input(string $label, string $name, mixed $value = '', string $type = 'text', bool $required = false, int $max = 190): void
{
    if ($type !== 'password' && isset($_POST[$name]) && is_string($_POST[$name])) {
        $value = $_POST[$name];
    }
    $id = 'field-' . $name;
    ?>
    <div class="form-field">
        <label class="form-label" for="<?= e($id) ?>"><?= e($label) ?><?= $required ? ' *' : '' ?></label>
        <input class="form-control" id="<?= e($id) ?>" name="<?= e($name) ?>" type="<?= e($type) ?>"
               value="<?= $type === 'password' ? '' : e($value) ?>" <?= $required ? 'required' : '' ?>
               <?= $type === 'text' || $type === 'email' || $type === 'password' ? 'maxlength="' . $max . '"' : '' ?>
               <?= $type === 'number' ? 'min="0" max="9999999999.99" step="0.01"' : '' ?>
               <?= $type === 'password' ? 'autocomplete="' . (in_array($name, ['new_password', 'password_confirm'], true) ? 'new-password' : 'current-password') . '"' : '' ?>>
    </div>
    <?php
}

function textarea(string $label, string $name, mixed $value = '', int $max = 10000): void
{
    if (isset($_POST[$name]) && is_string($_POST[$name])) {
        $value = $_POST[$name];
    }
    ?>
    <div class="form-field field-full">
        <label class="form-label" for="field-<?= e($name) ?>"><?= e($label) ?></label>
        <textarea class="form-control" id="field-<?= e($name) ?>" name="<?= e($name) ?>" rows="4" maxlength="<?= $max ?>"><?= e($value) ?></textarea>
    </div>
    <?php
}

function select_field(string $label, string $name, array $options, mixed $value, bool $required = true): void
{
    if (isset($_POST[$name]) && is_string($_POST[$name])) {
        $value = $_POST[$name];
    }
    ?>
    <div class="form-field">
        <label class="form-label" for="field-<?= e($name) ?>"><?= e($label) ?><?= $required ? ' *' : '' ?></label>
        <select class="form-select" name="<?= e($name) ?>" id="field-<?= e($name) ?>" <?= $required ? 'required' : '' ?>>
            <?php foreach ($options as $key => $text): ?>
                <option value="<?= e($key) ?>" <?= (string) $key === (string) $value ? 'selected' : '' ?>><?= e($text) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php
}

function save_button(string $cancel): void
{
    echo '<div class="form-actions field-full"><button class="btn btn-primary" type="submit">Änderungen speichern</button> <a class="btn btn-outline-secondary" href="' . e($cancel) . '">Abbrechen</a></div></div></form>';
}

function delete_button(string $action, int $id, string $label = 'Löschen'): void
{
    form_start($action, $id, 'Diesen Eintrag wirklich unwiderruflich löschen?');
    echo '<button type="submit" class="btn btn-sm btn-outline-danger">' . e($label) . '</button></form>';
}

function search_toolbar(string $placeholder, string $href, string $label): void
{
    ?>
    <div class="toolbar mb-4">
        <input type="search" class="form-control search-input" data-search placeholder="<?= e($placeholder) ?>" aria-label="<?= e($placeholder) ?>">
        <a class="btn btn-primary" href="<?= e($href) ?>">＋ <?= e($label) ?></a>
    </div>
    <?php
}

function empty_state(string $message, ?string $href = null, string $label = 'Jetzt anlegen'): void
{
    echo '<div class="empty-state"><div class="stat-icon" aria-hidden="true">◇</div><h3>Noch keine Einträge</h3><p>' . e($message) . '</p>';
    if ($href) {
        echo '<a class="btn btn-primary" href="' . e($href) . '">' . e($label) . '</a>';
    }
    echo '</div>';
}

function render_auth(string $page, bool $hasAdmin): void
{
    global $config;
    head($page === 'register' ? 'Admin einrichten' : 'Willkommen zurück');
    ?>
    <body class="auth-page">
    <main class="auth-shell">
        <section class="auth-aside">
            <div class="brand"><span class="brand-mark">IT</span><span><strong class="brand-name"><?= e($config['name']) ?></strong><span class="brand-subtitle">SERVICE CONTROL CENTER</span></span></div>
            <span class="badge text-bg-primary mb-4">Dein Business. Dein Überblick.</span>
            <h1>Mehr Zeit für IT.<br>Alles andere im Blick.</h1>
            <p>Kunden, Projekte und Finanzen – in deinem persönlichen Service-Dashboard.</p>
            <div class="auth-features">
                <div class="auth-feature">◇ Kunden &amp; Projekte zentral verwalten</div>
                <div class="auth-feature">↗ Fortschritt &amp; Einnahmen verfolgen</div>
                <div class="auth-feature">▣ Lokal betrieben. Nur für dich.</div>
            </div>
        </section>
        <section class="auth-card">
            <div class="auth-logo brand-mark">IT</div>
            <p class="text-muted text-uppercase small">ADMIN WORKSPACE</p>
            <h2 class="login-heading"><?= $page === 'register' ? 'Dein Admin-Zugang' : 'Willkommen zurück' ?></h2>
            <p class="text-muted"><?= $page === 'register' ? 'Einmalige, geschützte Einrichtung deines Panels.' : 'Melde dich an und bring deine Projekte voran.' ?></p>
            <?php alerts(); ?>
            <?php if ($page === 'register' && strlen($config['registration_key']) < 32): ?>
                <div class="alert alert-warning">Bitte zuerst in config.php einen zufälligen Einrichtungsschlüssel mit mindestens 32 Zeichen setzen. Die Registrierung bleibt bis dahin gesperrt.</div>
            <?php endif; ?>
            <?php form_start($page); ?>
            <div class="form-grid">
                <?php
                if ($page === 'register') {
                    input('Dein Name', 'name', '', 'text', true, 120);
                }
                input('E-Mail-Adresse', 'email', '', 'email', true);
                input('Passwort' . ($page === 'register' ? ' (mindestens 12 Zeichen)' : ''), 'password', '', 'password', true, 128);
                ?>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-password-toggle="field-password">Passwort anzeigen</button>
                <?php if ($page === 'register'): ?>
                    <?php input('Passwort wiederholen', 'password_confirm', '', 'password', true, 128); ?>
                    <?php input('Einrichtungsschlüssel aus config.php', 'registration_key', '', 'password', true, 256); ?>
                <?php endif; ?>
                <button class="btn btn-primary btn-lg w-100" type="submit"><?= $page === 'register' ? 'Admin-Zugang erstellen' : 'Sicher anmelden →' ?></button>
            </div></form>
            <div class="auth-footer mt-4">
                <?php if ($page === 'register'): ?>
                    <a href="<?= e(url('login')) ?>">← Zur Anmeldung</a>
                <?php elseif (!$hasAdmin): ?>
                    Noch kein Zugang? <a href="<?= e(url('register')) ?>">Admin einrichten</a>
                <?php else: ?>
                    <span class="text-muted small">Geschützter Bereich · Registrierung geschlossen</span>
                <?php endif; ?>
            </div>
        </section>
    </main></body></html>
    <?php
}

function render_layout(string $page, string $title, array $admin, string $body): void
{
    global $config;
    $nav = [
        'dashboard' => ['◈', 'Übersicht'], 'customers' => ['♙', 'Kunden'],
        'projects' => ['▤', 'Projekte'], 'products' => ['◇', 'Produkte & Leistungen'],
        'tasks' => ['☑', 'Aufgaben'], 'finances' => ['↗', 'Finanzen'], 'settings' => ['⚙', 'Einstellungen'],
    ];
    $activePage = str_starts_with($page, 'customer') ? 'customers'
        : (str_starts_with($page, 'project') ? 'projects' : (str_starts_with($page, 'product') ? 'products' : $page));
    head($title);
    ?>
    <body>
    <a class="visually-hidden-focusable position-absolute p-3" href="#main">Zum Inhalt springen</a>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar" aria-label="Seitenleiste">
            <a class="brand" href="<?= e(url('dashboard')) ?>"><span class="brand-mark">IT</span><span><strong class="brand-name"><?= e($config['name']) ?></strong><span class="brand-subtitle">SERVICE PANEL</span></span></a>
            <div class="text-muted small px-3 mb-3 mt-4">WORKSPACE</div>
            <nav aria-label="Hauptnavigation" class="nav flex-column gap-1">
                <?php foreach ($nav as $key => [$icon, $label]): ?>
                    <a class="nav-link <?= $activePage === $key ? 'active' : '' ?>" href="<?= e(url($key)) ?>" <?= $activePage === $key ? 'aria-current="page"' : '' ?>><span class="nav-icon" aria-hidden="true"><?= $icon ?></span><?= e($label) ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="sidebar-footer">
                <div class="d-flex align-items-center gap-3 mb-3"><span class="avatar"><?= e(mb_strtoupper(mb_substr($admin['name'], 0, 1))) ?></span><div><strong><?= e($admin['name']) ?></strong><div class="text-muted small">Administrator</div></div></div>
                <?php form_start('logout'); ?><button type="submit" class="btn btn-outline-secondary w-100">Abmelden ↗</button></form>
            </div>
        </aside>
        <div class="main-content">
            <header class="topbar">
                <div class="d-flex gap-3 align-items-center">
                    <button type="button" class="btn btn-outline-secondary sidebar-toggle" id="sidebar-toggle" aria-controls="sidebar" aria-expanded="false" aria-label="Menü öffnen">☰</button>
                    <div class="page-heading"><div class="text-muted small">Workspace / <?= e($title) ?></div><h1 class="page-title"><?= e($title) ?></h1></div>
                </div>
                <div class="topbar-actions"><span class="text-muted small d-none d-md-inline"><?= e(date_de(date('Y-m-d'))) ?></span><span class="badge status-active">● Admin</span></div>
            </header>
            <main id="main" class="content">
                <?php alerts(); ?>
                <?= $body ?>
            </main>
            <footer class="page-footer"><?= e($config['name']) ?> · Dein Service. Dein Überblick. <span class="text-muted">PHP &amp; MySQL · Lokal betrieben</span></footer>
        </div>
    </div>
    </body></html>
    <?php
}

function render_page(string $page): void
{
    match ($page) {
        'dashboard' => render_dashboard(),
        'customers' => render_customers(),
        'customer' => render_customer(),
        'customer_edit' => render_customer_edit(),
        'projects' => render_projects(),
        'project' => render_project(),
        'project_edit' => render_project_edit(),
        'products' => render_products(),
        'product_edit' => render_product_edit(),
        'tasks' => render_tasks(),
        'finances' => render_finances(),
        'settings' => render_settings(),
        default => render_dashboard(),
    };
}

function get_id(): int
{
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || $id < 1) {
        throw new InvalidArgumentException('Ungültige Eintragsnummer.');
    }
    return $id;
}

function project_rows(string $where = '', array $params = []): array
{
    return rows("SELECT p.*, c.name AS customer_name, r.name AS priority_name, r.color AS priority_color,
        COALESCE(pay.paid, 0) AS paid FROM projects p
        JOIN customers c ON c.id=p.customer_id JOIN priorities r ON r.id=p.priority_id
        LEFT JOIN (SELECT project_id, SUM(amount) AS paid FROM payments WHERE paid_on <= ? GROUP BY project_id) pay ON pay.project_id=p.id
        $where", [date('Y-m-d'), ...$params]);
}

function project_table(array $projects): void
{
    if (!$projects) {
        empty_state('Lege deinen ersten Kundenauftrag an.', url('project_edit'), 'Projekt anlegen');
        return;
    }
    ?>
    <div class="table-responsive"><table class="table align-middle">
        <thead><tr><th>Projekt / Kunde</th><th>Priorität</th><th>Fortschritt</th><th>Budget / Offen</th><th>Termin</th></tr></thead>
        <tbody>
        <?php foreach ($projects as $project): ?>
            <tr data-search-row>
                <td><a class="customer-name" href="<?= e(url('project', ['id' => $project['id']])) ?>"><?= e($project['title']) ?></a><div class="text-muted small"><?= e($project['customer_name']) ?></div></td>
                <td><span class="badge priority-<?= e($project['priority_color']) ?>"><?= e($project['priority_name']) ?></span></td>
                <td><div class="progress-meta"><span class="small"><?= e(status_label($project['status'])) ?></span><span><?= (int) $project['progress'] ?>%</span></div><div class="progress" role="progressbar" aria-label="<?= e('Fortschritt ' . $project['title']) ?>" aria-valuenow="<?= (int) $project['progress'] ?>" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width:<?= (int) $project['progress'] ?>%"></div></div></td>
                <td><?= e(money($project['total_price'])) ?><div class="text-muted small">Offen: <?= e(money(max(0, $project['total_price'] - $project['paid']))) ?></div></td>
                <td class="<?= $project['due_date'] && $project['due_date'] < date('Y-m-d') && $project['status'] !== 'done' ? 'text-danger' : 'text-muted' ?>"><?= e(date_de($project['due_date'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php
}

function stat_card(string $label, mixed $value, string $icon, string $note, bool $currency = false): void
{
    ?>
    <div class="stat-card reveal"><div class="d-flex justify-content-between align-items-center"><span class="stat-label"><?= e($label) ?></span><span class="stat-icon" aria-hidden="true"><?= e($icon) ?></span></div>
        <div class="stat-value" data-count="<?= e($value) ?>" data-format="<?= $currency ? 'currency' : 'number' ?>"><?= e($currency ? money($value) : $value) ?></div>
        <div class="stat-change text-muted"><?= e($note) ?></div>
    </div>
    <?php
}

function finance_totals(): array
{
    $today = date('Y-m-d');
    $income = one('SELECT COALESCE(SUM(amount),0) AS total, COALESCE(SUM(CASE WHEN paid_on >= ? THEN amount ELSE 0 END),0) AS month FROM payments WHERE paid_on<=?', [date('Y-m-01'), $today]);
    $expense = one('SELECT COALESCE(SUM(amount),0) AS total FROM expenses WHERE spent_on<=?', [$today]);
    $balances = one('SELECT COALESCE(SUM(GREATEST(p.total_price-COALESCE(pay.total,0),0)),0) AS outstanding FROM projects p LEFT JOIN (SELECT project_id, SUM(amount) AS total FROM payments WHERE paid_on<=? GROUP BY project_id) pay ON pay.project_id=p.id', [$today]);
    return ['income' => $income['total'], 'month' => $income['month'], 'expense' => $expense['total'], 'balance' => $income['total'] - $expense['total'], 'outstanding' => $balances['outstanding']];
}

function revenue_chart(): void
{
    $start = (new DateTimeImmutable('first day of this month'))->modify('-11 months');
    $income = rows("SELECT DATE_FORMAT(paid_on, '%Y-%m') AS month, SUM(amount) AS total FROM payments WHERE paid_on BETWEEN ? AND ? GROUP BY month", [$start->format('Y-m-d'), date('Y-m-d')]);
    $amounts = array_column($income, 'total', 'month');
    $max = max([1, ...array_values($amounts)]);
    $monthNames = ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];
    ?>
    <div class="chart" role="img" aria-label="Monatliche Einnahmen der letzten zwölf Monate; Einzelwerte stehen in der folgenden Tabelle.">
        <?php for ($i = 0; $i < 12; $i++):
            $month = $start->modify("+$i months");
            $amount = $amounts[$month->format('Y-m')] ?? 0;
            ?>
            <div class="chart-column"><span class="chart-value"><?= e(money($amount)) ?></span><div class="chart-bar" style="--bar-height:<?= (int) round($amount / $max * 100) ?>%" title="<?= e($month->format('m/Y') . ': ' . money($amount)) ?>"></div><span class="chart-label"><?= e($monthNames[(int) $month->format('n') - 1]) ?></span></div>
        <?php endfor; ?>
    </div>
    <details class="mt-3"><summary class="text-muted small">Monatswerte als Tabelle anzeigen</summary>
        <div class="table-responsive"><table class="table table-sm"><caption>Eingegangene Zahlungen je Monat</caption><thead><tr><th>Monat</th><th>Einnahmen</th></tr></thead><tbody>
        <?php for ($i = 0; $i < 12; $i++): $month = $start->modify("+$i months"); ?>
            <tr><td><?= e($month->format('m/Y')) ?></td><td><?= e(money($amounts[$month->format('Y-m')] ?? 0)) ?></td></tr>
        <?php endfor; ?>
        </tbody></table></div>
    </details>
    <?php
}

function render_dashboard(): void
{
    $totals = finance_totals();
    $customers = one('SELECT COUNT(*) AS total FROM customers')['total'];
    $projects = one("SELECT COUNT(*) AS total FROM projects WHERE status='active'")['total'];
    ?>
    <div class="section-heading mb-4 d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div><h2>Dein Business auf einen Blick <span aria-hidden="true">✦</span></h2><p class="page-subtitle">Was heute zählt: deine Kunden, deine Projekte und dein Erfolg.</p></div>
        <a class="btn btn-primary" href="<?= e(url('project_edit')) ?>">＋ Neues Projekt</a>
    </div>
    <div class="stat-grid">
        <?php
        stat_card('Einnahmen diesen Monat', $totals['month'], '↗', 'Tatsächlich eingegangene Zahlungen', true);
        stat_card('Aktive Projekte', $projects, '▤', 'Aufträge mit Status „In Arbeit“');
        stat_card('Deine Kunden', $customers, '♙', 'Kontakte in deinem Workspace');
        stat_card('Offene Beträge', $totals['outstanding'], '◷', 'Projektpreise abzüglich Zahlungen', true);
        ?>
    </div>
    <div class="dashboard-grid">
        <section class="panel-card reveal">
            <div class="card-header"><div><h2 class="card-title">Einnahmenentwicklung</h2><p class="card-subtitle">Die letzten 12 Monate · in EUR</p></div><a class="btn btn-sm btn-outline-secondary" href="<?= e(url('finances')) ?>">Finanzen →</a></div>
            <?php revenue_chart(); ?>
        </section>
        <section class="panel-card reveal">
            <div class="card-header"><div><h2 class="card-title">Deine nächsten Schritte</h2><p class="card-subtitle">Offene Aufgaben &amp; Termine</p></div></div>
            <?php
            $tasks = rows('SELECT t.*, p.title AS project_title FROM tasks t LEFT JOIN projects p ON p.id=t.project_id WHERE t.done=0 ORDER BY t.due_date IS NULL, t.due_date, t.id DESC LIMIT 5');
            if (!$tasks) {
                empty_state('Freie Bahn! Lege deine nächste Aufgabe an.', url('tasks'), 'Zu den Aufgaben');
            }
            ?>
            <div class="activity-list">
                <?php foreach ($tasks as $task): ?>
                    <div class="activity-item"><span class="activity-dot"></span><div class="activity-body"><a href="<?= e(url('tasks')) ?>"><?= e($task['title']) ?></a><p class="text-muted small mb-1"><?= e($task['project_title'] ?? 'Allgemeine Aufgabe') ?></p><span class="activity-time <?= $task['due_date'] && $task['due_date'] < date('Y-m-d') ? 'text-danger' : '' ?>"><?= e(date_de($task['due_date'])) ?></span></div></div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
    <section class="panel-card mt-4 reveal">
        <div class="card-header"><div><h2 class="card-title">Projekte im Fokus</h2><p class="card-subtitle">Offene Aufträge nach Priorität und Termin</p></div><a href="<?= e(url('projects')) ?>" class="btn btn-sm btn-outline-secondary">Alle Projekte →</a></div>
        <?php project_table(project_rows("WHERE p.status <> 'done' ORDER BY r.sort_order, p.due_date IS NULL, p.due_date, p.id DESC LIMIT 6")); ?>
    </section>
    <?php
}

function render_customers(): void
{
    search_toolbar('Kunden durchsuchen …', url('customer_edit'), 'Kunde anlegen');
    $customers = rows('SELECT c.*, COUNT(p.id) AS projects FROM customers c LEFT JOIN projects p ON p.customer_id=c.id GROUP BY c.id ORDER BY c.name');
    ?>
    <section class="panel-card">
        <div class="card-header"><div><h2 class="card-title">Dein Kundenstamm</h2><p class="card-subtitle"><?= count($customers) ?> Kontakte · persönliche Betreuung beginnt hier</p></div></div>
        <?php if (!$customers): empty_state('Speichere deinen ersten Kunden.', url('customer_edit')); else: ?>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Name / Firma</th><th>Kontakt</th><th>Projekte</th><th>Seit</th><th>Details</th></tr></thead><tbody>
        <?php foreach ($customers as $customer): ?>
            <tr data-search-row><td><a class="customer-name" href="<?= e(url('customer', ['id' => $customer['id']])) ?>"><?= e($customer['name']) ?></a><div class="text-muted small"><?= e($customer['company']) ?></div></td><td><?= e($customer['email']) ?><div class="text-muted small"><?= e($customer['phone']) ?></div></td><td><span class="badge text-bg-secondary"><?= (int) $customer['projects'] ?></span></td><td class="text-muted"><?= e(date_de($customer['created_at'])) ?></td><td><a class="btn btn-sm btn-outline-secondary" href="<?= e(url('customer', ['id' => $customer['id']])) ?>">Öffnen →</a></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
        <?php endif; ?>
    </section>
    <?php
}

function render_customer_edit(): void
{
    $customer = isset($_GET['id']) ? require_record('customers', get_id()) : ['id' => 0, 'name' => '', 'company' => '', 'email' => '', 'phone' => '', 'address' => '', 'notes' => ''];
    ?>
    <section class="panel-card">
        <div class="card-header"><div><h2 class="card-title"><?= $customer['id'] ? 'Kundendaten bearbeiten' : 'Neuen Kunden anlegen' ?></h2><p class="card-subtitle">Mit * markierte Felder sind Pflichtfelder.</p></div></div>
        <?php form_start('save_customer', (int) $customer['id']); ?><div class="form-grid">
        <?php
        input('Name', 'name', $customer['name'], 'text', true, 160);
        input('Firma', 'company', $customer['company'], 'text', false, 160);
        input('E-Mail', 'email', $customer['email'], 'email', true);
        input('Telefon', 'phone', $customer['phone'], 'text', false, 60);
        textarea('Adresse', 'address', $customer['address'], 4000);
        textarea('Interne Notizen', 'notes', $customer['notes']);
        save_button(url('customers'));
        ?>
    </section>
    <?php
}

function render_customer(): void
{
    $customer = require_record('customers', get_id());
    $projects = project_rows('WHERE p.customer_id=? ORDER BY p.id DESC', [$customer['id']]);
    ?>
    <div class="toolbar mb-4"><a class="btn btn-outline-secondary" href="<?= e(url('customers')) ?>">← Alle Kunden</a><div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="<?= e(url('customer_edit', ['id' => $customer['id']])) ?>">Bearbeiten</a><a class="btn btn-primary" href="<?= e(url('project_edit', ['customer_id' => $customer['id']])) ?>">＋ Projekt anlegen</a></div></div>
    <div class="detail-grid">
        <section class="panel-card"><div class="card-header"><h2 class="card-title"><?= e($customer['name']) ?></h2><span class="badge text-bg-secondary">Kunde #<?= (int) $customer['id'] ?></span></div>
            <dl><dt>Firma</dt><dd><?= e($customer['company'] ?: '–') ?></dd><dt>E-Mail</dt><dd><?= e($customer['email']) ?></dd><dt>Telefon</dt><dd><?= e($customer['phone'] ?: '–') ?></dd><dt>Adresse</dt><dd class="note-text"><?= e($customer['address'] ?: '–') ?></dd></dl>
        </section>
        <section class="panel-card"><div class="card-header"><h2 class="card-title">Interne Notizen</h2></div><p class="note-text"><?= e($customer['notes'] ?: 'Noch keine Notizen hinterlegt.') ?></p><p class="text-muted small">Kunde seit <?= e(date_de($customer['created_at'])) ?></p></section>
    </div>
    <section class="panel-card mt-4"><div class="card-header"><h2 class="card-title">Projekte von <?= e($customer['name']) ?></h2></div><?php project_table($projects); ?></section>
    <div class="mt-4"><?php delete_button('delete_customer', (int) $customer['id'], 'Kunde löschen'); ?><p class="text-muted small mt-2">Nur möglich, wenn keine Projekte mehr zugeordnet sind.</p></div>
    <?php
}

function render_projects(): void
{
    search_toolbar('Projekt, Kunde oder Status suchen …', url('project_edit'), 'Projekt anlegen');
    echo '<section class="panel-card"><div class="card-header"><div><h2 class="card-title">Deine Projekte</h2><p class="card-subtitle">Vom ersten Auftrag bis zum erfolgreichen Abschluss</p></div></div>';
    project_table(project_rows('ORDER BY p.status, r.sort_order, p.due_date IS NULL, p.due_date, p.id DESC'));
    echo '</section>';
}

function render_project_edit(): void
{
    $customers = rows('SELECT id, name FROM customers ORDER BY name');
    if (!$customers) {
        empty_state('Bitte zuerst einen Kunden anlegen, bevor du ein Projekt erstellst.', url('customer_edit'), 'Kunde anlegen');
        return;
    }
    $customerId = filter_var($_GET['customer_id'] ?? 0, FILTER_VALIDATE_INT) ?: $customers[0]['id'];
    $project = isset($_GET['id']) ? require_record('projects', get_id()) : [
        'id' => 0, 'title' => '', 'customer_id' => $customerId, 'priority_id' => 2,
        'description' => '', 'status' => 'active', 'progress' => 0, 'total_price' => '0.00', 'due_date' => '',
    ];
    ?>
    <section class="panel-card"><div class="card-header"><div><h2 class="card-title"><?= $project['id'] ? 'Projekt bearbeiten' : 'Neues Projekt' ?></h2><p class="card-subtitle">Definiere Auftrag, Priorität, Fortschritt und vereinbartes Budget.</p></div></div>
        <?php form_start('save_project', (int) $project['id']); ?><div class="form-grid">
        <?php
        input('Projekttitel / geforderter Auftrag', 'title', $project['title'], 'text', true);
        select_field('Kunde', 'customer_id', array_column($customers, 'name', 'id'), $project['customer_id']);
        select_field('Priorität', 'priority_id', array_column(rows('SELECT * FROM priorities ORDER BY sort_order'), 'name', 'id'), $project['priority_id']);
        select_field('Status', 'status', ['active' => 'In Arbeit', 'paused' => 'Pausiert', 'done' => 'Abgeschlossen'], $project['status']);
        input('Vereinbarter Gesamtpreis (EUR)', 'total_price', $project['total_price'], 'number', true);
        input('Fertigstellung bis', 'due_date', $project['due_date'], 'date');
        ?>
        <?php $progress = max(0, min(100, (int) ($_POST['progress'] ?? $project['progress']))); ?>
        <div class="form-field field-full"><label class="form-label" for="field-progress">Fortschritt: <output id="progress-value" for="field-progress" data-progress-output><?= $progress ?> %</output></label><input class="form-range" type="range" name="progress" id="field-progress" min="0" max="100" value="<?= $progress ?>" data-progress-input aria-describedby="progress-help"><p id="progress-help" class="text-muted small">Abgeschlossene Projekte werden automatisch auf 100 % gesetzt.</p></div>
        <?php textarea('Auftragsbeschreibung', 'description', $project['description']); save_button(url('projects')); ?>
    </section>
    <?php
}

function render_project(): void
{
    $projects = project_rows('WHERE p.id=?', [get_id()]);
    if (!$projects) {
        throw new InvalidArgumentException('Projekt nicht gefunden.');
    }
    $p = $projects[0];
    $payments = rows('SELECT * FROM payments WHERE project_id=? ORDER BY paid_on DESC, id DESC', [$p['id']]);
    $items = rows('SELECT *, quantity * unit_price AS subtotal FROM project_items WHERE project_id=? ORDER BY id', [$p['id']]);
    $products = rows('SELECT id, name, price, unit FROM products ORDER BY name');
    ?>
    <div class="toolbar mb-4"><a class="btn btn-outline-secondary" href="<?= e(url('projects')) ?>">← Alle Projekte</a><a class="btn btn-primary" href="<?= e(url('project_edit', ['id' => $p['id']])) ?>">Projekt bearbeiten</a></div>
    <div class="stat-grid">
        <?php
        stat_card('Vereinbarter Preis', $p['total_price'], '◇', 'Manuell festgelegter Gesamtpreis', true);
        stat_card('Eingezahlt', $p['paid'], '↗', 'Zahlungen bis einschließlich heute', true);
        stat_card('Noch offen', max(0, $p['total_price'] - $p['paid']), '◷', 'Unbezahlter Restbetrag', true);
        stat_card('Guthaben / Überzahlung', max(0, $p['paid'] - $p['total_price']), '＋', 'Über den Gesamtpreis hinaus bezahlt', true);
        ?>
    </div>
    <div class="detail-grid">
        <section class="panel-card">
            <div class="card-header"><h2 class="card-title"><?= e($p['title']) ?></h2><span class="badge priority-<?= e($p['priority_color']) ?>"><?= e($p['priority_name']) ?></span></div>
            <a href="<?= e(url('customer', ['id' => $p['customer_id']])) ?>" class="customer-name"><?= e($p['customer_name']) ?></a>
            <p class="note-text mt-3"><?= e($p['description'] ?: 'Noch keine Auftragsbeschreibung.') ?></p>
            <div class="progress-meta"><span class="badge status-<?= e($p['status']) ?>"><?= e(status_label($p['status'])) ?></span><strong><?= (int) $p['progress'] ?> %</strong></div>
            <div class="progress mt-2" role="progressbar" aria-label="Projektfortschritt" aria-valuenow="<?= (int) $p['progress'] ?>" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width:<?= (int) $p['progress'] ?>%"></div></div>
            <p class="text-muted small mt-3">Termin: <?= e(date_de($p['due_date'])) ?> · Aktualisiert: <?= e(date_de($p['updated_at'])) ?></p>
        </section>
        <section class="panel-card"><div class="card-header"><div><h2 class="card-title">Abschlag erfassen</h2><p class="card-subtitle">Teilzahlungen und Schlusszahlungen dokumentieren</p></div></div>
            <?php form_start('add_payment'); ?><input type="hidden" name="project_id" value="<?= (int) $p['id'] ?>"><div class="form-grid">
            <?php input('Betrag (EUR)', 'amount', '', 'number', true); input('Zahlungsdatum', 'paid_on', date('Y-m-d'), 'date', true); input('Verwendungszweck / Notiz', 'note', '', 'text', false, 255); ?>
            <div class="form-actions field-full"><button type="submit" class="btn btn-primary">Zahlung speichern</button></div></div></form>
        </section>
    </div>
    <section class="panel-card mt-4"><div class="card-header"><h2 class="card-title">Zahlungshistorie</h2><span class="text-muted small"><?= count($payments) ?> Zahlungen</span></div>
        <?php if (!$payments): empty_state('Für dieses Projekt wurden noch keine Zahlungen erfasst.'); else: ?>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Datum</th><th>Verwendungszweck</th><th>Betrag</th><th>Aktion</th></tr></thead><tbody>
        <?php foreach ($payments as $payment): ?>
            <tr><td><?= e(date_de($payment['paid_on'])) ?><?= $payment['paid_on'] > date('Y-m-d') ? ' · zukünftig' : '' ?></td><td><?= e($payment['note'] ?: 'Abschlagszahlung') ?></td><td class="amount-positive"><?= e(money($payment['amount'])) ?></td><td><?php delete_button('delete_payment', (int) $payment['id']); ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div><?php endif; ?>
    </section>
    <section class="panel-card mt-4"><div class="card-header"><div><h2 class="card-title">Leistungen &amp; Kalkulation</h2><p class="card-subtitle">Preis-Snapshots · ändern den vereinbarten Gesamtpreis nicht automatisch</p></div></div>
        <?php if ($items): ?>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Leistung</th><th>Menge</th><th>Einzelpreis</th><th>Summe</th><th>Aktion</th></tr></thead><tbody>
        <?php foreach ($items as $item): ?>
            <tr><td><?= e($item['name']) ?></td><td><?= e($item['quantity'] . ' ' . $item['unit']) ?></td><td><?= e(money($item['unit_price'])) ?></td><td><?= e(money($item['subtotal'])) ?></td><td><?php delete_button('delete_item', (int) $item['id']); ?></td></tr>
        <?php endforeach; ?>
        </tbody><tfoot><tr><th colspan="3">Kalkulationssumme</th><td colspan="2"><?= e(money(array_sum(array_column($items, 'subtotal')))) ?></td></tr></tfoot></table></div>
        <?php endif; ?>
        <?php if ($products): ?>
            <?php form_start('add_item'); ?><input type="hidden" name="project_id" value="<?= (int) $p['id'] ?>"><div class="form-grid">
            <?php
            $options = [];
            foreach ($products as $product) {
                $options[$product['id']] = $product['name'] . ' · ' . money($product['price']) . ' / ' . $product['unit'];
            }
            select_field('Produkt / Dienstleistung', 'product_id', $options, '');
            input('Menge (max. 99.999.999,99)', 'quantity', '1', 'number', true);
            ?>
            <div class="form-actions field-full"><button type="submit" class="btn btn-outline-secondary">＋ Position übernehmen</button></div></div></form>
        <?php else: ?>
            <p class="text-muted">Noch keine Produkte vorhanden. <a href="<?= e(url('product_edit')) ?>">Produkt anlegen →</a></p>
        <?php endif; ?>
    </section>
    <div class="mt-4"><?php delete_button('delete_project', (int) $p['id'], 'Projekt löschen'); ?><p class="text-muted small mt-2">Projekte mit Zahlungen sind vor dem Löschen geschützt.</p></div>
    <?php
}

function render_products(): void
{
    search_toolbar('Produkte und Leistungen suchen …', url('product_edit'), 'Produkt anlegen');
    $products = rows('SELECT * FROM products ORDER BY category, name');
    if (!$products) {
        empty_state('Erstelle deinen persönlichen Produkt- und Leistungskatalog.', url('product_edit'));
        return;
    }
    ?>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
        <article class="panel-card product-card reveal" data-search-row>
            <div class="d-flex justify-content-between align-items-center mb-3"><span class="stat-icon" aria-hidden="true">◇</span><span class="badge text-bg-secondary"><?= e($product['category'] ?: 'Allgemein') ?></span></div>
            <h2 class="card-title"><?= e($product['name']) ?></h2><p class="text-muted note-text mt-2"><?= e($product['description'] ?: 'Keine Beschreibung hinterlegt.') ?></p>
            <div class="price"><?= e(money($product['price'])) ?> <span class="text-muted small">/ <?= e($product['unit']) ?></span></div>
            <div class="d-flex gap-2 mt-4"><a class="btn btn-outline-secondary btn-sm" href="<?= e(url('product_edit', ['id' => $product['id']])) ?>">Bearbeiten</a><?php delete_button('delete_product', (int) $product['id']); ?></div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php
}

function render_product_edit(): void
{
    $product = isset($_GET['id']) ? require_record('products', get_id()) : ['id' => 0, 'name' => '', 'category' => '', 'description' => '', 'unit' => 'Stück', 'price' => '0.00'];
    ?>
    <section class="panel-card"><div class="card-header"><div><h2 class="card-title"><?= $product['id'] ? 'Produkt bearbeiten' : 'Produkt / Dienstleistung anlegen' ?></h2><p class="card-subtitle">Dein Katalog für Hardware, Support und individuelle IT-Leistungen.</p></div></div>
        <?php form_start('save_product', (int) $product['id']); ?><div class="form-grid">
        <?php
        input('Bezeichnung', 'name', $product['name'], 'text', true, 160);
        input('Kategorie', 'category', $product['category'], 'text', false, 80);
        input('Einheit (z.B. Stunde, Stück, Pauschale)', 'unit', $product['unit'], 'text', true, 40);
        input('Preis pro Einheit (EUR)', 'price', $product['price'], 'number', true);
        textarea('Beschreibung', 'description', $product['description'], 5000);
        save_button(url('products'));
        ?>
    </section>
    <?php
}

function render_tasks(): void
{
    $tasks = rows('SELECT t.*, p.title AS project_title FROM tasks t LEFT JOIN projects p ON p.id=t.project_id ORDER BY t.done, t.due_date IS NULL, t.due_date, t.id DESC');
    ?>
    <div class="detail-grid">
        <section class="panel-card"><div class="card-header"><div><h2 class="card-title">Deine Aufgaben</h2><p class="card-subtitle">Kleine Schritte. Große Fortschritte.</p></div></div>
            <input class="form-control mb-3" data-search type="search" placeholder="Aufgaben suchen …" aria-label="Aufgaben suchen">
            <?php if (!$tasks): empty_state('Lege deine erste Aufgabe an.'); endif; ?>
            <?php foreach ($tasks as $task): ?>
                <div class="list-item d-flex gap-3 justify-content-between align-items-start" data-search-row>
                    <div class="d-flex gap-3 align-items-start">
                        <?php form_start('toggle_task', (int) $task['id']); ?><button type="submit" class="btn btn-sm <?= $task['done'] ? 'btn-primary' : 'btn-outline-secondary' ?>" aria-label="<?= e(($task['done'] ? 'Wieder öffnen: ' : 'Erledigen: ') . $task['title']) ?>"><?= $task['done'] ? '✓' : '○' ?></button></form>
                        <div><strong class="<?= $task['done'] ? 'text-decoration-line-through text-muted' : '' ?>"><?= e($task['title']) ?></strong>
                            <div class="text-muted small"><?= e($task['project_title'] ?? 'Allgemein') ?></div><span class="small <?= !$task['done'] && $task['due_date'] && $task['due_date'] < date('Y-m-d') ? 'text-danger' : 'text-muted' ?>"><?= e(date_de($task['due_date'])) ?></span>
                        </div>
                    </div>
                    <?php delete_button('delete_task', (int) $task['id'], '×'); ?>
                </div>
            <?php endforeach; ?>
        </section>
        <section class="panel-card"><div class="card-header"><h2 class="card-title">Neue Aufgabe</h2></div>
            <?php form_start('save_task'); ?><div class="form-grid">
            <?php
            input('Was steht an?', 'title', '', 'text', true);
            input('Fällig am', 'due_date', '', 'date');
            select_field('Projekt (optional)', 'project_id', [0 => 'Allgemeine Aufgabe'] + array_column(rows('SELECT id, title FROM projects ORDER BY title'), 'title', 'id'), 0, false);
            ?>
            <div class="form-actions field-full"><button type="submit" class="btn btn-primary">＋ Aufgabe anlegen</button></div></div></form>
        </section>
    </div>
    <?php
}

function render_finances(): void
{
    $totals = finance_totals();
    ?>
    <div class="toolbar mb-4"><p class="text-muted mb-0">Ist-Zahlungen bis heute · alle Beträge in EUR</p><a class="btn btn-outline-secondary" href="<?= e(url('export')) ?>">↓ Zahlungen als CSV</a></div>
    <div class="stat-grid">
        <?php
        stat_card('Einnahmen gesamt', $totals['income'], '↗', 'Alle eingegangenen Zahlungen', true);
        stat_card('Ausgaben gesamt', $totals['expense'], '↙', 'Alle erfassten Betriebsausgaben', true);
        stat_card('Saldo', $totals['balance'], '◇', 'Einnahmen minus Ausgaben, kein Steuergewinn', true);
        stat_card('Offene Beträge', $totals['outstanding'], '◷', 'Ausstehende Projektzahlungen', true);
        ?>
    </div>
    <section class="panel-card mb-4"><div class="card-header"><div><h2 class="card-title">Einnahmenentwicklung</h2><p class="card-subtitle">Zahlungseingänge der letzten zwölf Monate</p></div></div><?php revenue_chart(); ?></section>
    <div class="detail-grid">
        <section class="panel-card"><div class="card-header"><h2 class="card-title">Letzte Zahlungseingänge</h2></div>
        <?php $payments = rows('SELECT pay.*, p.title, c.name FROM payments pay JOIN projects p ON p.id=pay.project_id JOIN customers c ON c.id=p.customer_id ORDER BY pay.paid_on DESC, pay.id DESC LIMIT 20'); ?>
        <?php if (!$payments): empty_state('Erfasse Zahlungen direkt beim jeweiligen Projekt.'); endif; ?>
        <?php foreach ($payments as $payment): ?>
            <div class="list-item d-flex justify-content-between gap-3"><div><a href="<?= e(url('project', ['id' => $payment['project_id']])) ?>"><?= e($payment['title']) ?></a><div class="text-muted small"><?= e($payment['name']) ?> · <?= e(date_de($payment['paid_on'])) ?><?= $payment['paid_on'] > date('Y-m-d') ? ' · zukünftig' : '' ?></div></div><strong class="amount-positive"><?= e(money($payment['amount'])) ?></strong></div>
        <?php endforeach; ?>
        </section>
        <section class="panel-card"><div class="card-header"><h2 class="card-title">Ausgabe erfassen</h2></div>
            <?php form_start('save_expense'); ?><div class="form-grid">
            <?php input('Bezeichnung', 'title', '', 'text', true); input('Kategorie', 'category', '', 'text', false, 80); input('Betrag (EUR)', 'amount', '', 'number', true); input('Datum', 'spent_on', date('Y-m-d'), 'date', true); ?>
            <div class="form-actions field-full"><button type="submit" class="btn btn-primary">Ausgabe speichern</button></div></div></form>
        </section>
    </div>
    <section class="panel-card mt-4"><div class="card-header"><h2 class="card-title">Ausgabenjournal</h2></div>
        <?php $expenses = rows('SELECT * FROM expenses ORDER BY spent_on DESC, id DESC'); ?>
        <?php if (!$expenses): empty_state('Noch keine Betriebsausgaben erfasst.'); else: ?>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Datum</th><th>Bezeichnung</th><th>Kategorie</th><th>Betrag</th><th>Aktion</th></tr></thead><tbody>
        <?php foreach ($expenses as $expense): ?>
            <tr><td><?= e(date_de($expense['spent_on'])) ?><?= $expense['spent_on'] > date('Y-m-d') ? ' · zukünftig' : '' ?></td><td><?= e($expense['title']) ?></td><td><?= e($expense['category']) ?></td><td class="amount-negative"><?= e(money($expense['amount'])) ?></td><td><?php delete_button('delete_expense', (int) $expense['id']); ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div><?php endif; ?>
    </section>
    <p class="text-muted small mt-4">Manuelle Übersicht, keine Buchhaltungs- oder Rechnungslösung. Keine automatische Umsatzsteuerberechnung. Zukünftige Buchungen zählen erst ab ihrem Datum zu den Ist-Werten.</p>
    <?php
}

function render_settings(): void
{
    $priorities = rows('SELECT * FROM priorities ORDER BY sort_order');
    $admin = one('SELECT * FROM admins WHERE id=1');
    ?>
    <div class="detail-grid">
        <section class="panel-card"><div class="card-header"><div><h2 class="card-title">Deine drei Prioritäten</h2><p class="card-subtitle">Bezeichnungen frei festlegen – die Reihenfolge bleibt hoch → niedrig.</p></div></div>
            <?php form_start('save_priorities'); ?><div class="form-grid">
            <?php foreach ($priorities as $index => $priority): input(['Höchste Priorität', 'Mittlere Priorität', 'Niedrigste Priorität'][$index], 'priority_' . $priority['id'], $priority['name'], 'text', true, 60); endforeach; ?>
            <div class="form-actions field-full"><button type="submit" class="btn btn-primary">Prioritäten speichern</button></div></div></form>
        </section>
        <section class="panel-card"><div class="card-header"><div><h2 class="card-title">Admin-Profil &amp; Sicherheit</h2><p class="card-subtitle">Änderungen mit dem aktuellen Passwort bestätigen.</p></div></div>
            <?php form_start('save_account'); ?><div class="form-grid">
            <?php
            input('Name', 'name', $admin['name'], 'text', true, 120);
            input('E-Mail', 'email', $admin['email'], 'email', true);
            input('Aktuelles Passwort', 'current_password', '', 'password', true, 128);
            input('Neues Passwort (optional, mindestens 12 Zeichen)', 'new_password', '', 'password', false, 128);
            input('Neues Passwort wiederholen', 'password_confirm', '', 'password', false, 128);
            ?>
            <div class="form-actions field-full"><button type="submit" class="btn btn-primary">Profil speichern</button></div></div></form>
        </section>
    </div>
    <section class="panel-card mt-4"><div class="card-header"><h2 class="card-title">Dein geschützter Workspace</h2></div><p class="text-muted">Nur ein Admin-Zugang, keine öffentliche Kundenregistrierung. Sitzungen enden nach 30 Minuten Inaktivität. Für den produktiven Betrieb HTTPS und regelmäßige Datenbank-Backups verwenden.</p><p class="text-muted mb-0">Alle Daten bleiben in deiner MySQL-Datenbank. Das Panel lädt keine externen Schriftarten, Skripte oder CDN-Ressourcen.</p></section>
    <?php
}

function export_payments(): never
{
    if (!authenticated()) {
        redirect('login');
    }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="zahlungen-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'wb');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Datum', 'Kunde', 'Projekt', 'Betrag EUR', 'Verwendungszweck'], ';', '"', '');
    $stmt = query('SELECT pay.paid_on, c.name, p.title, pay.amount, pay.note FROM payments pay JOIN projects p ON p.id=pay.project_id JOIN customers c ON c.id=p.customer_id ORDER BY pay.paid_on DESC, pay.id DESC');
    while ($row = $stmt->fetch()) {
        $cells = array_values($row);
        $cells[3] = number_format((float) $cells[3], 2, ',', '');
        foreach ($cells as &$cell) {
            if (preg_match('/^[\s]*[=+@\-\t\r\n]/u', $cell)) {
                $cell = "'" . $cell;
            }
        }
        unset($cell);
        fputcsv($out, $cells, ';', '"', '');
    }
    fclose($out);
    exit;
}
