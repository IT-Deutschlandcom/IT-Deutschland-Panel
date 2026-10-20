<?php
declare(strict_types=1);

defined('PANEL_RUNNING') || exit;

function handle_action(string $action): void
{
    global $config;
    verify_csrf();
    if ($action === 'login' || $action === 'register') {
        throttle_auth();
        $email = email_input();
        $password = secret_input('password', true, 72);
        if ($action === 'register') {
            $key = $config['registration_key'];
            if (strlen($key) < 32 || !hash_equals($key, secret_input('registration_key', true, 256))) {
                throw new InvalidArgumentException('Die Registrierung ist gesperrt oder der Einrichtungsschlüssel ist falsch.');
            }
            if (one('SELECT id FROM admins LIMIT 1')) {
                throw new InvalidArgumentException('Ein Administrator existiert bereits. Weitere Registrierungen sind deaktiviert.');
            }
            if (strlen($password) < 12 || strlen($password) > 72) {
                throw new InvalidArgumentException('Das Passwort muss 12 bis 72 Bytes lang sein.');
            }
            if (!hash_equals($password, secret_input('password_confirm'))) {
                throw new InvalidArgumentException('Die Passwörter stimmen nicht überein.');
            }
            query('INSERT INTO admins (id, name, email, password_hash) VALUES (1, ?, ?, ?)', [
                text('name', 120), $email, password_hash($password, PASSWORD_DEFAULT),
            ]);
            flash('Administrator angelegt. Bitte jetzt anmelden.');
            redirect('login');
        }
        $admin = one('SELECT * FROM admins WHERE email = ?', [$email]);
        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            throw new InvalidArgumentException('E-Mail-Adresse oder Passwort ist falsch.');
        }
        if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
            query('UPDATE admins SET password_hash = ? WHERE id = 1', [password_hash($password, PASSWORD_DEFAULT)]);
        }
        session_regenerate_id(true);
        $_SESSION['admin_id'] = 1;
        $_SESSION['last_activity'] = time();
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        query('DELETE FROM auth_throttles WHERE bucket = ?', [hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'))]);
        redirect('dashboard');
    }

    if (!authenticated()) {
        throw new InvalidArgumentException('Bitte zuerst anmelden.');
    }
    switch ($action) {
        case 'logout':
            $_SESSION = [];
            session_regenerate_id(true);
            redirect('login');
        case 'save_customer':
            $values = [
                text('name', 160), text('company', 160, false), email_input(),
                text('phone', 60, false), text('address', 4000, false), text('notes', 10000, false),
            ];
            $id = integer('id', 0);
            if ($id) {
                require_record('customers', $id);
                query('UPDATE customers SET name=?, company=?, email=?, phone=?, address=?, notes=? WHERE id=?', [...$values, $id]);
            } else {
                query('INSERT INTO customers (name, company, email, phone, address, notes) VALUES (?, ?, ?, ?, ?, ?)', $values);
                $id = (int) db()->lastInsertId();
            }
            flash('Kundendaten gespeichert.');
            redirect('customer', ['id' => $id]);
        case 'delete_customer':
            $id = integer('id');
            require_record('customers', $id);
            if (one('SELECT id FROM projects WHERE customer_id=? LIMIT 1', [$id])) {
                throw new InvalidArgumentException('Kunden mit Projekten können nicht gelöscht werden. Bitte zuerst die Projekte entfernen.');
            }
            query('DELETE FROM customers WHERE id=?', [$id]);
            flash('Kunde gelöscht.');
            redirect('customers');
        case 'save_project':
            $id = integer('id', 0);
            $customer = integer('customer_id');
            require_record('customers', $customer);
            $priority = integer('priority_id', 1, 3);
            $status = text('status', 20);
            if (!in_array($status, ['active', 'paused', 'done'], true)) {
                throw new InvalidArgumentException('Ungültiger Projektstatus.');
            }
            $progress = integer('progress', 0, 100);
            $values = [
                $customer, $priority, text('title'), text('description', 10000, false), $status,
                $status === 'done' ? 100 : $progress, decimal('total_price'), date_input('due_date'),
            ];
            if ($id) {
                require_record('projects', $id);
                query('UPDATE projects SET customer_id=?, priority_id=?, title=?, description=?, status=?, progress=?, total_price=?, due_date=? WHERE id=?', [...$values, $id]);
            } else {
                query('INSERT INTO projects (customer_id, priority_id, title, description, status, progress, total_price, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', $values);
                $id = (int) db()->lastInsertId();
            }
            flash('Projekt gespeichert.');
            redirect('project', ['id' => $id]);
        case 'delete_project':
            $id = integer('id');
            require_record('projects', $id);
            db()->beginTransaction();
            try {
                // Auch zeitgleich erfasste Zahlungen müssen vor der Löschprüfung sichtbar sein.
                query('SELECT id FROM projects WHERE id=? FOR UPDATE', [$id]);
                if (one('SELECT id FROM payments WHERE project_id=? LIMIT 1', [$id])) {
                    throw new InvalidArgumentException('Projekte mit Zahlungen sind vor dem Löschen geschützt. Zahlungen zuerst einzeln entfernen.');
                }
                query('DELETE FROM project_items WHERE project_id=?', [$id]);
                query('UPDATE tasks SET project_id=NULL WHERE project_id=?', [$id]);
                query('DELETE FROM projects WHERE id=?', [$id]);
                db()->commit();
            } catch (Throwable $error) {
                db()->rollBack();
                throw $error;
            }
            flash('Projekt gelöscht; zugehörige Aufgaben bleiben erhalten.');
            redirect('projects');
        case 'add_payment':
            $project = integer('project_id');
            require_record('projects', $project);
            query('INSERT INTO payments (project_id, amount, paid_on, note) VALUES (?, ?, ?, ?)', [
                $project, decimal('amount', true), date_input('paid_on', true), text('note', 255, false),
            ]);
            flash('Abschlagszahlung erfasst.');
            redirect('project', ['id' => $project]);
        case 'delete_payment':
            $payment = require_record('payments', integer('id'));
            query('DELETE FROM payments WHERE id=?', [$payment['id']]);
            flash('Zahlung entfernt.');
            redirect('project', ['id' => $payment['project_id']]);
        case 'save_product':
            $id = integer('id', 0);
            $values = [text('name', 160), text('category', 80, false), text('description', 5000, false), text('unit', 40), decimal('price')];
            if ($id) {
                require_record('products', $id);
                query('UPDATE products SET name=?, category=?, description=?, unit=?, price=? WHERE id=?', [...$values, $id]);
            } else {
                query('INSERT INTO products (name, category, description, unit, price) VALUES (?, ?, ?, ?, ?)', $values);
            }
            flash('Produkt gespeichert. Bereits übernommene Projektpositionen bleiben unverändert.');
            redirect('products');
        case 'delete_product':
            query('DELETE FROM products WHERE id=?', [integer('id')]);
            flash('Produkt gelöscht. Projektpositionen bleiben erhalten.');
            redirect('products');
        case 'add_item':
            $project = integer('project_id');
            require_record('projects', $project);
            $product = require_record('products', integer('product_id'));
            query('INSERT INTO project_items (project_id, name, unit, quantity, unit_price) VALUES (?, ?, ?, ?, ?)', [
                $project, $product['name'], $product['unit'], decimal('quantity', true, 8), $product['price'],
            ]);
            flash('Produkt als Position übernommen. Der vereinbarte Gesamtpreis bleibt unverändert.');
            redirect('project', ['id' => $project]);
        case 'delete_item':
            $item = require_record('project_items', integer('id'));
            query('DELETE FROM project_items WHERE id=?', [$item['id']]);
            flash('Position entfernt. Der Gesamtpreis bleibt unverändert.');
            redirect('project', ['id' => $item['project_id']]);
        case 'save_task':
            $project = integer('project_id', 0);
            if ($project) {
                require_record('projects', $project);
            }
            query('INSERT INTO tasks (project_id, title, due_date) VALUES (?, ?, ?)', [$project ?: null, text('title'), date_input('due_date')]);
            flash('Aufgabe erstellt.');
            redirect('tasks');
        case 'toggle_task':
            query('UPDATE tasks SET done = 1 - done WHERE id=?', [integer('id')]);
            redirect('tasks');
        case 'delete_task':
            query('DELETE FROM tasks WHERE id=?', [integer('id')]);
            flash('Aufgabe gelöscht.');
            redirect('tasks');
        case 'save_expense':
            query('INSERT INTO expenses (title, amount, spent_on, category) VALUES (?, ?, ?, ?)', [
                text('title'), decimal('amount', true), date_input('spent_on', true), text('category', 80, false),
            ]);
            flash('Ausgabe erfasst.');
            redirect('finances');
        case 'delete_expense':
            query('DELETE FROM expenses WHERE id=?', [integer('id')]);
            flash('Ausgabe entfernt.');
            redirect('finances');
        case 'save_priorities':
            $names = [text('priority_1', 60), text('priority_2', 60), text('priority_3', 60)];
            if (count(array_unique(array_map('mb_strtolower', $names))) !== 3) {
                throw new InvalidArgumentException('Bitte drei unterschiedliche Prioritätsnamen verwenden.');
            }
            db()->beginTransaction();
            try {
                foreach ($names as $index => $name) {
                    query('UPDATE priorities SET name=? WHERE id=?', [$name, $index + 1]);
                }
                db()->commit();
            } catch (Throwable $error) {
                db()->rollBack();
                throw $error;
            }
            flash('Die drei Prioritäten wurden aktualisiert.');
            redirect('settings');
        case 'save_account':
            throttle_auth();
            $admin = one('SELECT * FROM admins WHERE id=1');
            if (!password_verify(secret_input('current_password', true, 72), $admin['password_hash'])) {
                throw new InvalidArgumentException('Das bisherige Passwort ist falsch.');
            }
            $name = text('name', 120);
            $email = email_input();
            $newPassword = secret_input('new_password', false, 72);
            $hash = $admin['password_hash'];
            if ($newPassword !== '') {
                if (strlen($newPassword) < 12 || strlen($newPassword) > 72) {
                    throw new InvalidArgumentException('Das neue Passwort muss 12 bis 72 Bytes lang sein.');
                }
                if (!hash_equals($newPassword, secret_input('password_confirm'))) {
                    throw new InvalidArgumentException('Die neuen Passwörter stimmen nicht überein.');
                }
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            }
            query('UPDATE admins SET name=?, email=?, password_hash=? WHERE id=1', [$name, $email, $hash]);
            session_regenerate_id(true);
            flash('Admin-Profil gespeichert.');
            redirect('settings');
        default:
            throw new InvalidArgumentException('Unbekannte Aktion.');
    }
}
