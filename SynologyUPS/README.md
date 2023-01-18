
# SynologyUPS
Auslesen der USV / UPS Parameter aus einem Synology NAS.

### Inhaltsverzeichnis

- [SynologyUPS](#synologyups)
		- [Inhaltsverzeichnis](#inhaltsverzeichnis)
		- [1. Funktionsumfang](#1-funktionsumfang)
		- [2. Vorraussetzungen](#2-vorraussetzungen)
		- [3. Software-Installation](#3-software-installation)
		- [4. Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
		- [5. Statusvariablen und Profile](#5-statusvariablen-und-profile)
			- [Statusvariablen](#statusvariablen)
			- [Profile](#profile)
		- [6. WebFront](#6-webfront)
		- [7. PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang


* Anzeige der Verbindung zum NAS
* Anzeige der Verbindung zur USV
* Anzeige des USV Status
* Anzeige des Ladestands
* Anzeige der Laufzeit


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
Verbindung zum NAS	| boolean | Erreichbarkeit des Systems 
Verbindung zur USV	| boolean | Verbindung von NAS zur USV
USV Status    | string  | Status der USV im NAS
Ladezustand    | float   | Ladezustand in Prozent
Laufzeit             | integer | verbleibende Laufzeit in Minuten



#### Profile

Name   | Typ
------ | -------
SYNO_Online          | boolean
SYNO_Percent         | float
SYNO_Minute            | integer

### 6. WebFront


Anzeige der Statusvariablen im Webfront.

### 7. PHP-Befehlsreferenz

`boolean SYNOUPS_Update(integer $InstanzID);`
Manuelles Akktualisieren der Instanz. 

Beispiel:
`SYNOUPS_Update(12345);`