Kurzinfo zum Ablauf der Funktionsaufrufe

Initialisierung Mapbender-Modul: search.js
addmyLayer();
addBaselayers()
fest verdrahtete Hintergrundkarten in addBaselayers();
kann auch durch baseinfo[] mit dynamischen Hintergrundkarten ergänzt werden

BaseLayer() in searchobjects.js baut Darstellung


Suche nach diensten
searchMaps()
--> parseMapBenderJson()    Parsen der json-Daten
--> appendData() Ändern: favicon.ico	Fügt Dienste in Dom ein

Aufruf WMC:
map.php 
searchWmc(); // Aufruf Proxyscript wmc
Antwort json -->appendWmc() ID-String zusammenbauen
appendWmcData()
