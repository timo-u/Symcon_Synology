# SynologySystem
Auslesen der Sytemparameter aus einem Synology NAS.

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

* Anzeige von Verbindungszustand
* Anzeige der CPU-Last (5 min)
* Anzeige der Laufzeit in Stunden
* Anzeige der Systemtemperatur
* Anzeige der Speicherauslastung
* Anzeige des eingehenden (gesamten) Netzwerkverkehrs
* Anzeige des ausgehenden (gesamten) Netzwerkverkehrs
* Anzeige des Zustands des NAS (Systemabsturz)
* Anzeige der aktuellen Firmwareversion
* Anzeige verfügbarer Aktualisierungen

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
 Aktualisierungsintervall   | Intervall für automatische Aktualisierungen 

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Name   | Typ     | Beschreibung
------ | ------- | ------------
Status               | boolean | Erreichbarkeit des Systems 
CPU Last (5min)      | float   | CPU-Last der letzten 5 min
Laufzeit             | integer | Laufzeit in Stunden
System-Temperatur    | integer | Systemtemperatur (CPU)
Speicherauslastung   | float   | Prozentuale Belegung des Arbeitsspeichers
Netzwerk (Eingehend) | float   | Eingehender Netzwerkverkehr aller Netzwerkschnittstellen
Netzwerk (Ausgehend) | float   | Ausgehender Netzwerkverkehr aller Netzwerkschnittstellen
Systemabsturz        | boolean | Meldung für Systemabsturz
Firmware Version     | string  | Installierte Firmwareversion
Aktualisierung verfügbar| boolean | Verfügbares Update


#### Profile

Name   | Typ
------ | -------
SYNO_Online          | boolean
SYNO_Percent         | float
SYNO_Mbps            | float
SYNO_Fault           | boolean
SYNO_Temperature     | integer
SYNO_Hour            | integer

### 6. WebFront


Anzeige der Statusvariablen im Webfront.

### 7. PHP-Befehlsreferenz

`boolean SYNOSYS_Update(integer $InstanzID);`
Manuelles Akktualisieren der Instanz. 

Beispiel:
`SYNOSYS_Update(12345);`