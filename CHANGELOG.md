# Änderungen

## 5.7.0

- PHP 8.3, Contao 5.7 und Notification Center 2.7 als unterstützte Basis festgelegt.
- Entfernte Controller-Protokollierung durch den PSR-Logger mit Contao-Kontext ersetzt. Fehlgeschlagene Zustellungen werden nicht als Erfolg protokolliert.
- Bundle-Verzeichnis und Ressourcenregistrierung korrigiert. Doppelte Legacy-Ressourcen und alte system/modules-Kopie entfernt.
- RIM-Felder für Core- und Notification-Center-Module registriert.
- Gemeinsamen Versanddienst und auf die jeweilige Nachricht begrenzte RIM-Insert-Tags eingeführt. Unpräfixierte und präfixierte Mitgliedstokens einschließlich leerer Feldwerte unterstützt.
- Statuswechsel berücksichtigen Freigabe, Sperre und zeitliche Gültigkeit. Wiederholte Callbacks erzeugen keine zusätzlichen Nachrichten.
- Globale Standardvorlagen werden auch bei gespeicherter Null berücksichtigt.
- Gruppenversand mit Twig-Vorlage vervollständigt und auf native Mitgliedsgruppen umgestellt. Die veraltete Gruppen-Abhängigkeit entfernt.
- Gruppenversand auf Administratoren beschränkt, Formular-Token ergänzt und Ergebnisanzeige mit POST/Redirect/GET eingeführt.
- Automatische Auswahl einer möglicherweise unpassenden Formularbenachrichtigung entfernt.
- Automatisierte Regressionstests ergänzt.

## 1.0.0

Vorheriger DVC-Stand mit Contao-5.3-Anforderung.
