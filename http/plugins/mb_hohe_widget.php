
var $measure = $(this);

var MeasureApi = function (o) {

	var measureDialog,
		button,

		that = this,
		inProgress = false,
		title = o.title,
		defaultHtml = "<div title='" + title + "'>" +
			"<div class='mb-measure-text'><?php 
				echo nl2br(htmlentities("Klicken Sie in die Karte, um eine Strecke zu zeichnen, mit Doppelklick beim letzten Punkt wird ein Hoehendiagramm erzeugt.", ENT_QUOTES, "UTF-8"));
			?></div></div>",
		informationHtml =
                        "<canvas id='can' width='630' height='250'></canvas>";

	var jsonarray = [];		  
        
	var hideMeasureData = function () {
		measureDialog.find(".mb-measure-clicked-point").parent().hide();
		measureDialog.find(".mb-measure-current-point").parent().hide();
		measureDialog.find(".mb-measure-distance-last").parent().hide();
		measureDialog.find(".mb-measure-distance-total").parent().hide();
                measureDialog.find(".mb-measure-angle").parent().hide();

	};

	var changeDialogContent = function () {
		measureDialog.html(informationHtml);
		hideMeasureData();

		o.$target.unbind("click", changeDialogContent);
	};

	var create = function () {
		//
		// Initialise measure dialog
		//
                //alert(o.$target.offset().left + ' ' + o.$target.offset().top);
		measureDialog = $(informationHtml);
		measureDialog.dialog({
			dialogClass: "ownSuperClass",
            autoOpen: false,
			position: [20,80],
                        width : 'auto',
                        heigth: 'auto',
                        title: 'Höhenprofil',
                        open: function() {$('#toolsContainer').hide() && $('a.toggleToolsContainer').removeClass('activeToggle');},
                        close: function() {$('#altitudeProfile').removeClass("myOnClass");button.stop();}
                        
		}).bind("dialogclose", function () {
			button.stop();
			that.destroy();
		});

		//
		// Initialise button
		//
		button = new Mapbender.Button({
			domElement: $measure.get(0),
			over: o.src.replace(/_off/, "_over"),
			on: o.src.replace(/_off/, "_on"),
			off: o.src,
			name: o.id,
			go: that.activate,
			stop: that.deactivate
		});
	};


        var clearJsonArray  = function(evt,data) {
           
           

           jsonarray = [];
           points = [];


        };
        var updateJsonArray = function (evt, data) {
            
          jsonarray.push(data); 
   
        };


	var updateView = function (evt, data) {
                
                 
                 if(data == -1)
                 {
                   ctx.clearRect(0, 0, 630, 250);
                   prep_json(jsonarray);
                   
                   draw_lineII();
                   draw_Points(data);
                   draw_stuetzpunkte();
                   koordinaten_system_zeichnen(hoehe_min,hoehe_max,gesamt_laenge);
                 }
                 else if (data == -2)
                 {
                   
                   ctx.clearRect(0, 0, 630, 250);
                   draw_lineII();
                   draw_Points(data);
                   draw_stuetzpunkte();
                   koordinaten_system_zeichnen(hoehe_min,hoehe_max,gesamt_laenge);


                 }
                 else
                 {
                   if(data == -5) data = 0;
                   ctx.clearRect(0, 0, 630, 250);
                   draw_lineII();
                   draw_Points(data);
                   draw_stuetzpunkte();
                   koordinaten_system_zeichnen(hoehe_min,hoehe_max,gesamt_laenge);

                 }


	};

	var finishMeasure = function () {
		inProgress = false;
		that.deactivate();
	};

	var reinitializeMeasure = function () {
		inProgress = false;
		that.deactivate();
		that.activate();
	};

	this.activate = function () {
                //remove measured x and y values from print dialog
                $('input[name="measured_x_values"]').val("");
                $('input[name="measured_y_values"]').val("");

		if (o.$target.size() > 0) {
			o.$target
				.mb_hohe(o)
                                .bind("mb_hohecleardia", clearJsonArray)
                                .bind("mb_hohepointadded", updateJsonArray)                                
				.bind("mb_hoheupdate", updateView)				
				.bind("mb_hohelastpointadded", finishMeasure)
				.bind("mb_hohereinitialize", reinitializeMeasure)
				.bind("click", changeDialogContent);
		}
		if (!inProgress) {
			inProgress = true;
			measureDialog.html(defaultHtml);
		}

		measureDialog.dialog("open");
                setText();
	};

	this.destroy = function () {
		if (o.$target.size() > 0) {
			o.$target.mb_hohe("destroy")
                                .unbind("mb_hohepointadded", updateJsonArray)
                                .unbind("mb_hohecleardia", clearJsonArray)
				.unbind("mb_hoheupdate", updateView)
                                .unbind("mb_measurelastpointadded", finishMeasure)
				.unbind("mb_measurereinitialize", reinitializeMeasure);
		}
                ctx.clearRect(0, 0, 600, 250);
                points = [];
                jsonarray = [];
		hideMeasureData();
                ctx.fillText(t,9,15);
                hoehe_min = 700;
                hoehe_max = 100;
                gesamt_laenge = 0;
		if (measureDialog.dialog("isOpen")) {
			measureDialog.dialog("close");
		}
		measureDialog.html(defaultHtml);

                //remove measured x and y values from print dialog
                $('input[name="measured_x_values"]').val("");
                $('input[name="measured_y_values"]').val("");
	};
	
	this.deactivate = function () {
		if (o.$target.size() > 0) {
			o.$target.mb_hohe("deactivate");
		}
	};


	create();






var gesamt_laenge = 0;
var hoehe_min = 700;
var hoehe_max = 100;
var points = [];
var points_count =0;
var width = 600;
var height = 250;
var y_0 = height -30;
var y_oben = 20;
var font = "12px Arial"
var color_coord ='#99BF86';
var color_coord_halb = '#A3C1A7';//#838A87
var color_coord_garnicht = '#A3ABA7';
var line_100 = '#333333';
var c = document.getElementById("can");
var ctx = c.getContext("2d");




/*
 
wenn strecke:
man hat (reale) Werte 
Anfangswert start2
Endwert stop2
und will wissen welchem Wert (real) Wert wert2 im Vergleich auf der Strecke start1 bis stop1 entspricht

wenn !strecke:
hier wird berücksichtigt wenn start1 und/oder start2 nicht im 0-Punkt liegen für die Koordinate mitverrechnet werden müssen

*/
    var umrechnen = function (start1, stop1, start2, stop2, wert2, strecke) {
        var s_1 = stop1 - start1;
        var s_2 = stop2 - start2;
        if (strecke)
            return (wert2 / s_2) * s_1;
        else
            return Math.ceil(((wert2 - start2) / s_2) * s_1 + start1);
    }


/*
in w_hohe.js wurde im Pumkt 0 die totaldistance hinterlegt.
die von w_hohe.js übergebenen jarray Punkte werden an points übergeben und für das Diagramm umgerechnet in Pixel
hoehe_min, hoehe_max werden ermittelt.
*/
    var prep_json = function (jarray) {
        points_count = jarray.length;
        if( jarray[0].abstand > 0)
        gesamt_laenge = jarray[0].abstand;
        //alert('gesamt_laenge: ' + gesamt_laenge+ " " + points_count);
        jarray[0].abstand = 0;

        for (var i = 0; i < points_count; i++) {
            if (jarray[i].hoehe > hoehe_max) hoehe_max = jarray[i].hoehe;
            if (jarray[i].hoehe < hoehe_min) hoehe_min = jarray[i].hoehe;
        }

        var acc = 0;

        for (var i = 0; i < points_count; i++) {
            acc += jarray[i].abstand;
            var daten =
            {
                x: umrechnen(30, width - 30, 0, gesamt_laenge, acc, false),
                y: height - umrechnen(30, height - 20, hoehe_min, hoehe_max, jarray[i].hoehe, false),
                hoehe: jarray[i].hoehe,
                stuetzpunkt: jarray[i].stuetzpunkt,
//neu: farbliche Hervorhebung, ob ein Punkt im Bildbereich ist oder nicht( Kartenzoom oder Verschiebung)
	        ist_in_BBox: jarray[i].ist_in_BBox
            };
            points.push(daten);
        }
    }






    var koordinaten_system_zeichnen = function (start, stop, stop_meter) {
        //start = real Wert hoehe_min, stop  = hoehe_max
        var y_gesamt = y_0 - y_oben;
        var y_gesamt_real = stop - start;
        var l = Math.ceil(start / 100) * 100;
        var zeichnen_erste_linie = true;
        var l2 = start % 100;
        var dist = (width - 30 - 30) / 10;

        ctx.beginPath();
		//zeichne x - Achse
        ctx.moveTo(30, height - 20);
        ctx.lineTo(width - 30, height - 20);
		
		//Zeichne y - Achse
        ctx.moveTo(30, height - 20);
        ctx.lineTo(30, 20);
		
		
        ctx.lineWidth = 1;
        ctx.strokeStyle = color_coord;
        ctx.stroke();
//y_oben bekommt max Höhe
//"Minibindestrich" mit Ausgabe stop = max. Höhe, y - Achsenmarkierung
        ctx.beginPath();
        ctx.moveTo(30, y_oben);
        ctx.lineTo(27, y_oben);
        ctx.fillStyle = "#888888";
        ctx.font = font;

        ctx.fillText(stop, 3, y_oben + 4);
//y_0 ist min Höhe = start
        ctx.moveTo(30, y_0);
        ctx.lineTo(27, y_0);
        ctx.fillText(start, 3, y_0 + 4);
        ctx.stroke();

        if (l2 > 70) zeichnen_erste_linie = false;
//l2 == 0 ist der Sonderfall, wenn außerhalb der Daten gemessen wird.
	if(l2 != 0)
            l2 = 100 - l2;
        l2 = umrechnen(0, 200, start, stop, l2, true);
        l2 = y_0 - l2;
//l2 ist die nächste 100- er Linie über y_0 (min. Höhe)die schwach gezeichnet wird,
//wenn y_0 zu nah dran ist wird kein Text z.B. "100" oder "200" ausgegeben.
        ctx.beginPath();
        ctx.moveTo(30, l2);
        ctx.lineTo(width - 30, l2);
        if (zeichnen_erste_linie) ctx.fillText(l, 3, l2 + 4);
        ctx.lineWidth = 0.1;
        ctx.strokeStyle = line_100;
        ctx.stroke();
/* im Prinzip l = l2 am Anfang, wird gleich im 100 erhöht
l dient als Ausgabe von Text
*/
        while (true) {
            l += 100;
            if ((stop < l)) break;
//l2 ist die nächste 100 -er Linie
            l2 -= umrechnen(0, 200, start, stop, 100, true);

            ctx.beginPath();
            ctx.moveTo(30, l2);
            ctx.lineTo(width - 30, l2);
            if ((l2 + 4 - 14) > y_oben + 4) ctx.fillText(l, 3, l2 + 4);
            ctx.lineWidth = 0.1;
            ctx.strokeStyle = line_100;
            ctx.stroke();
        }
//x - Achse wird gesetzt mit Strecke
        for (var i = 1; i < 11; i++) {
            ctx.beginPath();
            ctx.moveTo(30 + i * dist, height - 20);
            ctx.lineTo(30 + i * dist, height - 17);
            ctx.fillText(m_or_km(stop_meter / 10 * i, stop_meter), 16 + i * dist, height - 3);
            ctx.lineWidth = 1;
            ctx.strokeStyle = color_coord;
            ctx.stroke();
        }
    };






/*
Anpassung der Einheit der x - Achse des Diagramms
*/

    var m_or_km = function(teil,gesamt) {

        if(gesamt > 999) {
            return "" + (Math.floor((teil/1000) * 10) / 10) + " km";
        }
        else
            return "" + Math.floor(teil) + " m";
    }

/*
stuetzpunkte sind die Punkte die der Anwender tatsächsich geklickt hat,
sie werden hier hervorgehoben gezeicnet.
*/

    var draw_stuetzpunkte = function() {

        ctx.fillStyle = "#888888";
        for(var i = 0;i< points_count; i++)
           if(points[i].stuetzpunkt)
               ctx.fillRect(points[i].x-2,points[i].y-1,4,4);

        ctx.fillStyle = "#888888";

    }

//zeichne Fadenkreuz
    var draw_Points = function(mark) {


        if(mark >= 0) {

            ctx.beginPath();
            ctx.moveTo(points[mark].x - 2,points[mark].y+1);
            ctx.lineTo(30,points[mark].y+1);
	
            ctx.moveTo(points[mark].x + 2,points[mark].y+1);
            ctx.lineTo(width-30,points[mark].y+1);
	
            ctx.moveTo(points[mark].x,points[mark].y - 1);
            ctx.lineTo(points[mark].x,20);
	
            ctx.moveTo(points[mark].x,points[mark].y + 3);
            ctx.lineTo(points[mark].x,height-20);
	
	
            ctx.lineWidth = 1;
            ctx.strokeStyle = line_100;
            ctx.stroke();
            ctx.fillStyle = line_100;
            ctx.fillText('~ ' + points[mark].hoehe + ' m',points[mark].x + 8,points[mark].y - 5);
        
        }
    }
/*
erste Linie: von 0 bis punkt0.höhe, 
dann Linie zeichnen von Punkt zu Punkt
am Schluss zur Grundlinie hinunterheichnen und mit 0 Punkt verbinden -> cosePath -> Fläche ausmalen
*/

    var draw_line = function() {
    

        ctx.beginPath();
        ctx.moveTo(30,height-20);
        ctx.lineTo(30,points[0].y);
        for(var i = 1;i< points_count; i++)
            ctx.lineTo(points[i].x,points[i].y);

        ctx.lineTo(width-30,height-20);
        ctx.lineTo(30,height-20);
        ctx.closePath();

        ctx.fillStyle = color_coord;
        ctx.fill();

        ctx.lineWidth = 0.1;
        ctx.strokeStyle = color_coord;
        ctx.stroke(); 
}
/*
neu 27.01.2020
der geschlossen Linienpfad ist hier die Fläche unterhalb 2 Punkten
er wird farblich gezeichnet je nach dem ob die zwei Punkte in der BBox sind oder nicht.
*/
    var draw_lineII = function () {

		for (var i = 1; i < points_count; i++) {
			if(i == 1) {
				ctx.beginPath();
				ctx.moveTo(30, height - 20);
				ctx.lineTo(30, points[0].y);
				ctx.lineTo(points[i].x, points[i].y);
				ctx.lineTo(points[i].x,height - 20);
				ctx.lineTo(30, height - 20);
				ctx.closePath();

				if(points[i - 1].ist_in_BBox && points[i].ist_in_BBox)
					ctx.fillStyle = color_coord;
				else if(points[i - 1].ist_in_BBox || points[i].ist_in_BBox)
					ctx.fillStyle = color_coord_halb;
				else ctx.fillStyle = color_coord_garnicht;
				
				ctx.fill();

				ctx.lineWidth = 0.1;
				if(points[i - 1].ist_in_BBox && points[i].ist_in_BBox)
					ctx.strokeStyle = color_coord;
				else if(points[i - 1].ist_in_BBox || points[i].ist_in_BBox)
					ctx.strokeStyle = color_coord_halb;
			        else ctx.strokeStyle = color_coord_garnicht;
		
				ctx.stroke();
				ctx.moveTo(points[i].x, points[i].y);
				continue;
			
			}
			
			
			ctx.beginPath();
			ctx.lineTo(points[i].x, points[i].y);
			ctx.lineTo(points[i].x,height - 20);
			ctx.lineTo(points[i-1].x,height - 20);
			ctx.lineTo(points[i-1].x, points[i-1].y);
			ctx.closePath();
			if(points[i - 1].ist_in_BBox && points[i].ist_in_BBox)
				ctx.fillStyle = color_coord;
			else if(points[i - 1].ist_in_BBox || points[i].ist_in_BBox)
				ctx.fillStyle = color_coord_halb;
			else

 				ctx.fillStyle = color_coord_garnicht;

			ctx.fill();

			ctx.lineWidth = 0.1;
			if(points[i - 1].ist_in_BBox && points[i].ist_in_BBox)
				ctx.strokeStyle = color_coord;
			else if(points[i - 1].ist_in_BBox || points[i].ist_in_BBox)
				ctx.strokeStyle = color_coord_halb;
			else ctx.strokeStyle = color_coord_garnicht;
		
			ctx.stroke();
			
			
		}


    }

    var setText = function() {
        ctx.font = "12px Arial";

        ctx.clearRect(0, 0, 600, 250);
        ctx.fillText("Sie koennen mit Klicken eine Strecke in die Kartei zeichnen. Beim letzten Punkt bitte ein Doppelklick.",9,15);
        ctx.fillText("Nach dem Erstellen koennen Sie ueber die Strecke fahren und bekommen die Hoehe angezeigt.",9,30);
        draw_stuetzpunkte();
    }

};
$measure.mapbender(new MeasureApi(options));
