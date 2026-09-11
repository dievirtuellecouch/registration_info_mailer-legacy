# Registration Info Mailer für Contao 5.7

`dvc/registration_info_mailer` **5.7.0** versendet Notification-Center-Mails bei Registrierung, Kontoaktivierung, Änderung persönlicher Daten, Passwortänderung sowie manueller Freischaltung oder Deaktivierung von Mitgliedern.

Voraussetzungen: **PHP ^8.3, Contao ^5.7, Notification Center ^2.7**. Contao 4 und 5.3 werden von dieser Hauptversion nicht unterstützt. Die zusätzliche Erweiterung für Gruppenzuordnungen wird nicht mehr benötigt.

## Installation

```sh
composer config repositories.registration-info-mailer vcs https://github.com/dievirtuellecouch/registration_info_mailer-legacy.git
composer require dvc/registration_info_mailer:^5.7
```

Vorher die alte `menatwork/registration_info_mailer`-Anforderung samt lokaler Path-Quelle entfernen. Beide Mailer dürfen nicht gleichzeitig aktiv sein. Nach einer Sicherung die Abhängigkeit installieren, `contao-setup` ausführen und den Datenbankvergleich prüfen. Bestehende RIM-Feldnamen und Benachrichtigungstypen bleiben erhalten; das Paket führt keine destruktiven Migrationen aus.

## Einzelne Mitgliedermails

In den Registrierungs-, persönlichen Daten- und Passwort-Modulen stehen die RIM-Schalter mit Vorlagenauswahl und optionaler Protokollierung zur Verfügung. Die entsprechenden Notification-Center-Module werden ebenfalls unterstützt. Bereits vom Notification Center versandte Nachrichten berücksichtigen, bevor zusätzliche RIM-Mails aktiviert werden.

Registrierte Typen: `member_registration_mail`, `member_activation_mail`, `account_change_mail`, `account_activation_mail`, `account_deactivation_mail`.

Bei Backend-Änderungen wird der vorherige mit dem gespeicherten Kontostatus verglichen. Login-Freigabe, Deaktivierung sowie Start- und Endzeit werden berücksichtigt. Ohne tatsächliche Statusänderung entsteht keine Mail. Der Versand benötigt den RIM-Mail-Schalter und eine ausgewählte oder globale Standardvorlage. Nach einer erfolgreich versandten Deaktivierungsmail wird der Schalter zurückgesetzt.

Mitgliedsfelder sind beispielsweise als `##firstname##`, `##member_firstname##`, `##member_raw_firstname##` und als bisherige Insert-Tags wie `{{rim::firstname}}` verfügbar. Insert-Tags sind auf die gerade erstellte Nachricht begrenzt. Passwort, Sitzung, Autologin, 2FA-Geheimnis und Wiederherstellungscodes werden nicht als Mitgliedstokens weitergereicht.

## E-Mail an Mitgliedsgruppen

Administratoren sehen den Menüpunkt **E-Mail Nachricht**. Dafür eine Benachrichtigung vom Typ **Formularübermittlung** mit dem exakten Titel **E-Mail an Mitglieder** anlegen und mindestens eine Nachricht samt Sprache veröffentlichen. Empfänger: `##form_email##`.

Alle Mitglieder der ausgewählten Gruppen werden berücksichtigt. Mitglieder in mehreren Gruppen werden pro Formularübermittlung nur einmal angeschrieben. Gruppenzuordnungen stammen aus dem regulären Contao-Mitgliederfeld; zusätzliche Tabellen sind nicht erforderlich. Der Versand ist Administratoren vorbehalten und mit CSRF-Tokens geschützt. Nach dem POST wird auf die Ergebnisansicht umgeleitet, damit das Neuladen keinen erneuten Versand auslöst.

Die Backend-Vorlage ist im Paket enthalten: `@RegistrationInfoMailer/mail_to_member.html.twig`.

## Tests

```sh
composer install --no-plugins --no-scripts
composer test
```

Alternativ kann `RIM_PROJECT_AUTOLOAD=/pfad/zum/projekt/vendor/autoload.php` für Tests mit bestehenden Contao-Abhängigkeiten gesetzt werden. Integrationsprüfungen mit Mitgliederdatenbanken ausschließlich in einer Entwicklungsumgebung mit abgefangenem Mailversand durchführen.

Änderungen: [CHANGELOG.md](CHANGELOG.md). Herkunft und Lizenz: [AUTHORS.md](AUTHORS.md), [LICENSE](LICENSE).
