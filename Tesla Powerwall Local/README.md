# Tesla Energiespeicher (Lokal)

[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-6.4-blue.svg)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-1.0.20230121-orange.svg)](https://github.com/Wilkware/TeslaPowerwall)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Actions](https://github.com/Wilkware/TeslaPowerwall/workflows/Check%20Style/badge.svg)](https://github.com/Wilkware/TeslaPowerwall/actions)

Das Modul bietet die Möglichkeit, mit einer Tesla Powerwall (Energiespeicher) über die lokale Netzwerk-API zu kommunizieren.  

## Inhaltverzeichnis

1. [Funktionsumfang](#user-content-1-funktionsumfang)
2. [Voraussetzungen](#user-content-2-voraussetzungen)
3. [Installation](#user-content-3-installation)
4. [Einrichten der Instanzen in IP-Symcon](#user-content-4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#user-content-5-statusvariablen-und-profile)
6. [WebFront](#user-content-6-webfront)
7. [PHP-Befehlsreferenz](#user-content-7-php-befehlsreferenz)
8. [Versionshistorie](#user-content-8-versionshistorie)

### 1. Funktionsumfang

Das Modules bietet eine Auswahl verschiedener API-Endpunkte zu 'abonieren' und die gelieferten Daten in Statusvariablen zu speichern.  
Eine genaue Beschreibung der Daten pro Endpunkt kann man hier nachlesen (<https://github.com/vloschiavo/powerwall2>).  
Die Anzahl an auswählbaren Endpunkten wird sukzessive erweitert.

### 2. Voraussetzungen

* IP-Symcon ab Version 6.4

### 3. Installation

* Über den Modul Store das Modul _Tesla Energiespeicher (Lokal)_ installieren.
* Alternativ Über das Modul-Control folgende URL hinzufügen.  
`https://github.com/Wilkware/TeslaPowerwall` oder `git://github.com/Wilkware/TeslaPowerwall.git`

### 4. Einrichten der Instanzen in IP-Symcon

* Unter 'Instanz hinzufügen' ist das _Tesla Energiespeicher (Lokal)_-Modul unter dem Hersteller 'Tesla' aufgeführt.

__Konfigurationsseite__:

Einstellungsbereich:

> Konto-Informationen ...

Name                               | Beschreibung
---------------------------------- | -----------------------------------------------------------------
Kunden eMail                       | Registrierte Mail-Adresse (Kunde/Customer)
Kunden Kennwort                    | Hinterlegtes Kennwort

> Geräteinformationen ...

Name                               | Beschreibung
---------------------------------- | -----------------------------------------------------------------
IP-Adresse                         | Lokale IP Adresse des Tesla Gateways/Powerwall

> Auswahl der Daten ...

Name                               | Beschreibung
---------------------------------- | -----------------------------------------------------------------
API-Endpunkte                      | Liste der aktuell auswählbaren und abrufbaren Daten-Endpunkte
Aktualisierungsintervall           | Zeitspanne in Minuten für die wiederkehrende Aktualisierung (1min..24h)

> Erweiterte Einstellungen ...

Name                               | Beschreibung
---------------------------------- | -----------------------------------------------------------------
Variablennamen in Großbuchstaben erzeugen! | Alle Datennamen konsequent in Großbustaben umwandeln.
Lebensdauer der Cookies           | Lebensdauer der Authentifizierung ohne jegliche Aktion

_Aktionsbereich:_

Aktion                  | Beschreibung
----------------------- | ---------------------------------
ANMELDEN                | Am Gateway anmelden (LOGIN), erzeugt notwendiges Cookie
ABMELDEN                | Abmelden vom System (löscht das Cookie)!
STATUS                  | Zeigt einige Statusinformationen (kein Login notwendig!)

### 5. Statusvariablen und Profile

Es werden entsprechend den ausgewählten Endpunkten Statusvariablen angelegt. VORSICHT: werden schnell viele Variablen!

### 6. WebFront

Es ist keine weitere Steuerung oder gesonderte Darstellung integriert.

### 7. PHP-Befehlsreferenz

Das Modul stellt keine direkten Funktionsaufrufe zur Verfügung.

### 8. Versionshistorie

v1.0.20240121

* _NEU_: Initialversion

## Entwickler

Seit nunmehr über 10 Jahren fasziniert mich das Thema Haussteuerung. In den letzten Jahren betätige ich mich auch intensiv in der IP-Symcon Community und steuere dort verschiedenste Skript und Module bei. Ihr findet mich dort unter dem Namen @pitti ;-)

[![GitHub](https://img.shields.io/badge/GitHub-@wilkware-181717.svg?style=for-the-badge&logo=github)](https://wilkware.github.io/)

## Spenden

Die Software ist für die nicht kommerzielle Nutzung kostenlos, über eine Spende bei Gefallen des Moduls würde ich mich freuen.

[![PayPal](https://img.shields.io/badge/PayPal-spenden-00457C.svg?style=for-the-badge&logo=paypal)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8816166)

## Lizenz

Namensnennung - Nicht-kommerziell - Weitergabe unter gleichen Bedingungen 4.0 International

[![Licence](https://img.shields.io/badge/License-CC_BY--NC--SA_4.0-EF9421.svg?style=for-the-badge&logo=creativecommons)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
