# Mail Marketing CMS – Implementierungsstand

Dieses Repository startet als PHP-8.2-/MySQL-Grundlage für den Betrieb unter IIS. Die derzeitige Lieferung umfasst die Konfiguration, PDO-Verbindung, sichere Installationsgrundlage und das Datenmodell für Administratoren, Listen, Kontakte, dokumentierte Versandgrundlagen und eine zentrale Sperrliste. Es ist **noch kein vollständiges Marketing-CMS**; Versand, Login, Kampagnen, SMTP-Verwaltung, Worker und weitere Module sind noch nicht implementiert und dürfen nicht als verfügbar betrachtet werden.

## Voraussetzungen

- Windows Server mit IIS und PHP 8.2 oder neuer (FastCGI)
- MySQL 8 oder MariaDB mit InnoDB
- PHP-Erweiterungen `pdo_mysql` und `openssl`

## Erste Einrichtung

1. Eine leere MySQL-Datenbank erstellen und `/database.sql` importieren.
2. `/config.php` bearbeiten und Datenbankzugangsdaten eintragen. Keine Zugangsdaten in öffentlichen Webroot- oder Versionskontrollkopien verwenden.
3. IIS so konfigurieren, dass PHP-Dateien über FastCGI ausgeführt werden und `web.config` wirksam ist. Das IIS-Anwendungspoolkonto benötigt nur die für die Anwendung erforderlichen Dateirechte.
4. `https://<host>/install.php` aufrufen und ein Administratorpasswort mit mindestens 12 Zeichen setzen.
5. Nach Einrichtung den Zugriff auf `install.php` in IIS sperren oder die Datei entfernen. Diese Basis enthält noch keine Anmeldeseite.

Die Installationsseite ist einmalig: Sobald mindestens ein Administrator existiert, legt sie keine weiteren Konten an. `config.php`, `database.sql` sowie interne Verzeichnisse werden durch IIS-Regeln gesperrt. IIS muss die Anwendung außerdem mit einem sicheren HTTPS-Binding ausliefern.

## Versandfreigabe

Neue Kontakte haben standardmäßig den Status `pending_consent`. Eine Einwilligung oder sonstige zulässige Versandgrundlage ist in `contact_consents` mit Quelle und Zeitpunkt zu dokumentieren. Import oder Anwendungslogik darf Kontakte nicht ohne gültigen Nachweis aktivieren; Adressen in `suppression_list` müssen unabhängig von Listenmitgliedschaften ausgeschlossen bleiben.

## Aktueller Umfang und nächste Schritte

- [x] Basiskonfiguration und PDO-Verbindung
- [x] Grundschema für Rollen, Administratoren, Auditdaten, Listen, Kontakte, Consent und Suppression
- [x] Einmalige Erstanlage eines Administrators mit CSRF-Schutz und Passwort-Hashing
- [ ] Anmeldung, Logout, Rollenprüfung und Rate-Limiting
- [ ] Kontaktimport/-verwaltung mit serverseitiger Consent-Prüfung
- [ ] Kampagnen, Versandwarteschlange und Windows-Worker
- [ ] SMTP, Abmeldung, Berichte und verbleibende Admin-Module

Ein echter SMTP-Versand ist in diesem Implementierungsstand nicht vorhanden.
