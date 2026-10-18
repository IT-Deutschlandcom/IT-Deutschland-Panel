<?php
declare(strict_types=1);

return [
    'name' => 'IT-Deutschland',
    'timezone' => 'Europe/Berlin',
    'database' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'name' => getenv('DB_NAME') ?: 'it_panel',
        'user' => getenv('DB_USER') ?: 'it_panel',
        'password' => getenv('DB_PASSWORD') ?: '',
    ],
    // Nur für die erste Registrierung: mindestens 32 zufällige Zeichen eintragen.
    // Nach der Registrierung kann der Schlüssel wieder entfernt werden.
    'registration_key' => getenv('PANEL_REGISTRATION_KEY') ?: '',
    'currency' => 'EUR',
];
