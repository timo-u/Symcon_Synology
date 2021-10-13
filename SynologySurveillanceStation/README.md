# SynologySurveillanceStation
Liefert den Zustand der Kameras der SurveillanceStation

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

* Liefert den Zustand der Kameras der SurveillanceStation

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
Kamera: NUMMER (NAME)          | integer | Zustand der Kamera


#### Profile

Name   | Typ
------ | -------
SYNO_Camera_State       |integer


### 6. WebFront

Anzeige der Statusvariablen im Webfront.

### 7. PHP-Befehlsreferenz

`boolean SYNOSYS_Update(integer $InstanzID);`
Manuelles Akktualisieren der Instanz. 

Beispiel:
`SYNOSYS_Update(12345);`