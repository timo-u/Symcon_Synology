# SynologyIO
Die IO-Instanz stellt die Verbindung zur Synology her und kümmert sich um den Anmeldevorgang.

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

* Verbindet sich mit der Synology und regelt den Anmeldevorgang

### 2. Vorraussetzungen

- IP-Symcon ab Version 6.0

### 3. Software-Installation

* Über den Module Store das 'Synology'-Modul installieren.
* Alternativ über das Module Control folgende URL hinzufügen: https://github.com/timo-u/Symcon_Synology

### 4. Einrichten der Instanzen in IP-Symcon

 Unter 'Instanz hinzufügen' kann das 'Synology'-Modul mithilfe des Schnellfilters gefunden werden.  
	- Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

Name     | Beschreibung
-------- | ------------------
URL                  | Komplette URL bestehend aus Protokoll, IP oder domain und Port ()
Benutzername         | Benutzername zur Anmeldung an der Synology
Passwort             | Passwort zur Anmeldung an der Synology
Server Überprüfen    | Prüft ob das Zertifikat zum Server passt
Zertifikat Prüfen    | Prüft die Zertifikatskette (Bei selbstsigniertem Zertifikat deaktivieren)

### 5. Statusvariablen und Profile

- keine

### 6. WebFront

- keine

### 7. PHP-Befehlsreferenz

`boolean SYNOIO_Login(integer $InstanzID,bool $force);`
Die Variable $force versucht eine Anmeldung, auch wenn ein vorheriges anmelden fehlgeschlagen ist.

Beispiel:
`SYNOIO_Login(12345,true);`