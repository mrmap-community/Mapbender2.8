<?php
# $Id: mod_inspireGpkg_server.php 1234 2023-07-03 16:21:04Z armin11 $
# 
# License:
# Copyright (c) 2009, Open Source Geospatial Foundation
# This program is dual licensed under the GNU General Public License
# and Simplified BSD license.
# http://svn.osgeo.org/mapbender/trunk/mapbender/license/license.txt

require(dirname(__FILE__)."/mb_validateSession.php");
require_once dirname(__FILE__) . "/../classes/class_user.php";

$ajaxResponse = new AjaxResponse($_POST);

function validateSpatialDatasetIdentifier($tmpSpatialDatasetIdentifier) {
    if (filter_var($tmpSpatialDatasetIdentifier, FILTER_VALIDATE_URL) === false) {
        return false;
    } else {
        return true;
    }
}

function validateGeojsonPolygon($tmpGeojson) {
    
    if ($tmpGeojson->type == 'Feature' && $tmpGeojson->geometry->type == 'Polygon') {
        return true;
    } else {
        return false;
    }
}

function validateType($tmpType) {
    if (in_array($tmpType, array('raster', 'vector'))) {
        return true;
    } else {
        return false;
    }
}

switch ($ajaxResponse->getMethod()) {
	case "checkOptions" :
		if (!Mapbender::postgisAvailable()) {
			$ajaxResponse->setSuccess(false);
			$ajaxResponse->setMessage(_mb("PostGIS is not available. Please contact the administrator."));
			$ajaxResponse->send();
		}
		//get configuration from ajax call
		$configuration = $ajaxResponse->getParameter("configuration");	
		//check configuration values 
		$configurationValid = false;
		//validate spatial_dataset_identifier
		foreach ($configuration->dataset_configuration->datasets as $spatialDataset) {
		    //$spatialDataset['resourceidentifier'];
		    $configurationValid = validateSpatialDatasetIdentifier($spatialDataset->resourceidentifier);
		    if ($configurationValid == false) {
		        break;
		    }
		}
		if ($configurationValid == false) {
		    $ajaxResponse->setSuccess(false);
		    $ajaxResponse->setMessage(_mb("Some spatial dataset identifier is not valid!"));
		} else {
		    $ajaxResponse->setSuccess(true);
		    $ajaxResponse->setMessage(_mb("Method checkOptions requested."));
		    //$e = new mb_exception("input for python method check_options: '" . json_encode($configuration) . "'");
		    #https://stackoverflow.com/questions/39471295/how-to-install-python-package-for-global-use-by-all-users-incl-www-data
		    //$e = new mb_exception('/usr/bin/python3 /tmp/inspire-gpkg-cache/cli_invoke.py ' . "'" . json_encode($configuration) . "'" . " " . "'checkOptions'" );
		    
		    $output = exec('/usr/bin/python3.9 ../extensions/inspire-gpkg-cache/cli_invoke.py ' . "'" . json_encode($configuration) . "'" . " " . "'checkOptions'" );
		    //$e = new mb_exception("output of python method check_options: " . $output);
		    //die();
		    
		    // some example json files for testing the client
		    $neg_list = <<<JSON
                [{"spatial_dataset_identifier": "http://www.mapbender.org/registry/spatial/dataset/8262a5ba-d4ce-6dce-b966-e34b7f325767",
                 "time_to_resolve_dataset": "0.6598763465881348",
                 "error_messages": ["Metadata could not be found in catalogue!"]},
                 {"spatial_dataset_identifier": "http://www.mapbender.org/registry/spatial/dataset/244f6ec7-7bb8-3306-9605-1e7e3c399582", 
                 "time_to_resolve_dataset": "0.27190661430358887",
                 "error_messages": ["Metadata could not be found in catalogue!"]}]
                JSON;
		    $pos_list = <<<JSON
                [{
                	"spatial_dataset_identifier": "https://registry.gdi-de.org/id/de.rp.vermkv/46f2d53e-6b79-284b-46a4-5f06c6248502",
                	"time_to_resolve_dataset": "0.520965576171875",
                	"error_messages": [],
                	"title": "ATKIS DTK50",
                	"fileidentifier": "46f2d53e-6b79-284b-46a4-5f06c6248502",
                	"format": "GeoTIFF",
                	"epsg_id": 25832,
                	"services": [{
                		"service_type": "view",
                		"service_version": "OGC:WMS 1.1.1",
                		"possible_dataset_type": "raster",
                		"access_uri": "https://www.geoportal.rlp.de/mapbender/php/wms.php?inspire=1&layer_id=61669&withChilds=1&REQUEST=GetCapabilities&SERVICE=WMS",
                		"service_resource_name": "rp_dtk50",
                		"error_messages": []
                	}, {
                		"service_type": "download",
                		"service_version": "predefined ATOM",
                		"possible_dataset_type": null,
                		"access_uri": null,
                		"service_resource_name": null,
                		"error_messages": ["ATOM Feed: No link to dataset feed for Spatial Dataset Identifier found in service feed", "Service is not usable for downloading dataset"]
                	}],
                	"time_to_resolve_services": "0.5183570384979248"
                }, {
                	"spatial_dataset_identifier": "https://registry.gdi-de.org/id/de.rp.vermkv/6c1a481c-72f2-45a0-32e8-0fcb89dc31eb",
                	"time_to_resolve_dataset": "0.3269343376159668",
                	"error_messages": [],
                	"title": "ATKIS DTK25",
                	"fileidentifier": "6c1a481c-72f2-45a0-32e8-0fcb89dc31eb",
                	"format": "GeoTIFF",
                	"epsg_id": 25832,
                	"services": [{
                		"service_type": "view",
                		"service_version": "OGC:WMS 1.1.1",
                		"possible_dataset_type": "raster",
                		"access_uri": "https://www.geoportal.rlp.de/mapbender/php/wms.php?inspire=1&layer_id=61673&withChilds=1&REQUEST=GetCapabilities&SERVICE=WMS",
                		"service_resource_name": "rp_dtk25",
                		"error_messages": []
                	}, {
                		"service_type": "download",
                		"service_version": "predefined ATOM",
                		"possible_dataset_type": null,
                		"access_uri": null,
                		"service_resource_name": null,
                		"error_messages": ["ATOM Feed: No link to dataset feed for Spatial Dataset Identifier found in service feed", "Service is not usable for downloading dataset"]
                	}],
                	"time_to_resolve_services": "0.3975808620452881"
                }, {
                	"spatial_dataset_identifier": "https://registry.gdi-de.org/id/de.rp.vermkv/69ec8eb9-9b0f-57c4-30b4-d171cc974fda",
                	"time_to_resolve_dataset": "0.38181567192077637",
                	"error_messages": [],
                	"title": "LVermGeoRP_DTK5",
                	"fileidentifier": "69ec8eb9-9b0f-57c4-30b4-d171cc974fda",
                	"format": "GeoTIFF",
                	"epsg_id": 25832,
                	"services": [{
                		"service_type": "download",
                		"service_version": "predefined ATOM",
                		"possible_dataset_type": null,
                		"access_uri": null,
                		"service_resource_name": null,
                		"error_messages": ["ATOM Feed: No link to dataset feed for Spatial Dataset Identifier found in service feed", "Service is not usable for downloading dataset"]
                	}, {
                		"service_type": "view",
                		"service_version": "OGC:WMS 1.1.1",
                		"possible_dataset_type": "raster",
                		"access_uri": "https://www.geoportal.rlp.de/mapbender/php/wms.php?inspire=1&layer_id=24143&withChilds=1&REQUEST=GetCapabilities&SERVICE=WMS",
                		"service_resource_name": "rp_dtk5",
                		"error_messages": []
                	}],
                	"time_to_resolve_services": "0.4434032440185547"
                }, {
                	"spatial_dataset_identifier": "https://registry.gdi-de.org/id/de.rp.vermkv/e7f59a98-c64c-bf3e-301e-1be256de1272",
                	"time_to_resolve_dataset": "0.36509060859680176",
                	"error_messages": [],
                	"title": "Kartenaufnahme der Rheinlande durch Tranchot und von M\u00fcffling (1803 - 1820)",
                	"fileidentifier": "e7f59a98-c64c-bf3e-301e-1be256de1272",
                	"format": "GeoTIFF",
                	"epsg_id": 25832,
                	"services": [{
                		"service_type": "view",
                		"service_version": "OGC:WMS 1.1.1",
                		"possible_dataset_type": "raster",
                		"access_uri": "https://www.geoportal.rlp.de/mapbender/php/wms.php?inspire=1&layer_id=49370&withChilds=1&REQUEST=GetCapabilities&SERVICE=WMS",
                		"service_resource_name": "rp_hktm",
                		"error_messages": []
                	}, {
                		"service_type": "download",
                		"service_version": "predefined ATOM",
                		"possible_dataset_type": null,
                		"access_uri": null,
                		"service_resource_name": null,
                		"error_messages": ["ATOM Feed: No link to dataset feed for Spatial Dataset Identifier found in service feed", "Service is not usable for downloading dataset"]
                	}],
                	"time_to_resolve_services": "0.5657927989959717"
                }, {
                	"spatial_dataset_identifier": "https://registry.gdi-de.org/id/de.rp.vermkv/2b009ae4-aa3e-ff21-870b-49846d9561b2",
                	"time_to_resolve_dataset": "0.41145801544189453",
                	"error_messages": [],
                	"title": "Luftbilder Rheinland-Pfalz DOP40",
                	"fileidentifier": "2b009ae4-aa3e-ff21-870b-49846d9561b2",
                	"format": "GeoTIFF",
                	"epsg_id": 25832,
                	"services": [{
                		"service_type": "view",
                		"service_version": "OGC:WMS 1.1.1",
                		"possible_dataset_type": "raster",
                		"access_uri": "https://www.geoportal.rlp.de/mapbender/php/wms.php?inspire=1&layer_id=61676&withChilds=1&REQUEST=GetCapabilities&SERVICE=WMS",
                		"service_resource_name": "rp_dop40",
                		"error_messages": []
                	}, {
                		"service_type": "download",
                		"service_version": "predefined ATOM",
                		"possible_dataset_type": "raster",
                		"access_uri": "https://www.geoportal.rlp.de/mapbender/php/mod_inspireDownloadFeed.php?id=2b009ae4-aa3e-ff21-870b-49846d9561b2&type=DATASET&generateFrom=wmslayer&layerid=30694",
                		"service_resource_name": null,
                		"error_messages": []
                	}],
                	"time_to_resolve_services": "0.5256874561309814"
                }, {
                	"spatial_dataset_identifier": "http://www.dlr-rnh.rlp.de/registry/spatial/dataset/7feed374-92ee-0441-cc8f-594651df2296",
                	"time_to_resolve_dataset": "0.4580819606781006",
                	"error_messages": [],
                	"title": "Nitrat-belastete Gebiete",
                	"fileidentifier": "7feed374-92ee-0441-cc8f-594651df2296",
                	"format": "Esri Shape",
                	"epsg_id": 3857,
                	"services": [{
                		"service_type": "view",
                		"service_version": "OGC:WMS 1.1.1",
                		"possible_dataset_type": "raster",
                		"access_uri": "https://www.geoportal.rlp.de/mapbender/php/wms.php?inspire=1&layer_id=71995&withChilds=1&REQUEST=GetCapabilities&SERVICE=WMS",
                		"service_resource_name": "1",
                		"error_messages": []
                	}],
                	"time_to_resolve_services": "0.35742688179016113"
                }, {
                	"spatial_dataset_identifier": "http://naturschutz.rlp.de/2b115f1ebeb7b0f8d7362b049d0e0f68",
                	"time_to_resolve_dataset": "0.4432182312011719",
                	"error_messages": [],
                	"title": "Biotopkataster (Fl\u00e4chen)",
                	"fileidentifier": "2b115f1e-beb7-b0f8-d736-2b049d0e0f68",
                	"format": "Database",
                	"epsg_id": 4258,
                	"services": [{
                		"service_type": "oaf",
                		"service_version": null,
                		"possible_dataset_type": "vector",
                		"access_uri": "https://www.geoportal.rlp.de/spatial-objects/537/collections/ms:bk_f",
                		"service_resource_name": null,
                		"error_messages": []
                	}, {
                		"service_type": "download",
                		"service_version": "predefined ATOM",
                		"possible_dataset_type": "raster",
                		"access_uri": "https://www.geoportal.rlp.de/mapbender/php/mod_inspireDownloadFeed.php?id=2b115f1e-beb7-b0f8-d736-2b049d0e0f68&type=DATASET&generateFrom=wmslayer&layerid=54195",
                		"service_resource_name": null,
                		"error_messages": []
                	}, {
                		"service_type": "view",
                		"service_version": "OGC:WMS 1.1.1",
                		"possible_dataset_type": "raster",
                		"access_uri": "https://www.geoportal.rlp.de/mapbender/php/wms.php?inspire=1&layer_id=54206&withChilds=1&REQUEST=GetCapabilities&SERVICE=WMS",
                		"service_resource_name": "bk_f_text",
                		"error_messages": []
                	}, {
                		"service_type": "download",
                		"service_version": "OGC:WFS 2.0.0",
                		"possible_dataset_type": "vector",
                		"access_uri": "https://www.geoportal.rlp.de/mapbender/php/wfs.php?INSPIRE=1&FEATURETYPE_ID=3015&REQUEST=GetCapabilities&SERVICE=WFS&VERSION=2.0.0",
                		"service_resource_name": "ms:bk_f",
                		"error_messages": []
                	}, {
                		"service_type": "view",
                		"service_version": "OGC:WMS 1.1.1",
                		"possible_dataset_type": "raster",
                		"access_uri": "https://www.geoportal.rlp.de/mapbender/php/wms.php?inspire=1&layer_id=54195&withChilds=1&REQUEST=GetCapabilities&SERVICE=WMS",
                		"service_resource_name": "bk_f",
                		"error_messages": []
                	}, {
                		"service_type": "download",
                		"service_version": "predefined ATOM",
                		"possible_dataset_type": null,
                		"access_uri": "https://www.geoportal.rlp.de/mapbender/php/mod_inspireDownloadFeed.php?id=2b115f1e-beb7-b0f8-d736-2b049d0e0f68&type=DATASET&generateFrom=wfs&wfsid=537&featuretypeid=3015",
                		"service_resource_name": null,
                		"error_messages": ["Service is not usable for downloading dataset"]
                	}],
                	"time_to_resolve_services": "0.6356899738311768"
                }, {
                	"spatial_dataset_identifier": "http://www.lbm.rlp.de/registry/spatial/dataset/b7f3e7fd-48cb-a886-d4fa-35542de49288",
                	"time_to_resolve_dataset": "0.4116837978363037",
                	"error_messages": [],
                	"title": "Landstra\u00dfen",
                	"fileidentifier": "b7f3e7fd-48cb-a886-d4fa-35542de49288",
                	"format": "Database",
                	"epsg_id": 31466,
                	"services": [{
                		"service_type": "download",
                		"service_version": "predefined ATOM",
                		"possible_dataset_type": "raster",
                		"access_uri": "https://www.geoportal.rlp.de/mapbender/php/mod_inspireDownloadFeed.php?id=b7f3e7fd-48cb-a886-d4fa-35542de49288&type=DATASET&generateFrom=wmslayer&layerid=36536",
                		"service_resource_name": null,
                		"error_messages": []
                	}, {
                		"service_type": "download",
                		"service_version": "predefined ATOM",
                		"possible_dataset_type": "vector",
                		"access_uri": "https://www.geoportal.rlp.de/mapbender/php/mod_inspireDownloadFeed.php?id=b7f3e7fd-48cb-a886-d4fa-35542de49288&type=DATASET&generateFrom=wfs&wfsid=226&featuretypeid=2217",
                		"service_resource_name": null,
                		"error_messages": []
                	}, {
                		"service_type": "view",
                		"service_version": "OGC:WMS 1.1.1",
                		"possible_dataset_type": "raster",
                		"access_uri": "https://www.geoportal.rlp.de/mapbender/php/wms.php?inspire=1&layer_id=36536&withChilds=1&REQUEST=GetCapabilities&SERVICE=WMS",
                		"service_resource_name": "Landesstrassen",
                		"error_messages": []
                	}, {
                		"service_type": "oaf",
                		"service_version": null,
                		"possible_dataset_type": "vector",
                		"access_uri": "https://www.geoportal.rlp.de/spatial-objects/226/collections/Landesstrassen",
                		"service_resource_name": null,
                		"error_messages": []
                	}],
                	"time_to_resolve_services": "0.6365325450897217"
                }, {
                	"spatial_dataset_identifier": "https://registry.gdi-de.org/id/de.rp.vermkv/79d8b001-972f-dc45-33ea-7d50113d4377",
                	"time_to_resolve_dataset": "0.38623833656311035",
                	"error_messages": [],
                	"title": "Gemarkungen RLP",
                	"fileidentifier": "79d8b001-972f-dc45-33ea-7d50113d4377",
                	"format": "Database",
                	"epsg_id": 25832,
                	"services": [{
                		"service_type": "download",
                		"service_version": "predefined ATOM",
                		"possible_dataset_type": null,
                		"access_uri": null,
                		"service_resource_name": null,
                		"error_messages": ["ATOM Feed: No link to dataset feed for Spatial Dataset Identifier found in service feed", "Service is not usable for downloading dataset"]
                	}, {
                		"service_type": "view",
                		"service_version": "OGC:WMS 1.1.1",
                		"possible_dataset_type": "raster",
                		"access_uri": "https://www.geoportal.rlp.de/mapbender/php/wms.php?inspire=1&layer_id=48288&withChilds=1&REQUEST=GetCapabilities&SERVICE=WMS",
                		"service_resource_name": "Gemarkungen",
                		"error_messages": []
                	}, {
                		"service_type": "oaf",
                		"service_version": null,
                		"possible_dataset_type": "vector",
                		"access_uri": "https://www.geoportal.rlp.de/spatial-objects/314/collections/vermkv:gemarkungen_rlp",
                		"service_resource_name": null,
                		"error_messages": []
                	}],
                	"time_to_resolve_services": "1.0210418701171875"
                }, {
                	"spatial_dataset_identifier": "http://www.lbm.rlp.de/registry/spatial/dataset/d4e949a9-d7a2-2050-e018-41ca97bdf11f",
                	"time_to_resolve_dataset": "0.5279743671417236",
                	"error_messages": [],
                	"title": "Bundesstra\u00dfen",
                	"fileidentifier": "d4e949a9-d7a2-2050-e018-41ca97bdf11f",
                	"format": "Database",
                	"epsg_id": 31466,
                	"services": [{
                		"service_type": "view",
                		"service_version": "OGC:WMS 1.1.1",
                		"possible_dataset_type": "raster",
                		"access_uri": "https://www.geoportal.rlp.de/mapbender/php/wms.php?inspire=1&layer_id=36535&withChilds=1&REQUEST=GetCapabilities&SERVICE=WMS",
                		"service_resource_name": "Bundesstrassen",
                		"error_messages": []
                	}, {
                		"service_type": "download",
                		"service_version": "predefined ATOM",
                		"possible_dataset_type": "raster",
                		"access_uri": "https://www.geoportal.rlp.de/mapbender/php/mod_inspireDownloadFeed.php?id=d4e949a9-d7a2-2050-e018-41ca97bdf11f&type=DATASET&generateFrom=wmslayer&layerid=36535",
                		"service_resource_name": null,
                		"error_messages": []
                	}, {
                		"service_type": "download",
                		"service_version": "predefined ATOM",
                		"possible_dataset_type": "vector",
                		"access_uri": "https://www.geoportal.rlp.de/mapbender/php/mod_inspireDownloadFeed.php?id=d4e949a9-d7a2-2050-e018-41ca97bdf11f&type=DATASET&generateFrom=wfs&wfsid=226&featuretypeid=2219",
                		"service_resource_name": null,
                		"error_messages": []
                	}, {
                		"service_type": "oaf",
                		"service_version": null,
                		"possible_dataset_type": "vector",
                		"access_uri": "https://www.geoportal.rlp.de/spatial-objects/226/collections/Bundesstrassen",
                		"service_resource_name": null,
                		"error_messages": []
                	}],
                	"time_to_resolve_services": "0.6075513362884521"
                }, {
                	"spatial_dataset_identifier": "https://lfu.rlp.de/8f35aa4febb687d285f2bbaacad26e19",
                	"time_to_resolve_dataset": "0.3138265609741211",
                	"error_messages": ["Metadata could not be found in catalogue!"]
                }, {
                	"spatial_dataset_identifier": "https://registry.gdi-de.org/id/de.rp.vermkv/a697f376-66fb-44a1-7881-2445b83efe3e",
                	"time_to_resolve_dataset": "0.35837459564208984",
                	"error_messages": [],
                	"title": "Gemeinden RLP",
                	"fileidentifier": "a697f376-66fb-44a1-7881-2445b83efe3e",
                	"format": "Database",
                	"epsg_id": 25832,
                	"services": [{
                		"service_type": "download",
                		"service_version": "predefined ATOM",
                		"possible_dataset_type": "vector",
                		"access_uri": "https://www.geoportal.rlp.de/mapbender/php/mod_inspireDownloadFeed.php?id=a697f376-66fb-44a1-7881-2445b83efe3e&type=DATASET&generateFrom=wfs&wfsid=314&featuretypeid=2637",
                		"service_resource_name": null,
                		"error_messages": []
                	}, {
                		"service_type": "oaf",
                		"service_version": null,
                		"possible_dataset_type": "vector",
                		"access_uri": "https://www.geoportal.rlp.de/spatial-objects/314/collections/vermkv:gemeinde_rlp",
                		"service_resource_name": null,
                		"error_messages": []
                	}, {
                		"service_type": "oaf",
                		"service_version": null,
                		"possible_dataset_type": "vector",
                		"access_uri": "https://www.geoportal.rlp.de/spatial-objects/511/collections/vermkv:gemeinde_rlp",
                		"service_resource_name": null,
                		"error_messages": []
                	}, {
                		"service_type": "view",
                		"service_version": "OGC:WMS 1.1.1",
                		"possible_dataset_type": "raster",
                		"access_uri": "https://www.geoportal.rlp.de/mapbender/php/wms.php?inspire=1&layer_id=45451&withChilds=1&REQUEST=GetCapabilities&SERVICE=WMS",
                		"service_resource_name": "Gemeinden",
                		"error_messages": []
                	}],
                	"time_to_resolve_services": "0.6428344249725342"
                }, {
                	"spatial_dataset_identifier": "http://www.lgb-rlp.de/registry/spatial/dataset/010fa400-b1ef-30ee-71df-c3c42e614292",
                	"time_to_resolve_dataset": "0.35459041595458984",
                	"error_messages": [],
                	"title": "Erdbebenereignisse",
                	"fileidentifier": "010fa400-b1ef-30ee-71df-c3c42e614292",
                	"format": "database",
                	"epsg_id": 31467,
                	"services": [{
                		"service_type": "view",
                		"service_version": "OGC:WMS 1.1.1",
                		"possible_dataset_type": "raster",
                		"access_uri": "https://www.geoportal.rlp.de/mapbender/php/wms.php?inspire=1&layer_id=26217&withChilds=1&REQUEST=GetCapabilities&SERVICE=WMS",
                		"service_resource_name": "Erdbebenereignisse",
                		"error_messages": []
                	}, {
                		"service_type": "download",
                		"service_version": "predefined ATOM",
                		"possible_dataset_type": null,
                		"access_uri": null,
                		"service_resource_name": null,
                		"error_messages": ["ATOM Feed: No link to dataset feed for Spatial Dataset Identifier found in service feed", "Service is not usable for downloading dataset"]
                	}, {
                		"service_type": "oaf",
                		"service_version": null,
                		"possible_dataset_type": "vector",
                		"access_uri": "https://www.geoportal.rlp.de/spatial-objects/385/collections/Erdbebenereignisse_aktuell",
                		"service_resource_name": null,
                		"error_messages": []
                	}, {
                		"service_type": "download",
                		"service_version": "predefined ATOM",
                		"possible_dataset_type": null,
                		"access_uri": null,
                		"service_resource_name": null,
                		"error_messages": ["ATOM Feed: No link to dataset feed for Spatial Dataset Identifier found in service feed", "Service is not usable for downloading dataset"]
                	}, {
                		"service_type": "oaf",
                		"service_version": null,
                		"possible_dataset_type": "vector",
                		"access_uri": "https://www.geoportal.rlp.de/spatial-objects/385/collections/Erdbebenereignisse",
                		"service_resource_name": null,
                		"error_messages": []
                	}],
                	"time_to_resolve_services": "0.670055627822876"
                }, {
                	"spatial_dataset_identifier": "https://map1.sgdnord.rlp.de/a7ae05165abc0a6e88aaa59c5f19d299",
                	"time_to_resolve_dataset": "0.2889115810394287",
                	"error_messages": ["Metadata could not be found in catalogue!"]
                }]
                JSON;
		    //get back array of datasets - idx = spatial_dataset_identifier
		    //demo usage
		    //$ajaxResponse->setResult(json_decode($pos_list));
		    //activate for productive use
		    $ajaxResponse->setResult(json_decode($output));
		}
		break;
	case "generateCache":
	    $configuration = $ajaxResponse->getParameter("configuration");
	    //check configuration values
	    $configurationValid = false;
	    //validate spatial_dataset_identifier
	    foreach ($configuration->dataset_configuration->datasets as $spatialDataset) {
	        //$spatialDataset['resourceidentifier'];
	        $configurationValid = validateType($spatialDataset->type) && validateSpatialDatasetIdentifier($spatialDataset->resourceidentifier);
	        if ($configurationValid == false) {
	            break;
	        }
	    }
	    if ($configurationValid != false) {
	        if (validateGeojsonPolygon($configuration->area_of_interest) == false) {
	            $configurationValid = false;
	        }
	    } 
	    if (!$configurationValid) {
	        $ajaxResponse->setSuccess(false);
	        $ajaxResponse->setMessage(_mb("Some entries in json config are not valid!"));
	    } else {
	        $ajaxResponse->setSuccess(true);
	        $ajaxResponse->setMessage(_mb("Method generateCache requested."));
	        
	        $e = new mb_exception("generateCache configuration: " . "'" . json_encode($configuration) . "'");
	        //get userId
	        //get userEmail
	        $userId = Mapbender::session()->get("mb_user_id");
	        $user = new User((int)$userId);
	        if ($user->isPublic()) {
	            $ajaxResponse->setSuccess(false);
	            $ajaxResponse->setMessage(_mb("Public user is not allowed to download data."));
	            $ajaxResponse->send();
	            die();
	        } else {
	            //$ajaxResponse->setMessage(_mb("You will get an email with a link to the geopackage <" . $user->email . ">!"));
	            //$ajaxResponse->send();
	        }
	        //check if id and email are the same as from ajax call
	        $uuid = new Uuid();
	        
	        //add location for cache
	        if (defined("GPKG_ABSOLUTE_DOWNLOAD_PATH") && GPKG_ABSOLUTE_DOWNLOAD_PATH != "") {
	            $outputFolder = GPKG_ABSOLUTE_DOWNLOAD_PATH;
	        } else {
	            $outputFolder = '/tmp/';
	        }
	        $outputFilename = date('Y-m-d',time()) . "_" . $uuid . "_" . (string)$user->id;
	        $configuration->output_folder = $outputFolder;
	        $configuration->output_filename = $outputFilename;
	        $configuration->notification->email_address = $user->email;
	        $configuration->notification->subject = _mb("Your GeoPortal.rlp geopackage download has been processed");
	        if (defined("GPKG_ABSOLUTE_DOWNLOAD_URI") && GPKG_ABSOLUTE_DOWNLOAD_URI != "") {
	            $configuration->notification->text = 'Downloadlink: ';
	            $configuration->notification->text .= GPKG_ABSOLUTE_DOWNLOAD_URI . $outputFilename . ".gpkg";
	            $configuration->notification->text .= "\n";
	            $configuration->notification->text .= 'Preview (NGA Viewer): ';
	            $configuration->notification->text .= 'https://ngageoint.github.io/geopackage-viewer-js/?gpkg=' . urlencode(GPKG_ABSOLUTE_DOWNLOAD_URI . $outputFilename . ".gpkg");
	            //https://ngageoint.github.io/geopackage-viewer-js/?gpkg=
	        } else {
	           $configuration->notification->text = "https://www.geoportal.rlp.de/metadata/" . $outputFilename . ".gpkg";
	        }
	        //check values
	        //invoke python script
	        $output = exec('/usr/bin/python3.9 ../extensions/inspire-gpkg-cache/cli_invoke.py ' . "'" . json_encode($configuration) . "'" . " " . "'generateCache'" );
	        
	        //$pythonResult = system('/usr/bin/python3.9 ../extensions/inspire-gpkg-cache/cli_invoke.py ' . "'" . json_encode($configuration) . "'" . " " . "'generateCache' > /dev/null" );
	        
	        
	        //$e = new mb_exception("output of python script - method generate_cache: " .$output);
	        //$ajaxResponse->setResult(json_decode('{"result": "top"}'));
	        $ajaxResponse->setMessage(_mb("Your geopackage has been created. Please control your mailbox") . " (" . $user->email . ")");
	        //$ajaxResponse->send();
	    }
	    break;
	default :
		$ajaxResponse->setSuccess(false);
		$ajaxResponse->setMessage(_mb("No known method invoked!"));
		break;
}
$ajaxResponse->send();
?>