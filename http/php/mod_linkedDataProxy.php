<?php
require_once (dirname ( __FILE__ ) . "/../../core/globalSettings.php");
require_once (dirname ( __FILE__ ) . "/../classes/class_cache.php");
require_once (dirname ( __FILE__ ) . "/../classes/class_universal_wfs_factory.php");
require_once (dirname ( __FILE__ ) . "/../classes/class_gml_3_factory.php");
require_once (dirname ( __FILE__ ) . "/../classes/class_owsConstraints.php");
require_once (dirname ( __FILE__ ) . "/../classes/class_connector.php"); // for resolving external @context content
require_once (dirname ( __FILE__ ) . "/../classes/class_user.php");
global $rewritePath;
global $behindRewrite;
global $linkedDataProxyUrl;
global $nonceLife;
global $restrictToOpenData;
/*
 * examples:
 * get
 * http://localhost/mapbender/php/mod_linkedDataProxy.php?wfsid=19&collection=TEHG_RLP%3Atehg_anlagen_2013_gesamt&items=all&f=html
 *
 * rest
 *
 * TODO: add CORS header, add authentication - see spec: 
 * HTTP authentication, 
 * an API key (either as a header or as a query parameter),
 * OAuth2’s common flows (implicit, password, application and access code) as defined in RFC6749, and
 * OpenID Connect Discovery
 * 
 */
if (file_exists ( dirname ( __FILE__ ) . "/../../conf/linkedDataProxy.json" )) {
	$configObject = json_decode ( file_get_contents ( "../../conf/linkedDataProxy.json" ) );
}
if (isset ( $configObject ) && isset ( $configObject->memory_limit ) && $configObject->memory_limit != "") {
	ini_set ( 'memory_limit', $configObject->memory_limit );
}
if (isset ( $configObject ) && isset ( $configObject->http_method ) && ($configObject->http_method != "GET" || $configObject->http_method != "POST")) {
	$wfs_http_method = $configObject->http_method;
} else {
	$wfs_http_method = "GET";
}
if (isset ( $configObject ) && isset ( $configObject->use_internal_bootstrap ) && $configObject->use_internal_bootstrap == true) {
	$useInternalBootstrap = true;
}
if (isset ( $configObject ) && isset ( $configObject->own_css ) && $configObject->own_css != "") {
	$cssFile = $configObject->own_css;
} else {
	$cssFile = "../css/ldproxy_ia.css";
}
if (isset ( $configObject ) && isset ( $configObject->default_pages ) && $configObject->default_pages != "") {
	$limit = $configObject->default_pages;
} else {
	$limit = 10;
}
if (isset ( $configObject ) && isset ( $configObject->allowed_limits ) && is_array ( $configObject->allowed_limits )) {
	$allowedLimits = $configObject->allowed_limits;
} else {
	$allowedLimits = array (
			"1",
			"5",
			"10",
			"20",
			"50",
			"100",
			"200" 
	);
}
if (isset ( $configObject ) && isset ( $configObject->initial_bbox ) && is_array ( $configObject->initial_bbox ) && count ( $configObject->initial_bbox ) == 4) {
	$minxFC = $configObject->initial_bbox [0];
	$minyFC = $configObject->initial_bbox [1];
	$maxxFC = $configObject->initial_bbox [2];
	$maxyFC = $configObject->initial_bbox [3];
} else {
	$minxFC = 48;
	$minyFC = 6;
	$maxxFC = 51;
	$maxyFC = 9;
}
if (isset ( $configObject ) && isset ( $configObject->behind_rewrite ) && $configObject->behind_rewrite == true) {
	$behindRewrite = true;
} else {
	$behindRewrite = false;
}
if (isset ( $configObject ) && isset ( $configObject->rewrite_path ) && $configObject->rewrite_path != "") {
	$rewritePath = $configObject->rewrite_path;
} else {
	$rewritePath = "linkedDataProxy";
}
if (isset ( $configObject ) && isset ( $configObject->open_data_filter ) && $configObject->open_data_filter == true) {
    $restrictToOpenData = true;
} else {
    $restrictToOpenData = false;	
}
// textual data:
$textualDataArray = array (
		"title",
		"description",
		"datasource_url",
		"legal_notice_link",
		"privacy_notice_link",
		"map_position" 
);

$title = "Open Spatial Data served by Mapbender WFS 3.0 Proxy";
$description = "Description of the instance of Mapbender WFS 3.0 Proxy";
$datasource_url = "https://www.geoportal.rlp.de/";
$legal_notice_link = "https://www.geoportal.rlp.de/article/Impressum";
$privacy_notice_link = "https://www.geoportal.rlp.de/article/Datenschutz";
$map_position = "side";

if (! empty ( $_SERVER ['HTTPS'] )) {
	$schema = "https";
} else {
	$schema = "http";
}

$linkedDataProxyUrl = $schema . "://" . $_SERVER ['HTTP_HOST'] . "/" . $rewritePath;

if ($behindRewrite == true) {
	$cssFile = MAPBENDER_PATH . "/php/" . $cssFile;
	$imagePathReplace = MAPBENDER_PATH . "/img/"; // for license symbols exhange ../img/ with this string!
}

foreach ( $textualDataArray as $textualData ) {
	if (isset ( $configObject ) && isset ( $configObject->{$textualData} ) && $configObject->{$textualData} != "") {
		${$textualData} = $configObject->{$textualData};
	}
}
// $startmem = memory_get_usage();
// http://localhost/mapbender/devel_tests/wfsClientTest.php?wfsid=16&ft=vermkv:fluren_rlp&bbox=7.9,50.8,8.0,52
// problem: mapbender parses featuretype names from wfs <= 1.1.0 without namespaces, if they are not explecitly defined!
// better to use wfs 2.0.0 as native interface
// default page
$page = 0;
// default format f is html, also json and xml is possible - implement content negotiation
$f = "html";
// overwrite outputFormat for special headers:
// try to read out first entry!
$acceptHeaderArray = array();

if (strpos ( $_SERVER ["HTTP_ACCEPT"], ";" ) != false) {
	$formatPartOfAcceptHeader = explode ( ';', $_SERVER ["HTTP_ACCEPT"] );
	$formatPartOfAcceptHeader = $formatPartOfAcceptHeader [0];
} else {
	$formatPartOfAcceptHeader = $_SERVER ["HTTP_ACCEPT"];
}
if (strpos ( $formatPartOfAcceptHeader, "," ) != false) {
	$formatPartOfAcceptHeader = explode ( ',', $formatPartOfAcceptHeader );
	foreach ($formatPartOfAcceptHeader as $acceptedFormat) {
		$acceptHeaderArray[] = trim($acceptedFormat);
	}
} else {
	$acceptHeaderArray[0] = $_SERVER ["HTTP_ACCEPT"];
}
// $e = new mb_exception("php/mod_linkedDataProxy.php: first found format: ".$formatPartOfAcceptHeader);
// TODO: check all given formats in header an choose the right one 
$acceptedHeaderFormatArray = array();
foreach ($acceptHeaderArray as $acceptHeaderFomat) {
	if (in_array ( $acceptHeaderFomat, array (
			"application/xml",
			"text/xml",
			"text/xml; subtype=gml/3.1.1",
			"text/xml; subtype=gml/3.2",
			"text/xml; subtype=gml/2.1.2",
			"text/xml; subtype=gml/3.2.1"
	) )) {
		$acceptedHeaderFormatArray[] = "xml";
	}
	if (in_array ( $acceptHeaderFomat, array (
			"application/geo+json",
			"application/openapi+json;version=3.0",
			"application/json",
			"application/json; subtype=geojsontext/xml",
			"text/json"
	) )) {
		$acceptedHeaderFormatArray[] = "json";
	}
	if (in_array ( $acceptHeaderFomat, array (
			"text/html"
	) )) {
		$acceptedHeaderFormatArray[] = "html";
	}
}
if (is_array($acceptedHeaderFormatArray) && count($acceptedHeaderFormatArray) > 0) {
	$f = $acceptedHeaderFormatArray[0];
} else {
	//$f = "html"; //default value
}
/*
 * For debugging purposes only
 */
// $e = new mb_exception("php/mod_linkedDataProxy.php: HTTP ACCEPT HEADER found: ".$_SERVER ["HTTP_ACCEPT"]);
// $e = new mb_exception("php/mod_linkedDataProxy.php: REQUEST PARAMETER: ".json_encode($_REQUEST));
// $e = new mb_exception("php/mod_linkedDataProxy.php: requested format: ".$f);

// parameter to control if the native json should be requested from the server, if support for geojson is available!
$nativeJson = false;
// default outputFormat for wfs objects:
$outputFormat = "text/xml";
$outputFormat = "text/xml; subtype=gml/3.1.1";
// outputFormat whitelist
$allowedOutputFormats = array (
		"text/xml; subtype=gml/3.1.1",
		"text/xml; subtype=gml/3.2",
		"text/xml; subtype=gml/2.1.2",
		"text/xml; subtype=gml/3.2.1",
		"SHAPEZIP",
		"application/json; subtype=geojson",
		"application/openapi+json;version=3.0",
		"text/csv",
		"application/zip" 
);
// outputFormat for pages - parameter f=...
$allowedFormats = array (
		"html",
		"xml",
		"json" 
);
//
$newline = " ";
// for digest authentication
// function to get relevant user information from mb db
function getUserInfo($mbUsername, $mbEmail) {
	$result = array();
	if (preg_match('#[@]#', $mbEmail)) {
		$sql = "SELECT mb_user_id, mb_user_digest, mb_user_password, password FROM mb_user where mb_user_name = $1 AND mb_user_email = $2";
		$v = array($mbUsername, $mbEmail);
		$t = array("s", "s");
	} else {
		$sql = "SELECT mb_user_id, mb_user_aldigest As mb_user_digest, mb_user_password, password FROM mb_user where mb_user_name = $1";
		$v = array($mbUsername);
		$t = array("s");
	}
	$res = db_prep_query($sql, $v, $t);
	if (!($row = db_fetch_array($res))) {
		$result[0] = "-1";
	} else {
		$result[0] = $row['mb_user_id'];
		$result[1] = $row['mb_user_digest'];
		$result[2] = $row['mb_user_password'];
		$result[3] = $row['password'];
	}
	return $result;
}
function http_digest_parse($txt) {
	// protect against missing data
	$needed_parts = array('nonce' => 1, 'nc' => 1, 'cnonce' => 1, 'qop' => 1, 'username' => 1, 'uri' => 1, 'response' => 1);
	$data = array();
	$keys = implode('|', array_keys($needed_parts));
	preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);
	foreach ($matches as $m) {
		$data[$m[1]] = $m[3] ? $m[3] : $m[4];
		unset($needed_parts[$m[1]]);
	}
	return $needed_parts ? false : $data;
}
function getNonce() {
	global $nonceLife;
	$time = ceil(time() / $nonceLife) * $nonceLife;
	return md5(date('Y-m-d H:i', $time) . ':' . $_SERVER['REMOTE_ADDR'] . ':' . NONCEKEY);
}
function microtime_float() {
	list ( $usec, $sec ) = explode ( " ", microtime () );
	return (( float ) $usec + ( float ) $sec);
}
// outputFormatter for attribute values - urls, ...
function string2html($string) {
	if (filter_var ( $string, FILTER_VALIDATE_URL )) {
		return "<a href='" . $string . "' target='_blank'>" . $string . "</a>";
	}
	return $string;
}
function delTotalFromQuery($paramName, $url) {
	$query = explode ( "?", $url );
	parse_str ( $query [1], $vars );
	if (is_array ( $paramName )) {
		foreach ( $paramName as $param ) {
			unset ( $vars [$param] );
		}
	} else {
		unset ( $vars [$paramName] );
	}
	$urlNew = $query [0] . "?" . http_build_query ( $vars );
	return $urlNew;
}
function getJsonSchemaObject($feature) {
	// $e = new mb_exception("php/mod_linkedDataProxy.php - getJsonSchemaObject");
	$returnObject = new stdClass ();
	$returnObject->success = false;
	$cache = new Cache ();
	$url = $feature->properties->{'json-schema_0.7_id'};
	if (isset ( $url )) {
		// $e = new mb_exception("php/mod_linkedDataProxy.php - getJsonSchemaObject - url is set");
		// cache schema resolving!
		if ($cache->isActive) {
			// $e = new mb_exception("php/mod_linkedDataProxy.php - cache is active!");
			if ($cache->cachedVariableExists ( md5 ( $url ) ) == false) {
				$schemaContextConnector = new Connector ();
				$file = $schemaContextConnector->load ( $url );
				$returnObject->schema = json_decode ( $file );
				if ($returnObject->schema == false) {
					$returnObject->success = false;
				} else {
					$returnObject->success = true;
					$cache->cachedVariableAdd ( md5 ( $url ), $returnObject );
				}
			} else {
				// $e = new mb_exception("php/mod_linkedDataProxy.php - read json-schema from cache!");
				$returnObject = $cache->cachedVariableFetch ( md5 ( $url ) );
			}
		} else {
			//$e = new mb_exception ( "php/mod_linkedDataProxy.php - cache is inactive" );
			$schemaContextConnector = new Connector ();
			$file = $schemaContextConnector->load ( $url );
			$returnObject->schema = json_decode ( $file );
			if ($returnObject->schema == false) {
				$returnObject->success = false;
			} else {
				$returnObject->success = true;
			}
			$returnObject->url = $url;
		}
		return $returnObject;
	} else {
		return $returnObject;
	}
}
function mapFeatureKeys($featureList, $schemaObject) {
	$featureListNew = array();
	foreach ( $featureList as $feature ) {
		$featureNew = array();
		foreach ( $feature->properties as $key => $value ) {
			if (isset ( $schemaObject->properties->{$key}->title )) {
				$attributeTitle = $schemaObject->properties->{$key}->title;
			} else {
				$attributeTitle = $key;
			}
			$featureNew[$attributeTitle] = $value;
			/*if (isset ( $schemaObject->properties->{$key}->description )) {
				$attributeDescription = $schemaObject->properties->{$key}->description;
			} else {
				$attributeDescription = $attributeTitle;
			}*/
		}	
		$featureListNew[] = $featureNew;
	}
	return $featureListNew;
}
function getJsonLdObject($feature) {
	$returnObject = new stdClass ();
	$returnObject->success = false;
	$url = $feature->properties->{'json-ld_1.1_context'};
	if (isset ( $url )) {
		$schemaContextConnector = new Connector ();
		$file = $schemaContextConnector->load ( $url );
		$returnObject->schema = json_decode ( $file );
		if ($returnObject->schema == false) {
			$returnObject->success = false;
		} else {
			$returnObject->success = true;
		}
		$returnObject->url = $url;
		return $returnObject;
	} else {
		return $returnObject;
	}
}
function getOpenApi3JsonComponentTemplate() {
	$openApi3JsonComponent = <<<JSON
{  "components" : {
    "schemas" : {
      "exception" : {
        "required" : [ "code" ],
        "type" : "object",
        "properties" : {
          "code" : {
            "type" : "string"
          },
          "description" : {
            "type" : "string"
          }
        }
      },
      "root" : {
        "required" : [ "links" ],
        "type" : "object",
        "properties" : {
          "links" : {
            "type" : "array",
            "example" : [ {
              "href" : "http://data.example.org/",
              "rel" : "self",
              "type" : "application/json",
              "title" : "this document"
            }, {
              "href" : "http://data.example.org/api",
              "rel" : "service-desc",
              "type" : "application/openapi+json;version=3.0",
              "title" : "the API definition"
            }, {
              "href" : "http://data.example.org/conformance",
              "rel" : "conformance",
              "type" : "application/json",
              "title" : "WFS 3.0 conformance classes implemented by this server"
            }, {
              "href" : "http://data.example.org/collections",
              "rel" : "data",
              "type" : "application/json",
              "title" : "Metadata about the feature collections"
            } ],
            "items" : {
              "\$ref" : "#/components/schemas/link"
            }
          }
        }
      },
      "req-classes" : {
        "required" : [ "conformsTo" ],
        "type" : "object",
        "properties" : {
          "conformsTo" : {
            "type" : "array",
            "example" : [ "http://www.opengis.net/spec/wfs-1/3.0/req/core", "http://www.opengis.net/spec/wfs-1/3.0/req/oas30", "http://www.opengis.net/spec/wfs-1/3.0/req/html", "http://www.opengis.net/spec/wfs-1/3.0/req/geojson" ],
            "items" : {
              "type" : "string"
            }
          }
        }
      },
      "link" : {
        "required" : [ "href" ],
        "type" : "object",
        "properties" : {
          "href" : {
            "type" : "string",
            "example" : "http://data.example.com/buildings/123"
          },
          "rel" : {
            "type" : "string",
            "example" : "prev"
          },
          "type" : {
            "type" : "string",
            "example" : "application/geo+json"
          },
          "hreflang" : {
            "type" : "string",
            "example" : "en"
          }
        }
      },
      "content" : {
        "required" : [ "collections", "links" ],
        "type" : "object",
        "properties" : {
          "links" : {
            "type" : "array",
            "example" : [ {
              "href" : "http://data.example.org/collections.json",
              "rel" : "self",
              "type" : "application/json",
              "title" : "this document"
            }, {
              "href" : "http://data.example.org/collections.html",
              "rel" : "alternate",
              "type" : "text/html",
              "title" : "this document as HTML"
            }, {
              "href" : "http://schemas.example.org/1.0/foobar.xsd",
              "rel" : "describedBy",
              "type" : "application/xml",
              "title" : "XML schema for Acme Corporation data"
            } ],
            "items" : {
              "\$ref" : "#/components/schemas/link"
            }
          },
          "collections" : {
            "type" : "array",
            "items" : {
              "\$ref" : "#/components/schemas/collectionInfo"
            }
          }
        }
      },
      "collectionInfo" : {
        "required" : [ "links", "name" ],
        "type" : "object",
        "properties" : {
          "name" : {
            "type" : "string",
            "description" : "identifier of the collection used, for example, in URIs",
            "example" : "buildings"
          },
          "title" : {
            "type" : "string",
            "description" : "human readable title of the collection",
            "example" : "Buildings"
          },
          "description" : {
            "type" : "string",
            "description" : "a description of the features in the collection",
            "example" : "Buildings in the city of Bonn."
          },
          "links" : {
            "type" : "array",
            "example" : [ {
              "href" : "http://data.example.org/collections/buildings/items",
              "rel" : "item",
              "type" : "application/geo+json",
              "title" : "Buildings"
            }, {
              "href" : "http://example.com/concepts/buildings.html",
              "rel" : "describedBy",
              "type" : "text/html",
              "title" : "Feature catalogue for buildings"
            } ],
            "items" : {
              "\$ref" : "#/components/schemas/link"
            }
          },
          "extent" : {
            "\$ref" : "#/components/schemas/extent"
          },
          "crs" : {
            "type" : "array",
            "description" : "The coordinate reference systems in which geometries may be retrieved. Coordinate reference systems are identified by a URI. The first coordinate reference system is the coordinate reference system that is used by default. This is always http://www.opengis.net/def/crs/OGC/1.3/CRS84, i.e. WGS84 longitude/latitude.",
            "example" : [ "http://www.opengis.net/def/crs/OGC/1.3/CRS84", "http://www.opengis.net/def/crs/EPSG/0/4326" ],
            "items" : {
              "type" : "string"
            },
            "default" : [ "http://www.opengis.net/def/crs/OGC/1.3/CRS84" ]
          },
          "relations" : {
            "type" : "object",
            "description" : "Related collections that may be retrieved for this collection",
            "example" : "{'id': 'label'}"
          }
        }
      },
      "extent" : {
        "required" : [ "spatial" ],
        "type" : "object",
        "properties" : {
          "crs" : {
            "type" : "string",
            "description" : "Coordinate reference system of the coordinates in the spatial extent (property spatial). In the Core, only WGS84 longitude/latitude is supported. Extensions may support additional coordinate reference systems.",
            "enum" : [ "http://www.opengis.net/def/crs/OGC/1.3/CRS84" ],
            "default" : "http://www.opengis.net/def/crs/OGC/1.3/CRS84"
          },
          "spatial" : {
            "maxItems" : 6,
            "minItems" : 4,
            "type" : "array",
            "description" : "West, north, east, south edges of the spatial extent. The minimum and maximum values apply to the coordinate reference system WGS84 longitude/latitude that is supported in the Core. If, for example, a projected coordinate reference system is used, the minimum and maximum values need to be adjusted.",
            "example" : [ -180, -90, 180, 90 ],
            "items" : {
              "type" : "number"
            }
          }
        }
      },
      "featureCollectionGeoJSON" : {
        "required" : [ "features", "type" ],
        "type" : "object",
        "properties" : {
          "type" : {
            "type" : "string",
            "enum" : [ "FeatureCollection" ]
          },
          "features" : {
            "type" : "array",
            "items" : {
              "\$ref" : "#/components/schemas/featureGeoJSON"
            }
          },
          "links" : {
            "type" : "array",
            "items" : {
              "\$ref" : "#/components/schemas/link"
            }
          },
          "timeStamp" : {
            "type" : "string",
            "format" : "dateTime"
          },
          "numberMatched" : {
            "minimum" : 0,
            "type" : "integer"
          },
          "numberReturned" : {
            "minimum" : 0,
            "type" : "integer"
          }
        }
      },
      "featureGeoJSON" : {
        "required" : [ "geometry", "properties", "type" ],
        "type" : "object",
        "properties" : {
          "type" : {
            "type" : "string",
            "enum" : [ "Feature" ]
          },
          "geometry" : {
            "\$ref" : "#/components/schemas/geometryGeoJSON"
          },
          "properties" : {
            "type" : "object",
            "nullable" : true
          },
          "id" : {
            "oneOf" : [ {
              "type" : "string"
            }, {
              "type" : "integer"
            } ]
          }
        }
      },
      "geometryGeoJSON" : {
        "required" : [ "type" ],
        "type" : "object",
        "properties" : {
          "type" : {
            "type" : "string",
            "enum" : [ "Point", "MultiPoint", "LineString", "MultiLineString", "Polygon", "MultiPolygon", "GeometryCollection" ]
          }
        }
      }
    },
    "parameters" : {
      "f" : {
        "name" : "f",
        "in" : "query",
        "description" : "The format of the response. If no value is provided, the standard http rules apply, i.e., the accept header shall be used to determine the format. Pre-defined values are xml, json and html. The response to other  values is determined by the server.",
        "required" : false,
        "style" : "form",
        "explode" : false,
        "schema" : {
          "type" : "string",
          "enum" : [ "json", "xml", "html" ]
        },
        "example" : "json"
      },
      "limit" : {
        "name" : "limit",
        "in" : "query",
        "description" : "The optional limit parameter limits the number of items that are presented in the response document.Only items are counted that are on the first level of the collection in the response document.  Nested objects contained within the explicitly requested items shall not be counted.Minimum = 1. Maximum = 10000. Default = 10.",
        "required" : false,
        "style" : "form",
        "explode" : false,
        "schema" : {
          "maximum" : 10000,
          "minimum" : 1,
          "type" : "integer",
          "default" : 10
        },
        "example" : 10
      },
      "offset" : {
        "name" : "offset",
        "in" : "query",
        "description" : "The optional offset parameter indicates the index within the result set from which the server shall begin presenting results in the response document. The first element has an index of 0. Minimum = 0. Default = 0.",
        "required" : false,
        "style" : "form",
        "explode" : false,
        "schema" : {
          "minimum" : 0,
          "type" : "integer",
          "default" : 0
        },
        "example" : 0
      },
      "bbox" : {
        "name" : "bbox",
        "in" : "query",
        "description" : "Only features that have a geometry that intersects the bounding box are selected. The bounding box is provided as four or six numbers, depending on whether the coordinate reference system includes a vertical axis (elevation or depth):   * Lower left corner, coordinate axis 1 * Lower left corner, coordinate axis 2 * Lower left corner, coordinate axis 3 (optional) * Upper right corner, coordinate axis 1 * Upper right corner, coordinate axis 2 * Upper right corner, coordinate axis 3 (optional)  The coordinate reference system of the values is WGS84 longitude/latitude (http://www.opengis.net/def/crs/OGC/1.3/CRS84) unless a different coordinate reference system is specified in the parameter `bbox-crs`.  For WGS84 longitude/latitude the values are in most cases the sequence of minimum longitude, minimum latitude, maximum longitude and maximum latitude. However, in cases where the box spans the antimeridian the first value (west-most box edge) is larger than the third value (east-most box edge).  If a feature has multiple spatial geometry properties, it is the decision of the server whether only a single spatial geometry property is used to determine the extent or all relevant geometries.",
        "required" : false,
        "style" : "form",
        "explode" : false,
        "schema" : {
          "maxItems" : 6,
          "minItems" : 4,
          "type" : "array",
          "items" : {
            "type" : "number"
          }
        }
      },
      "time" : {
        "name" : "time",
        "in" : "query",
        "description" : "Either a date-time or a period string that adheres to RFC 3339. Examples:  * A date-time: 2018-02-12T23:20:50Z * A period: 2018-02-12T00:00:00Z/2018-03-18T12:31:12Z or \"2018-02-12T00:00:00Z/P1M6DT12H31M12S  Only features that have a temporal property that intersects the value of `time` are selected.  If a feature has multiple temporal properties, it is the decision of the server whether only a single temporal property is used to determine the extent or all relevant temporal properties.",
        "required" : false,
        "style" : "form",
        "explode" : false,
        "schema" : {
          "type" : "string"
        }
      },
      "resultType" : {
        "name" : "resultType",
        "in" : "query",
        "description" : "This service will respond to a query in one of two ways (excluding an exception response). It may either generate a complete response document containing resources that satisfy the operation or it may simply generate an empty response container that indicates the count of the total number of resources that the operation would return. Which of these two responses is generated is determined by the value of the optional resultType parameter. The allowed values for this parameter are results and hits. If the value of the resultType parameter is set to \"results\", the server will generate a complete response document containing resources that satisfy the operation. If the value of the resultType attribute is set to hits, the server will generate an empty response document containing no resource instances. Default = results.",
        "required" : false,
        "style" : "form",
        "explode" : false,
        "schema" : {
          "type" : "string",
          "enum" : [ "hits", "results" ],
          "default" : "results"
        },
        "example" : "results"
      },
      "featureId" : {
        "name" : "featureId",
        "in" : "path",
        "description" : "Local identifier of a specific feature",
        "required" : true,
        "schema" : {
          "type" : "string"
        }
      },
      "properties" : {
        "name" : "properties",
        "in" : "query",
        "description" : "The properties that should be included for each feature. The parameter value is a comma-separated list of property names.",
        "required" : false,
        "style" : "form",
        "explode" : false,
        "schema" : {
          "type" : "array",
          "items" : {
            "type" : "string"
          }
        }
      }
    }
  }
}
JSON;
	return $openApi3JsonComponent;
}

// TODO - built function to map get parameters back to combined rest/get uri
// wfsid=...&collection=....&item=...&f=html -> /linkedDataProxy/{wfsid}/collections/{collectionId}/items/{itemId}?f=html
// uri of proxy have to be given absolute? -

// http://localhost/linkedDataProxy/19/collections/TEHG_RLP%3Atehg_anlagen_2013_gesamt/items
// http://localhost/linkedDataProxy/19/
// preg_grep()
function get2Rest($requestString) {
	global $linkedDataProxyUrl;
	global $behindRewrite;
	// return $requestString;
	if ($behindRewrite == true) {
		$e = new mb_notice ( "php/mod_linkedDataProxy.php function get2Rest string to exchange: " . $requestString );
		// get query part:
		if (strpos ( $requestString, "?" ) !== false) {
			$queryPartArray = explode ( "?", $requestString );
			$queryString = $queryPartArray [1];
		} else {
			$queryString = $requestString;
		}
		$e = new mb_notice ( "php/mod_linkedDataProxy.php function get2Rest found query: " . $queryString );
		// map queryString to rest url
		parse_str ( $queryString, $requestArray );
		$e = new mb_notice ( "php/mod_linkedDataProxy.php function get2Rest array of get parameters: " . json_encode ( $requestArray ) );
		// initialize new api path
		$apiPath = ''; // would be relative
		               // parts of api to extract from query
		$apiParams = array (
				"wfsid",
				"collections",
				"collection",
				"items",
				"item" 
		);
		// build path from params
		if (isset ( $requestArray ['wfsid'] ) && $requestArray ['wfsid'] != "") {
			$apiPath .= "/" . $requestArray ['wfsid'] . "";
		}
		/*
		 * if (isset($requestArray['getapidescription']) && $requestArray['getapidescription'] == "true") {
		 * $apiPath .= "/api/";
		 * }
		 */
		if ($requestArray ['collections'] == "api") {
			$apiPath .= "/api";
		}
		if (isset ( $requestArray ['collection'] ) && $requestArray ['collection'] != "") {
			if ($requestArray ['collection'] == "all") {
				$apiPath .= "/collections";
			} else {
				$apiPath .= "/collections/" . $requestArray ['collection'] . "";
			}
		}
		if (isset ( $requestArray ['items'] ) && $requestArray ['items'] != "") {
			if ($requestArray ['items'] == "all") {
				$apiPath .= "/items";
			}
		}
		if (isset ( $requestArray ['item'] ) && $requestArray ['item'] != "") {
			$apiPath .= "/items/" . $requestArray ['item'];
		}
		// remove all $apiParams from initial requestArray
		foreach ( $apiParams as $apiParamName ) {
			unset ( $requestArray [$apiParamName] );
		}
		// unset empty request elements - relicts?
		foreach ( $requestArray as $key => $value ) {
			if ($value == "") {
				unset ( $requestArray [$key] );
			}
		}
		$e = new mb_notice ( "php/mod_linkedDataProxy.php function get2Rest extracted api path: " . $apiPath );
		$e = new mb_notice ( "php/mod_linkedDataProxy.php function get2Rest further get parameters to add to api path: " . json_encode ( $requestArray ) );
		// build new url from proxyPath, apiPath and the further get parameters
		if ($apiPath == "" && http_build_query ( $requestArray ) == "") {
			$newUrl = $linkedDataProxyUrl . "/";
		} else {
			$newUrl = $linkedDataProxyUrl . $apiPath . "?" . ltrim ( http_build_query ( $requestArray ), "&" );
		}
		$e = new mb_notice ( "php/mod_linkedDataProxy.php function get2Rest new absolute url for href: " . $newUrl );
		return str_replace ( "?&", "?", rtrim ( $newUrl, "?" ) );
	} else {
		return $requestString;
	}
}

// first get request parameter for api - if invoked behind apache2 mod_rewrite to built REST
if (isset ( $_REQUEST ["api"] ) && $_REQUEST ["api"] != "") {
	$e = new mb_notice ( "php/mod_linkedDataProxy.php try to read GET parameters from api: " . $_REQUEST ["api"] );
	// first get whole request "api" divide it by ? to distinguish query parameters from rest uris
	if (strpos ( $_REQUEST ["api"], "?" ) !== false) {
		$pathArray = explode ( '?', $_REQUEST ["api"] );
		$restPath = $pathArray [0];
		$queryString = $pathArray [1];
		// TODO foreach $queryString object generate one $_REQUEST[] variable - if allowed!!!!! - make a lookup table
	} else {
		$restPath = $_REQUEST ["api"];
	}
	// parse api - split by / - template: {wfsid}/collection/{collectionId}/items/{itemId}
	$requestParams = explode ( "/", $restPath );
	// array to store the request params from rest api - merge them afterward to get right request_uri!
	$apiParamsArray = array ();
	switch (count ( $requestParams )) {
		case "1" :
			$_REQUEST ["wfsid"] = $requestParams [0];
			// $apiParamsArray["wfsid"] = $requestParams[0];
			break;
		case "2" :
			if ($requestParams [1] == "api") {
				$_REQUEST ["wfsid"] = $requestParams [0];
				$_REQUEST ["collections"] = "api";
			} else {
				if ($requestParams [1] == "collections") {
					$_REQUEST ["wfsid"] = $requestParams [0];
					$_REQUEST ["collections"] = "all";
				} else {
					echo 'URI not valid! {wfsid}/collection or {wfsid}/api<br/>';
					die ();
				}
			}
			break;
		case "3" :
			if ($requestParams [1] == "collections") {
				$_REQUEST ["wfsid"] = $requestParams [0];
				$_REQUEST ["collection"] = $requestParams [2];
				$_REQUEST ["items"] = "all";
			} else {
				echo 'URI not valid! {wfsid}/collections/{collectionId} <br/>';
				die ();
			}
			break;
		case "4" :
			if ($requestParams [1] == "collections" && $requestParams [3] == "items") {
				$_REQUEST ["wfsid"] = $requestParams [0];
				$_REQUEST ["collection"] = $requestParams [2];
				$_REQUEST ["items"] = "all";
			} else {
				echo 'URI not valid! {wfsid}/collections/{collectionId}/items <br/>';
				die ();
			}
			break;
		case "5" :
			if ($requestParams [1] == "collections" && $requestParams [3] == "items") {
				$_REQUEST ["wfsid"] = $requestParams [0];
				$_REQUEST ["collection"] = $requestParams [2];
				$_REQUEST ["item"] = $requestParams [4];
			} else {
				echo 'URI not valid! {wfsid}/collections/{collectionId}/items/{itemId} <br/>';
				die ();
			}
			break;
	}
}

// TODO - built function to map get parameters back to combined rest/get uri
// wfsid=...&collection=....&item=...&f=html -> /linkedDataProxy/{wfsid}/collections/{collectionId}/items/{itemId}?f=html

// parse request parameters
if (isset ( $_REQUEST ["wfsid"] ) & $_REQUEST ["wfsid"] != "") {
	// validate to csv integer list
	$testMatch = $_REQUEST ["wfsid"];
	$pattern = '/^[\d,]*$/';
	if (! preg_match ( $pattern, $testMatch )) {
		// echo 'id: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>wfsid</b> is not valid (integer or cs integer list).<br/>';
		die ();
	}
	$wfsid = $testMatch;
	$testMatch = NULL;
}
if (isset ( $_REQUEST ["fid"] ) & $_REQUEST ["fid"] != "") {
	// validate to csv integer list
	$testMatch = $_REQUEST ["fid"];
	$pattern = '/^[0-9a-zA-Z\.\-_:]*$/';
	if (! preg_match ( $pattern, $testMatch )) {
		// echo 'id: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>id</b> is not valid (integer or cs integer list).<br/>';
		die ();
	}
	$fid = $testMatch;
	$testMatch = NULL;
}
if (isset ( $_REQUEST ["p"] ) & $_REQUEST ["p"] != "") {
	// validate to csv integer list
	$testMatch = $_REQUEST ["p"];
	$pattern = '/^[\d]*$/';
	if (! preg_match ( $pattern, $testMatch )) {
		// echo 'id: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>p</b> is not valid (integer).<br/>';
		die ();
	}
	$page = $testMatch;
	$testMatch = NULL;
}
// alternate for page - limit offset
if (isset ( $_REQUEST ["limit"] ) & $_REQUEST ["limit"] != "") {
	// validate to csv integer list
	$testMatch = $_REQUEST ["limit"];
	if (! in_array ( $testMatch, $allowedLimits )) {
		echo 'Parameter <b>limit</b> is not valid - must be one of: ' . implode ( ',', $allowedLimits ) . '<br/>';
		die ();
	}
	$limit = ( integer ) $testMatch;
	$testMatch = NULL;
}
if (isset ( $_REQUEST ["offset"] ) & $_REQUEST ["offset"] != "") {
	// validate to csv integer list
	$testMatch = $_REQUEST ["offset"];
	$pattern = '/^[\d]*$/';
	if (! preg_match ( $pattern, $testMatch )) {
		// echo 'id: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>offset</b> is not valid (integer).<br/>';
		die ();
	}
	$offset = ( integer ) $testMatch;
	$testMatch = NULL;
}
if (isset ( $_REQUEST ["collection"] ) & $_REQUEST ["collection"] != "") {
	// validate to csv integer list
	$testMatch = $_REQUEST ["collection"];
	$pattern = '/^[0-9a-zA-Z\.\-
_:]*$/';
	if (! preg_match ( $pattern, $testMatch )) {
		// echo 'id: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>collection</b> is not valid (ogc resource name or id).<br/>';
		die ();
	}
	$collection = $testMatch;
	$testMatch = NULL;
}
if (isset ( $_REQUEST ["collections"] ) & $_REQUEST ["collections"] != "") {
	// validate to csv integer list
	$testMatch = $_REQUEST ["collections"];
	if (! in_array ( $testMatch, array (
			"all",
			"api" 
	) )) {
		echo 'Parameter <b>collections</b> is not valid (maybe all or api).<br/>';
		die ();
	}
	$collections = $testMatch;
	$testMatch = NULL;
}
if (isset ( $_REQUEST ["items"] ) & $_REQUEST ["items"] != "") {
	// validate to csv integer list
	$testMatch = $_REQUEST ["items"];
	if (! in_array ( $testMatch, array (
			"all" 
	) )) {
		echo 'Parameter <b>items</b> is not valid (maybe all).<br/>';
		die ();
	}
	$items = $testMatch;
	$testMatch = NULL;
}
if (isset ( $_REQUEST ["item"] ) & $_REQUEST ["item"] != "") {
	// reg expr
	$testMatch = $_REQUEST ["item"];
	$pattern = '/^[0-9a-zA-Z\.\-_:]*$/';
	if (! preg_match ( $pattern, $testMatch )) {
		// echo 'id: <b>'.$testMatch.'</b> is not valid.<br/>';
		echo 'Parameter <b>item</b> is not valid (/^[0-9a-zA-Z\.\-_:]*$/).<br/>';
		die ();
	}
	$item = $testMatch;
	$testMatch = NULL;
}
if (isset ( $_REQUEST ["outputFormat"] ) & $_REQUEST ["outputFormat"] != "") {
	// validate to csv integer list
	$testMatch = $_REQUEST ["outputFormat"];
	
	if (! in_array ( $testMatch, $allowedOutputFormats )) {
		echo 'Parameter <b>outputFormat</b> is not valid - must be one of: ' . implode ( ',', $allowedOutputFormats ) . '<br/>';
		die ();
	}
	$outputFormat = $testMatch;
	$testMatch = NULL;
}
if (isset ( $_REQUEST ["f"] ) & $_REQUEST ["f"] != "") {
	// validate to csv integer list
	$testMatch = $_REQUEST ["f"];
	if (! in_array ( $testMatch, $allowedFormats )) {
		echo 'Parameter <b>f</b> is not valid - must be one of: ' . implode ( ',', $allowedFormats ) . '<br/>';
		die ();
	}
	$f = $testMatch;
	$testMatch = NULL;
}
if (isset ( $_REQUEST ["bbox"] ) & $_REQUEST ["bbox"] != "") {
	// validate to float/integer
	$testMatch = $_REQUEST ["bbox"];
	// $pattern = '/^[-\d,]*$/';
	$pattern = '/^[-+]?([0-9]*\.[0-9]+|[0-9]+)*$/';
	$testMatchArray = explode ( ',', $testMatch );
	if (count ( $testMatchArray ) != 4) {
		echo 'Parameter <b>bbox</b> has a wrong amount of entries.<br/>';
		die ();
	}
	for($i = 0; $i < count ( $testMatchArray ); $i ++) {
		if (! preg_match ( $pattern, $testMatchArray [$i] )) {
			echo 'Parameter <b>bbox</b> is not a valid coordinate value.<br/>';
			die ();
		}
	}
	$bbox = $testMatch;
	$testMatch = NULL;
}
if (isset ( $_REQUEST ["nativeJson"] ) & $_REQUEST ["nativeJson"] != "") {
	// validate to csv integer list
	$testMatch = $_REQUEST ["nativeJson"];
	if (! in_array ( $testMatch, array (
			"true",
			"false" 
	) )) {
		echo 'Parameter <b>nativeJson</b> is not valid - must be one of: ' . implode ( ',', array (
				"true",
				"false" 
		) ) . '<br/>';
		die ();
	}
	if ($testMatch == "true") {
		$nativeJson = true;
	}
	$testMatch = NULL;
}

// merge together all request parameters to new global available query_string which is needed for further href's
// this string holds parts from rest url and the further parameters in case of rewrite (rest) and simple invocation via php script
$wholeQueryArray = $_REQUEST;
// unset api part from rewrite, cause this was already read before and intgrated into request array
unset ( $wholeQueryArray ['api'] );
// remove api from
global $wholeQuery;
$wholeQuery = http_build_query ( $wholeQueryArray );
// ************************************************************************************************************************************
// overwrite request_uri if invoked from rest api to add further parameters which came from api path
$e = new mb_notice ( "php/linkedDataProxy.php: Original request URI: " . $_SERVER ['REQUEST_URI'] );
$e = new mb_notice ( "php/linkedDataProxy.php: Mapping to GET Parameters: " . $wholeQuery );
// ************************************************************************************************************************************
// remove api from $_SERVER['REQUEST_URI']
$_SERVER ['REQUEST_URI'] = delTotalFromQuery ( "api", $_SERVER ['REQUEST_URI'] );
// add all other parameters
if (isset ( $_REQUEST ['api'] ) && $_REQUEST ['api'] != "") {
	if (strpos ( $_SERVER ['REQUEST_URI'], "?" ) !== false) {
		$requestUriArray = explode ( "?", $_SERVER ['REQUEST_URI'] );
		$_SERVER ['REQUEST_URI'] = $requestUriArray [0] . "?" . $wholeQuery;
	} else {
		$_SERVER ['REQUEST_URI'] = $wholeQuery;
	}
}
// ************************************************************************************************************************************
$e = new mb_notice ( "php/linkedDataProxy.php: \$_SERVER['REQUEST_URI'] after \"api\" paramter deleted: " . $_SERVER ['REQUEST_URI'] );
// example when invoked from rest api:
// before: /mapbender/php/mod_linkedDataProxy.php?api=18/collections
// after: wfsid=18&collections=all
// therefor we need a resubstitution of the /mapbender/php/mod_linkedDataProxy.php with {proxyPath} and
// the build the further path fom the relevant GET parameters
// function get2Rest() -> does the work
// ************************************************************************************************************************************
$proxyStartTime = microtime_float ();
// instantiate needed classes
$cache = new Cache ();
// generate json return object - after this build up html if wished!!!!****************************************************************
// returnObject differs for service / collection / item
$returnObject = new stdClass ();
// ************************************************************************************************************************************
// service list part
// ************************************************************************************************************************************
/*
 * GET list of wfs which are published with an open license - they don't need autorization control
 * 
 */
if ($restrictToOpenData == true) {
    $sql = "SELECT * FROM (SELECT wfs_id, wfs_version, wfs_abstract, wfs_title, wfs_owsproxy, fkey_termsofuse_id, wfs_getcapabilities, providername, fees FROM wfs INNER JOIN wfs_termsofuse ON wfs_id = fkey_wfs_id) AS wfs_tou INNER JOIN termsofuse ON fkey_termsofuse_id = termsofuse_id WHERE isopen = 1";
} else {
    $sql = "SELECT * FROM (SELECT wfs_id, wfs_version, wfs_abstract, wfs_title, wfs_owsproxy, fkey_termsofuse_id, wfs_getcapabilities, providername, fees FROM wfs INNER JOIN wfs_termsofuse ON wfs_id = fkey_wfs_id) AS wfs_tou INNER JOIN termsofuse ON fkey_termsofuse_id = termsofuse_id";
} 
$v = array ();
$t = array ();
$res = db_prep_query ( $sql, $v, $t );
$i = 0;
$openWfsIds = array();
while ( $row = db_fetch_array ( $res ) ) {
	$openWfsIds[] = $row ['wfs_id'];
	$i ++;
}
unset($i);
//$e = new mb_exception("php/linkedDataProxy.php: open wfs: ".json_encode($openWfsIds));//strings !
if (! isset ( $wfsid ) || $wfsid == "") {
	// list all public available wfs which are classified as opendata!
	$returnObject->service = array ();
	if ($restrictToOpenData == true) {
	    $sql = "SELECT * FROM (SELECT wfs_id, wfs_version, wfs_abstract, wfs_title, wfs_owsproxy, fkey_termsofuse_id, wfs_getcapabilities, providername, fees FROM wfs INNER JOIN wfs_termsofuse ON wfs_id = fkey_wfs_id) AS wfs_tou INNER JOIN termsofuse ON fkey_termsofuse_id = termsofuse_id WHERE isopen = 1";
	} else {// all wfs - without open filter!
	    $sql = "SELECT wfs_id, wfs_abstract, wfs_version, wfs_title, wfs_owsproxy, wfs_getcapabilities, providername, fees FROM wfs";
	}
	$v = array ();
	$t = array ();
	$res = db_prep_query ( $sql, $v, $t );
	$i = 0;
	while ( $row = db_fetch_array ( $res ) ) {
		$returnObject->service [$i]->id = $row ['wfs_id'];
		$returnObject->service [$i]->title = $row ['wfs_title'];
		$returnObject->service [$i]->version = $row ['wfs_version'];
		$returnObject->service [$i]->description = $row ['wfs_abstract'];
		$returnObject->service [$i]->provider = $row ['providername'];
		$returnObject->service [$i]->license = $row ['fees'];
		$returnObject->service [$i]->accessUrl = $row ['wfs_getcapabilities'];
		$i ++;
	}
	if ($i == 0) {
		$returnObject->success = false;
		$returnObject->message = "No services found in registry";
		// $e = new mb_exception("no wfs found");
	} else {
		$returnObject->success = true;
		$returnObject->message = "Services found in registry!";
	}
} else {
		// ************************************************************************************************************************************
		// service part
		// ************************************************************************************************************************************
		// try to instantiate wfs object
		/*
	 * check authentication if access to resource / featuretype is not allowed
	 *
	 */
	if (! in_array ( $wfsid, $openWfsIds )) {
		$proxyActivated = false;
		$authType = "digest";
		// $e = new mb_exception("php/linkedDataProxy.php: wfs has no open data compatible license - check autorization!");
		// for a special featuretype check autorization
		// check authorization - see http_auth/http/index.php
		// check if security proxy is activated
		$sql = "SELECT wfs_owsproxy FROM wfs WHERE wfs_id = $1";
		$v = array ($wfsid);
		$t = array ('i');
		$res = db_prep_query ( $sql, $v, $t );
		while ( $row = db_fetch_array ( $res ) ) {
			if (isset($row['wfs_owsproxy']) && $row['wfs_owsproxy'] != "") {
				$proxyActivated = true;
				$admin = new administration();
			} else {
				$admin = false;
			}
		}
		$anonymousAccess = false;
		//$e = new mb_exception ( $collection );
		if (isset ( $collection ) && ! is_null ( $collection ) && $proxyActivated == true) {
			$user = new user ( PUBLIC_USER );
			$anonymousAccess = $user->areFeaturetypesAccessible ( $collection, $wfsid );
			if ($anonymousAccess == true) {
				$userId = PUBLIC_USER;
			} else {
				switch ($authType) {
					case 'digest' :
						// special for type of authentication ******************************
						// control if digest auth is set, if not set, generate the challenge with getNonce()
						if (empty ( $_SERVER ['PHP_AUTH_DIGEST'] )) {
							header ( 'HTTP/1.1 401 Unauthorized' );
							header ( 'WWW-Authenticate: Digest realm="' . REALM . '",qop="auth",nonce="' . getNonce () . '",opaque="' . md5 ( REALM ) . '"' );
							die ( 'Login cancelled by user!' );
						}
						// read out the header in an array
						$requestHeaderArray = http_digest_parse ( $_SERVER ['PHP_AUTH_DIGEST'] );
						// error if header could not be read
						if (! ($requestHeaderArray)) {
							echo 'Following Header information cannot be validated - check your clientsoftware!<br>';
							echo $_SERVER ['PHP_AUTH_DIGEST'] . '<br>';
							die ();
						}
						// get mb_username and email out of http_auth username string
						$userIdentification = explode ( ';', $requestHeaderArray ['username'] );
						$mbUsername = $userIdentification [0];
						$mbEmail = $userIdentification [1]; // not given in all circumstances
						$userInformation = getUserInfo ( $mbUsername, $mbEmail );
						/*
						 * $result[0] = $row['mb_user_id'];
						 * $result[1] = $row['mb_user_digest'];
						 * $result[2] = $row['mb_user_password'];
						 * $result[3] = $row['password'];
						 */
						if ($userInformation [0] == '-1') {
							die ( 'User with name: ' . $mbUsername . ' and email: ' . $mbEmail . ' not known to security proxy!' );
						}
						if ($userInformation [1] == '') { // check if digest exists in db - if no digest exists it should be a null string!
							die ( 'User with name: ' . $mbUsername . ' and email: ' . $mbEmail . ' has no digest - please set a new password and try again!' );
						}
						// first check the stale!
						if ($requestHeaderArray ['nonce'] == getNonce ()) {
							// Up-to-date nonce received
							$stale = false;
						} else {
							// Stale nonce received (probably more than x seconds old)
							$stale = true;
							// give another chance to authenticate
							header ( 'HTTP/1.1 401 Unauthorized' );
							header ( 'WWW-Authenticate: Digest realm="' . REALM . '",qop="auth",nonce="' . getNonce () . '",opaque="' . md5 ( REALM ) . '" ,stale=true' );
						}
						// generate the valid response to check the request of the client
						$A1 = $userInformation [1];
						$A2 = md5 ( $_SERVER ['REQUEST_METHOD'] . ':' . $requestHeaderArray ['uri'] );
						$valid_response = $A1 . ':' . getNonce () . ':' . $requestHeaderArray ['nc'];
						$valid_response .= ':' . $requestHeaderArray ['cnonce'] . ':' . $requestHeaderArray ['qop'] . ':' . $A2;
						$valid_response = md5 ( $valid_response );
						if ($requestHeaderArray ['response'] != $valid_response) { // the user have to authenticate new - cause something in the authentication went wrong
							die ( 'Authentication failed - sorry, you have to authenticate once more!' );
						}
						// if we are here - authentication has been done well!
						// let's do the proxy things (came from owsproxy.php):
						// special for type of authentication ******************************
						// user information
						// define $userId from database information
						$userId = $userInformation [0];
						break;
					case 'basic' :
						if (! isset ( $_SERVER ['PHP_AUTH_USER'] )) {
							header ( 'WWW-Authenticate: Basic realm="' . REALM . '"' );
							header ( 'HTTP/1.1 401 Unauthorized' );
							die ( 'Authentication failed - sorry, you have to authenticate once more!' );
						} else {
							// get mb_username and email out of http_auth username string
							$userIdentification = explode ( ';', $_SERVER ['PHP_AUTH_USER'] );
							$mbUsername = $userIdentification [0];
							$mbEmail = $userIdentification [1]; // not given in all circumstances
							$userInformation = getUserInfo ( $mbUsername, $mbEmail );
							/*
							 * $result[0] = $row['mb_user_id'];
							 * $result[1] = $row['mb_user_digest'];
							 * $result[2] = $row['mb_user_password'];
							 * $result[3] = $row['password'];
							 */
							if ($userInformation [0] == '-1') {
								die ( 'User with name: ' . $mbUsername . ' and email: ' . $mbEmail . ' not known to security proxy!' );
							}
							/*
							 * if ($userInformation[1] == '') { //check if digest exists in db - if no digest exists it should be a null string!
							 * die('User with name: ' . $mbUsername . ' and email: ' . $mbEmail . ' has no digest - please set a new password and try again!');
							 * }
							 */
							// check password - new since 06/2019 - secure password !!!!!
							if ($userInformation [3] == '' || $userInformation [3] == null) {
								die ( 'User with name: ' . $mbUsername . ' and email: ' . $mbEmail . ' has no password which is stored in a secure way. - Please login at the portal to generate one!' );
							}
							if (password_verify ( $_SERVER ['PHP_AUTH_PW'], $userInformation [3] )) {
								$userId = $userInformation [0];
							} else {
								$userId = $userInformation [0];
								die ( 'HTTP Authentication failed for user: ' . $mbUsername . '!' );
							}
						}
						break;
				}
			}
			// userId known now
			// check autorization
			$user = new user ( $userId );
			//$e = new mb_exception( $userId );
			$accessAllowed = $user->areFeaturetypesAccessible ( $collection, $wfsid );
			if ($accessAllowed == false) {
				header('HTTP/1.0 403 Forbidden');
				die("Access to requested collection is not allowed to current user - log out and try again!"); // give http 403!
			} /*else {
				echo "Access to " . $collection . " allowed for requesting user"; // give http 403!
				die ();
			}*/
		}
	}
	$myWfsFactory = new UniversalWfsFactory ();
	$wfs = $myWfsFactory->createFromDb ( $wfsid ); // set force version to pull featuretype_name with namespace!!!!, $forceVersion = "2.0.0"
	if ($wfs == null) {
		$returnObject->success = false;
		$returnObject->message = "Wfs object could not be created from db!";
	} else {
		// $e = new mb_exception($wfs->providerName." - ".$wfs->summary." - ".$wfs->electronicMailAddress." - ".$wfs->fees);
		// repair some missing wfs data
		if (! isset ( $wfs->summary ) || $wfs->summary == null || $wfs->summary == "") {
			$wfs->summary = "WFS description is missing!";
		}
		if (! isset ( $wfs->providerName ) || $wfs->providerName == null || $wfs->providerName == "") {
			$wfs->providerName = "WFS providername is missing!";
		}
		if (! isset ( $wfs->electronicMailAddress ) || $wfs->electronicMailAddress == null || $wfs->electronicMailAddress == "") {
			$wfs->electronicMailAddress = "test@test.org";
		}
		if (! isset ( $wfs->fees ) || $wfs->fees == null || $wfs->fees == "") {
			$wfs->fees = "No fees are given in WFS Capabilities!";
		}
		// create service part if no collection is requested
		if (! isset ( $collection ) || $collection == "" || $collections == "all" || $collections == "api") {
			// ************************************************************************************************************************************
			// service only part
			// ************************************************************************************************************************************
			// add from rlp!
			$returnObject->id = $wfsid;
			$returnObject->title = $wfs->title;
			$returnObject->description = $wfs->summary;
			$returnObject->provider = $wfs->providerName;
			$returnObject->providerEmail = $wfs->electronicMailAddress;
			// $returnObject->providerHomepage = $wfs->homepage; //TODO add to ows class!
			//$returnObject->providerHomepage = "https://www.geoportal.rlp.de/";
			$returnObject->providerHomepage = METADATA_DEFAULT_CODESPACE;
			$returnObject->license = $wfs->fees;
			
			// get contraints
			$constraints = new OwsConstraints ();
			$constraints->languageCode = "de";
			$constraints->asTable = true;
			$constraints->id = $wfsid;
			$constraints->type = "wfs";
			$constraints->returnDirect = false;
			$tou = $constraints->getDisclaimer (); // TODO encoding problems may occur!
			if (isset ( $imagePathReplace )) {
				$tou = str_replace ( "../img/", $imagePathReplace, $tou );
			}
			if ($f == "html") {
				$returnObject->license = $tou; // - generate license info in json for json format!!!!!
			}
			$returnObject->accessUrl = $wfs->getCapabilities;
			// uri to test openapi description:
			// https://editor.swagger.io/
			if ($collections == "api") {
				$apiDescriptionJson = new stdClass ();
				$apiDescriptionJson->openapi = "3.0.1";
				$apiDescriptionJson->info->title = $wfs->title;
				$apiDescriptionJson->info->description = $wfs->summary;
				
				$apiDescriptionJson->info->contact->name = $wfs->providerName;
				$apiDescriptionJson->info->contact->url = $returnObject->providerHomepage;
				$apiDescriptionJson->info->contact->email = $wfs->electronicMailAddress;
				
				$apiDescriptionJson->info->license->name = $wfs->fees;
				
				$apiDescriptionJson->info->version = "1.0.0";
				
				// server url - for the proxy!!!
				$apiDescriptionJson->servers [0]->url = $linkedDataProxyUrl . "/" . $wfsid . "/";
				
				$apiDescriptionJson->tags [0]->name = "Capabilities";
				$apiDescriptionJson->tags [0]->description = "Essential characteristics of this API including information about the data.";
				$apiDescriptionJson->tags [1]->name = "Features";
				$apiDescriptionJson->tags [1]->description = "Access to data (features).";
				// path / ****************************************************************
				$apiDescriptionJson->paths->{'/'}->get->tags = array (
						"Capabilities" 
				);
				$apiDescriptionJson->paths->{'/'}->get->summary = "landing page of this API";
				$apiDescriptionJson->paths->{'/'}->get->description = "The landing page provides links to the API definition, the Conformance statements and the metadata about the feature data in this dataset.";
				$apiDescriptionJson->paths->{'/'}->get->operationId = "getLandingPage";
				$apiDescriptionJson->paths->{'/'}->get->parameters = array ();
				
				$apiDescriptionJson->paths->{'/'}->get->responses->{'200'}->description = "links to the API capabilities and the feature collections shared by this API.";
				$apiDescriptionJson->paths->{'/'}->get->responses->{'200'}->content->{'application/json'}->schema->{'$ref'} = "#/components/schemas/root";
				$apiDescriptionJson->paths->{'/'}->get->responses->{'200'}->content->{'text/html'}->schema->type = "string";
				$apiDescriptionJson->paths->{'/'}->get->responses->{'default'}->description = "An error occured.";
				$apiDescriptionJson->paths->{'/'}->get->responses->{'default'}->content->{'application/json'}->schema->{'$ref'} = "#/components/schemas/exception";
				$apiDescriptionJson->paths->{'/'}->get->responses->{'default'}->content->{'text/html'}->schema->{'type'} = "string";
				// path / ****************************************************************
				// path /api ****************************************************************
				$apiDescriptionJson->paths->{'/api'}->get->tags = array (
						"Capabilities" 
				);
				$apiDescriptionJson->paths->{'/api'}->get->summary = "the API description - this document";
				$apiDescriptionJson->paths->{'/api'}->get->operationId = "getApiDescription";
				$apiDescriptionJson->paths->{'/api'}->get->parameters = array ();
				
				$apiDescriptionJson->paths->{'/api'}->get->responses->{'200'}->description = "The formal documentation of this API according to the OpenAPI specification, version 3.0. I.e., this document.";
				$apiDescriptionJson->paths->{'/api'}->get->responses->{'200'}->content->{'application/openapi+json;version=3.0'}->schema->type = "object";
				$apiDescriptionJson->paths->{'/api'}->get->responses->{'200'}->content->{'text/html'}->schema->type = "string";
				$apiDescriptionJson->paths->{'/api'}->get->responses->{'default'}->description = "An error occured.";
				$apiDescriptionJson->paths->{'/api'}->get->responses->{'default'}->content->{'application/json'}->schema->{'$ref'} = "#/components/schemas/exception";
				$apiDescriptionJson->paths->{'/api'}->get->responses->{'default'}->content->{'text/html'}->schema->{'type'} = "string";
				// path /api ****************************************************************
				// path /conformance ****************************************************************
				$apiDescriptionJson->paths->{'/conformance'}->get->tags = array (
						"Capabilities" 
				);
				$apiDescriptionJson->paths->{'/conformance'}->get->summary = "information about standards that this API conforms to";
				$apiDescriptionJson->paths->{'/conformance'}->get->description = "list all requirements classes specified in a standard (e.g., WFS 3.0 Part 1: Core) that the server conforms to";
				$apiDescriptionJson->paths->{'/conformance'}->get->operationId = "getRequirementsClasses";
				$apiDescriptionJson->paths->{'/conformance'}->get->parameters = array ();
				
				$apiDescriptionJson->paths->{'/conformance'}->get->responses->{'200'}->description = "the URIs of all requirements classes supported by the server";
				$apiDescriptionJson->paths->{'/conformance'}->get->responses->{'200'}->content->{'application/json'}->schema->{'$ref'} = "#/components/schemas/req-classes";
				$apiDescriptionJson->paths->{'/conformance'}->get->responses->{'default'}->description = "An error occured.";
				$apiDescriptionJson->paths->{'/conformance'}->get->responses->{'default'}->content->{'application/json'}->schema->{'$ref'} = "#/components/schemas/exception";
				// path /conformance ****************************************************************
				// path /collections ****************************************************************
				$apiDescriptionJson->paths->{'/collections'}->get->tags = array (
						"Capabilities" 
				);
				$apiDescriptionJson->paths->{'/collections'}->get->summary = "describe the feature collections in the dataset";
				$apiDescriptionJson->paths->{'/collections'}->get->operationId = "describeCollections";
				$apiDescriptionJson->paths->{'/collections'}->get->parameters = array ();
				
				$apiDescriptionJson->paths->{'/collections'}->get->responses->{'200'}->description = "Metadata about the feature collections shared by this API.";
				$apiDescriptionJson->paths->{'/collections'}->get->responses->{'200'}->content->{'application/json'}->schema->{'$ref'} = "#/components/schemas/content";
				$apiDescriptionJson->paths->{'/collections'}->get->responses->{'200'}->content->{'text/html'}->schema->type = "string";
				$apiDescriptionJson->paths->{'/collections'}->get->responses->{'default'}->description = "An error occured.";
				$apiDescriptionJson->paths->{'/collections'}->get->responses->{'default'}->content->{'application/json'}->schema->{'$ref'} = "#/components/schemas/exception";
				$apiDescriptionJson->paths->{'/collections'}->get->responses->{'default'}->content->{'text/html'}->schema->{'type'} = "string";
				// path /collections ****************************************************************
				// collect the elements foreach featuretype via sql
				/*
				 * check authorization before - against the list of accessable featuretypes for this user
				 */
				foreach ( $wfs->featureTypeArray as $featureType ) {
					// path /collections ****************************************************************
					$featuretypePathPart = '/collections/' . $featureType->name;
					
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->tags = array (
							"Capabilities" 
					);
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->summary = "describe the " . $featureType->title . " feature collection";
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->operationId = "describeCollection" . $featureType->name;
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->parameters = array ();
					
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->responses->{'200'}->description = "Metadata about the collection shared by this API.";
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->responses->{'200'}->content->{'application/geo+json'}->schema->{'$ref'} = "#/components/schemas/collectionInfo";
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->responses->{'200'}->content->{'text/html'}->schema->type = "string";
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->responses->{'default'}->description = "An error occured.";
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->responses->{'default'}->content->{'application/json'}->schema->{'$ref'} = "#/components/schemas/exception";
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->responses->{'default'}->content->{'text/html'}->schema->{'type'} = "string";
					// items *************************************************************************
					$featuretypePathPart = '/collections/' . $featureType->name . '/items';
					
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->tags = array (
							"Features" 
					);
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->summary = "retrieve features of " . $featureType->title . " feature collection";
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->operationId = "getFeatures" . $featureType->name;
					// possible query filters:
					$queryParams = array (
							"f",
							"limit",
							"offset",
							"bbox",
							"resultType",
							"properties" 
					);
					// TODO: crs, bbox-crs, maxAllowableOffset
					foreach ( $queryParams as $param ) {
						$apiDescriptionJson->paths->{$featuretypePathPart}->get->parameters []->{'$ref'} = "#/components/parameters/" . $param;
					}
					// TODO : funktion ? -https://www.ldproxy.nrw.de/topographie/api/?f=json -$apiDescriptionJson->paths->{$featuretypePathPart}->get->parameters[] =
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->responses->{'200'}->description = "A feature.";
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->responses->{'200'}->content->{'application/geo+json'}->schema->{'$ref'} = "#/components/schemas/featureGeoJSON";
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->responses->{'200'}->content->{'text/html'}->schema->type = "string";
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->responses->{'default'}->description = "An error occured.";
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->responses->{'default'}->content->{'application/json'}->schema->{'$ref'} = "#/components/schemas/exception";
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->responses->{'default'}->content->{'text/html'}->schema->{'type'} = "string";
					// items *************************************************************************
					// {items}/{featureId} *************************************************************************
					$featuretypePathPart = '/collections/' . $featureType->name . '/items/{featureId}';
					
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->tags = array (
							"Features" 
					);
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->summary = "retrieve a " . $featureType->title;
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->operationId = "getFeature" . $featureType->name;
					// possible query filters:
					$queryParams = array (
							"featureId",
							"f",
							"properties" 
					);
					// TODO: crs, maxAllowableOffset
					foreach ( $queryParams as $param ) {
						$apiDescriptionJson->paths->{$featuretypePathPart}->get->parameters []->{'$ref'} = "#/components/parameters/" . $param;
					}
					// TODO : funktion ? -https://www.ldproxy.nrw.de/topographie/api/?f=json -$apiDescriptionJson->paths->{$featuretypePathPart}->get->parameters[] =
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->responses->{'200'}->description = "Information about the feature collection plus the first features matching the selection parameters.";
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->responses->{'200'}->content->{'application/geo+json'}->schema->{'$ref'} = "#/components/schemas/featureCollectionGeoJSON";
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->responses->{'200'}->content->{'text/html'}->schema->type = "string";
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->responses->{'default'}->description = "An error occured.";
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->responses->{'default'}->content->{'application/json'}->schema->{'$ref'} = "#/components/schemas/exception";
					$apiDescriptionJson->paths->{$featuretypePathPart}->get->responses->{'default'}->content->{'text/html'}->schema->{'type'} = "string";
					// {items}/{featureId}*************************************************************************
					// path /collections ****************************************************************
					// define template - first use it from ia
					// TODO: remove not used elements
				}
				
				$jsonTemplate = json_decode ( getOpenApi3JsonComponentTemplate () );
				
				$apiDescriptionJson->components = $jsonTemplate->components;
				/*
				 * //components
				 * $apiDescriptionJson->components->schemas->exception->required[0] = "code";
				 * $apiDescriptionJson->components->schemas->exception->type = "object";
				 * $apiDescriptionJson->components->schemas->exception->properties->code->type = "string";
				 * $apiDescriptionJson->components->schemas->exception->properties->description->type = "string";
				 *
				 * $apiDescriptionJson->components->schemas->root->required[0] = "links";
				 * $apiDescriptionJson->components->schemas->root->type = "object";
				 * $apiDescriptionJson->components->schemas->root->properties->links->type = "array";
				 * $apiDescriptionJson->components->schemas->root->properties->links->example[0]->href = "http://data.example.org/";
				 * $apiDescriptionJson->components->schemas->root->properties->links->example[0]->rel = "self";
				 * $apiDescriptionJson->components->schemas->root->properties->links->example[0]->type = "application/json";
				 * $apiDescriptionJson->components->schemas->root->properties->links->example[0]->title = "this document";
				 *
				 * $apiDescriptionJson->components->schemas->root->properties->links->example[1]->href = "http://data.example.org/api";
				 * $apiDescriptionJson->components->schemas->root->properties->links->example[1]->rel = "service-desc";
				 * $apiDescriptionJson->components->schemas->root->properties->links->example[1]->type = "application/openapi+json;version=3.0";
				 * $apiDescriptionJson->components->schemas->root->properties->links->example[1]->title = "the API definition";
				 * $apiDescriptionJson->components->schemas->root->properties->links->example[2]->href = "http://data.example.org/conformance";
				 * $apiDescriptionJson->components->schemas->root->properties->links->example[2]->rel = "conformance";
				 * $apiDescriptionJson->components->schemas->root->properties->links->example[2]->type = "application/json";
				 * $apiDescriptionJson->components->schemas->root->properties->links->example[2]->title = "WFS 3.0 conformance classes implemented by this server";
				 * $apiDescriptionJson->components->schemas->root->properties->links->example[3]->href = "http://data.example.org/collections";
				 * $apiDescriptionJson->components->schemas->root->properties->links->example[3]->rel = "data";
				 * $apiDescriptionJson->components->schemas->root->properties->links->example[3]->type = "application/json";
				 * $apiDescriptionJson->components->schemas->root->properties->links->example[1]->title = "Metadata about the feature collections";
				 *
				 *
				 * $apiDescriptionJson->components->schemas->{'req-classes'}->required[0] = "conformsTo";
				 * $apiDescriptionJson->components->schemas->{'req-classes'}->type = "object";
				 * $apiDescriptionJson->components->schemas->{'req-classes'}->properties->conformsTo->type = "array";
				 * $apiDescriptionJson->components->schemas->{'req-classes'}->properties->conformsTo->example[0] = "http://www.opengis.net/spec/wfs-1/3.0/req/core";
				 * $apiDescriptionJson->components->schemas->{'req-classes'}->properties->conformsTo->example[1] = "http://www.opengis.net/spec/wfs-1/3.0/req/oas30";
				 * $apiDescriptionJson->components->schemas->{'req-classes'}->properties->conformsTo->example[2] = "http://www.opengis.net/spec/wfs-1/3.0/req/html";
				 * $apiDescriptionJson->components->schemas->{'req-classes'}->properties->conformsTo->example[3] = "http://www.opengis.net/spec/wfs-1/3.0/req/geojson";
				 * $apiDescriptionJson->components->schemas->{'req-classes'}->properties->conformsTo->items = "string";
				 *
				 * $apiDescriptionJson->components->schemas->link->required[0] = "href";
				 * $apiDescriptionJson->components->schemas->link->type = "object";
				 * $apiDescriptionJson->components->schemas->link->properties->href->type = "string";
				 * $apiDescriptionJson->components->schemas->link->properties->href->example = "http://data.example.com/buildings/123";
				 * $apiDescriptionJson->components->schemas->link->properties->rel->type = "string";
				 * $apiDescriptionJson->components->schemas->link->properties->rel->example = "prev";
				 * $apiDescriptionJson->components->schemas->link->properties->type->type = "string";
				 * $apiDescriptionJson->components->schemas->link->properties->type->example = "application/geo+json";
				 * $apiDescriptionJson->components->schemas->link->properties->type->hreflang = "string";
				 * $apiDescriptionJson->components->schemas->link->properties->type->hreflang = "de";
				 *
				 */
				/*
				 * $apiDescriptionJson->components->schemas->content->
				 * $apiDescriptionJson->components->schemas->collectionInfo->
				 * $apiDescriptionJson->components->schemas->extent->
				 * $apiDescriptionJson->components->schemas->featureCollectionGeoJSON->
				 * $apiDescriptionJson->components->schemas->featureGeoJSON->
				 * $apiDescriptionJson->components->schemas->geometryGeoJSON->
				 *
				 * $apiDescriptionJson->components->parameters->
				 */
				$paramArray = array (
						"f",
						"limit",
						"offset",
						"bbox",
						"resultType",
						"featureId",
						"limitList",
						"properties" 
				); // TODO: relations, resolve, offsetList, crs, bbox-crs, maxAllowedOffset
				                                                                                                           // first draft - set only json based api description and give it back
				header ( "application/json" );
				echo json_encode ( $apiDescriptionJson, JSON_UNESCAPED_SLASHES );
				die ();
			}
			//
			$returnObject->links = array ();
			$returnObject->crs = array ();
			$returnObject->collections = array ();
			$returnObject->links [0]->rel = "self";
			$returnObject->links [0]->type = "application/json";
			$returnObject->links [0]->title = "this document";
			$returnObject->links [0]->href = get2Rest ( $_SERVER ['REQUEST_URI'] . "&f=json" );
			$returnObject->links [1]->rel = "alternate";
			$returnObject->links [1]->type = "text/html";
			$returnObject->links [1]->title = "this document as HTML";
			$returnObject->links [1]->href = get2Rest ( $_SERVER ['REQUEST_URI'] . "&f=html" );
			// TODO service api
			$returnObject->links [2]->rel = "service-desc";
			$returnObject->links [2]->type = "application/vnd.oai.openapi+json;version=3.0";
			$returnObject->links [2]->title = "The OpenAPI definition as JSON";
			$returnObject->links [2]->href = get2Rest ( $_SERVER ['REQUEST_URI'] . "&collections=api" );
			// TODO conformance
			// TODO data
			$returnObject->links [3]->rel = "data";
			$returnObject->links [3]->type = "application/json";
			$returnObject->links [3]->title = "Metadata about the feature collections";
			$returnObject->links [3]->href = get2Rest ( $_SERVER ['REQUEST_URI'] . "&collection=all" );
			// available crs? - howto get from capabilities
			
			// ************************************************************************************************************************************
			// collection / featuretype list
			// ************************************************************************************************************************************
			$collectionArray = array ();
			$collectionCount = 0;
			foreach ( $wfs->featureTypeArray as $featureType ) {
				$returnObject->collections [$collectionCount]->name = $featureType->name;
				$returnObject->collections [$collectionCount]->title = $featureType->title;
				$returnObject->collections [$collectionCount]->description = $featureType->description;
				$returnObject->collections [$collectionCount]->extent->spatial = array ();
				$returnObject->collections [$collectionCount]->links [0]->rel = "item";
				$returnObject->collections [$collectionCount]->links [0]->type = "application/json";
				$returnObject->collections [$collectionCount]->links [0]->title = $featureType->title . " as GeoJSON";
				$returnObject->collections [$collectionCount]->links [0]->href = get2Rest ( $_SERVER ['REQUEST_URI'] . "&collection=" . $featureType->name . "&items=all" );
				// one item entry for each format!
				// self
				$returnObject->collections [$collectionCount]->links [1]->rel = "self";
				$returnObject->collections [$collectionCount]->links [1]->type = "application/json";
				$returnObject->collections [$collectionCount]->links [1]->title = "Information about the " . $featureType->title . " data";
				$returnObject->collections [$collectionCount]->links [1]->href = get2Rest ( $_SERVER ['REQUEST_URI'] . "&collection=" . $featureType->name );
				// alternate
				// TODO
				$returnObject->collections [$collectionCount]->extent->crs = array ();
				$collectionCount ++;
			}
		} else {
			// special collection selected
			// is collection one of the featuretypes of this wfs?
			// ************************************************************************************************************************************
			// collection part
			// ************************************************************************************************************************************
			// test if collection is available in service
			$ftNameInWfs = false;
			foreach ( $wfs->featureTypeArray as $featureType ) {
				if ($featureType->name == $collection) {
					// requested ft found!
					$ftNameInWfs = true;
					$ftTitle = $featureType->title;
					$ftName = $featureType->name;
					$ftDbId = $featureType->id;
					// output formats
					$ftOutputFormats = implode ( ',', array_unique ( $featureType->featuretypeOutputFormatArray ) );
					// get other relevant ft information
					// extract schema - get all elements that are strings and integers
					$ftElementArray = $featureType->elementArray; // consists of name and type
					                                              // get allowed attributes for filtering
					//$e = new mb_exception("php/mod_linkedDataProxy.php: ftElementArray: ".json_encode($ftElementArray));
					$ftAllowedAttributesArray = array ();
					foreach ( $ftElementArray as $ftElement ) {
						// $e = new mb_exception($ftElement->name ." - " .$ftElement->type);
					    if (in_array((string)$ftElement->type, array("string", "xsd:string", "int"))) {
							$ftAllowedAttributesArray [] = $ftElement->name;
						}
					}
					break;
				}
			}
			if ($ftNameInWfs) {
				$myFeatureType = $wfs->findFeatureTypeByName ( $ftName );
				$geomColumnName = $wfs->findGeomColumnNameByFeaturetypeId ( $myFeatureType->id );
				// check all allowed attributes to may be set by GET param
				$stringFilterArray = array ();
				$stringFilterActive = array ();
				$stringFilterIndex = 0;
				// $e = new mb_exception("test: count: ".count($ftAllowedAttributesArray));
				foreach ( $ftAllowedAttributesArray as $ftAllowedAttribute ) {
					// $e = new mb_exception("search for: ".$ftAllowedAttribute);
					if (isset ( $_REQUEST [$ftAllowedAttribute] ) && $_REQUEST [$ftAllowedAttribute] != "") {
						// $e = new mb_exception("found param:".$ftAllowedAttribute.": ".$_REQUEST[$ftAllowedAttribute]);
						$testMatch = $_REQUEST [$ftAllowedAttribute];
						$pattern = '/^[0-9a-zA-Z\.\-_:*]*$/';
						if (! preg_match ( $pattern, $testMatch )) {
							echo 'Parameter <b>' . $ftAllowedAttribute . '</b> is not valid (allowed string).<br/>';
							die ();
						}
						$stringFilterActive [] = $ftAllowedAttribute;
						$stringFilterArray [$stringFilterIndex]->elementName = $ftAllowedAttribute;
						$stringFilterArray [$stringFilterIndex]->elementFilter = $testMatch;
						$stringFilterIndex ++;
						$testMatch = NULL;
					}
				}
				// first test - only use string filter from index 0!
				if (! isset ( $item ) || $item == "") {
					// generate description of collection in json
					$returnObject->name = $myFeatureType->name;
					$returnObject->title = $myFeatureType->title;
					$returnObject->description = $myFeatureType->abstract;
					
					$returnObject->extent->spatial = $myFeatureType->latLonBboxArray;
					$returnObject->extent->temporal = array ();
					$returnObject->links = array ();
					$returnObject->links [0]->rel = "item";
					$returnObject->links [0]->type = "application/geo+json";
					$returnObject->links [0]->title = $myFeatureType->title . " as GeoJSON";
					$returnObject->links [0]->href = get2Rest ( $_SERVER ['REQUEST_URI'] . "&collection=" . $featureType->name . "&items=all&f=json" );
					
					// TODO: items in other formats, self, alternate
					if ($items == "all") { // show items in list!
					                       // reinitialize object!
						$returnObject = new stdClass ();
						// for rlp:
						$returnObject->serviceTitle = $wfs->title;
						$returnObject->collectionId = $myFeatureType->id;
						$returnObject->collectionName = $ftName;
						$returnObject->collectionTitle = $myFeatureType->title;
						//
						$returnObject->title = $myFeatureType->title;
						$returnObject->id = $ftName;
						$returnObject->description = $myFeatureType->summary;
						$returnObject->extent->spatial = $myFeatureType->latLonBboxArray;
						$returnObject->extent->temporal = array ();
						
						
						
						$returnObject->type = "FeatureCollection";
						$returnObject->links = array ();
						$returnObject->links [0]->rel = "self";
						$returnObject->links [0]->type = "application/geo+json";
						$returnObject->links [0]->title = "this document";
						$returnObject->links [0]->href = get2Rest ( $_SERVER ['REQUEST_URI'] );
						// TODO alternate
						// check for given spatialFilter (bbox)
						if (isset ( $bbox ) && $bbox != '') {
							$filter = $wfs->bbox2spatialFilter ( $bbox, $geomColumnName, $srs = "EPSG:4326", $version = '2.0.0' );
						} else {
							$filter = null;
						}
						// add string filter if some one was given
						if (isset ( $stringFilterArray ) && count ( $stringFilterArray ) > 0) {
							// foreach - generate own filter - TODO
							/*
							 * <And>
							 * <PropertyIsEqualTo><ValueReference>dog:gemarkung</ValueReference><Literal>0401</Literal></PropertyIsEqualTo>
							 * <PropertyIsEqualTo><ValueReference>dog:flur</ValueReference><Literal>109</Literal></PropertyIsEqualTo>
							 * <PropertyIsEqualTo><ValueReference>dog:flurstuecksnummer</ValueReference><Literal>00212</Literal></PropertyIsEqualTo>
							 * <PropertyIsEqualTo><ValueReference>dog:flurstuecksnummernenner</ValueReference><Literal>0007</Literal></PropertyIsEqualTo>
							 * </And>
							 */
							/*
							 * <fes:PropertyIsLike wildCard="*" singleChar="." escapeChar="\">
							 * <fes:ValueReference>st:stationName</fes:ValueReference>
							 * <fes:Literal>Rov*</fes:Literal>
							 * </fes:PropertyIsLike>
							 */
							// TODO allow combination of different text filters!!!! - Not all wfs support this ?
							if (false) {
								if (strpos ( $stringFilterArray [0]->elementFilter, "*" ) !== false) {
									$textFilter .= '<fes:PropertyIsLike wildCard="*" singleChar="." escapeChar="\">';
									$textFilter .= '<fes:ValueReference>' . $stringFilterArray [0]->elementName . '</fes:ValueReference>';
									$textFilter .= '<fes:Literal>' . $stringFilterArray [0]->elementFilter . '</fes:Literal>';
									$textFilter .= '</fes:PropertyIsLike>';
								} else {
									$textFilter .= '<fes:PropertyIsEqualTo>';
									$textFilter .= '<fes:ValueReference>' . $stringFilterArray [0]->elementName . '</fes:ValueReference>';
									$textFilter .= '<fes:Literal>' . $stringFilterArray [0]->elementFilter . '</fes:Literal>';
									$textFilter .= '</fes:PropertyIsEqualTo>';
								}
							} else {
								$textFilterArray = array ();
								$textFilterIndex = 0;
								$textFilterArray [$textFilterIndex] = "";
								foreach ( $stringFilterArray as $stringFilter ) {
									if (strpos ( $stringFilter->elementFilter, "*" ) !== false) {
										$textFilterArray [$textFilterIndex] .= '<fes:PropertyIsLike wildCard="*" singleChar="." escapeChar="\">';
										$textFilterArray [$textFilterIndex] .= '<fes:ValueReference>' . $stringFilter->elementName . '</fes:ValueReference>';
										$textFilterArray [$textFilterIndex] .= '<fes:Literal>' . $stringFilter->elementFilter . '</fes:Literal>';
										$textFilterArray [$textFilterIndex] .= '</fes:PropertyIsLike>';
									} else {
										$textFilterArray [$textFilterIndex] .= '<fes:PropertyIsEqualTo>';
										$textFilterArray [$textFilterIndex] .= '<fes:ValueReference>' . $stringFilter->elementName . '</fes:ValueReference>';
										$textFilterArray [$textFilterIndex] .= '<fes:Literal>' . $stringFilter->elementFilter . '</fes:Literal>';
										$textFilterArray [$textFilterIndex] .= '</fes:PropertyIsEqualTo>';
									}
									$textFilterIndex ++;
								}
								if (count ( $textFilterArray ) > 1) { // bbox is set
									$textFilter = '<fes:And>' . implode ( '', $textFilterArray ) . '</fes:And>';
								} else {
									$textFilter = implode ( '', $textFilterArray );
								}
							}
						} else {
							$textFilter = null;
						}
						// build new filter
						if ($filter != null && $textFilter != null) {
							$filter = '<fes:Filter xmlns:fes="http://www.opengis.net/fes/2.0"><fes:And>' . $filter . $textFilter . '</fes:And></fes:Filter>';
						} else {
							if ($filter == null && $textFilter == null) {
								$filter = null;
							} else {
								$filter = '<fes:Filter xmlns:fes="http://www.opengis.net/fes/2.0">' . ( string ) $filter . ( string ) $textFilter . '</fes:Filter>';
							}
						}
						// test
						// $e = new mb_exception("filter: ".$filter);
						// write number of features to ram cache:
						if ($cache->isActive) {
							// if (false) {
							if ($cache->cachedVariableExists ( md5 ( "count_" . $wfsid . "_" . $collection . "_" . md5 ( $filter ) ) ) == false) {
								$numberOfObjects = $wfs->countFeatures ( $collection, $filter, "EPSG:4326", "2.0.0", false, $wfs_http_method );
								$cache->cachedVariableAdd ( md5 ( "count_" . $wfsid . "_" . $collection . "_" . md5 ( $filter ) ), $numberOfObjects );
							} else {
								// $e = new mb_exception("read count from cache!");
								$numberOfObjects = $cache->cachedVariableFetch ( md5 ( "count_" . $wfsid . "_" . $collection . "_" . md5 ( $filter ) ) );
							}
							// $e = new mb_notice("http/classes/class_crs.php - store crs info to cache!");
							// return true;
						} else {
							// TODO - define post/get central
							// $numberOfObjects = $wfs->countFeatures($collection, $filter, "2.0.0");
							$numberOfObjects = $wfs->countFeatures ( $collection, $filter, "EPSG:4326", "2.0.0", false, $wfs_http_method );
						}
						// $numberOfObjects = 1000;
						// $e = new mb_exception("counted features: ".$numberOfObjects);
						if ($numberOfObjects == 0 || $numberOfObjects == false) {
							$returnObject->success = false;
							$returnObject->message = "No results found or an error occured - see server logs - please try it again! Use the back button!";
							// if ($f == "json") {
							header ( "application/json" );
							echo json_encode ( $returnObject );
							// }
							die ();
						}
						// $e = new mb_exception("number of objects: ".$numberOfObjects);
						// request first object and metadata
						// count objects
						// TODO - create json
						// $html .= "wfs max features: ".$wfs->wfs_max_features."<br>";
						// $html .= $ftTitle." (".$numberOfObjects.") - id: " .$ftDbId. " - output formats: ".$ftOutputFormats."<br>";
						// get first page
						// calculate pages
						// $numberOfPages = ceil($numberOfObjects / $maxObjectsPerPage);
						$numberOfPages = ceil ( $numberOfObjects / $limit );
						// decide which page should be requested
						// $page = 0;
						// calculate offset for requested page
						if ($page >= $numberOfPages) {
							$returnObject->success = false;
							$returnObject->message = "Requested page exeeds number of max pages!";
							if ($f == "json") {
								header ( "application/json" );
								echo json_encode ( $returnObject );
							}
							die ();
						} else {
							$startIndex = $page * $limit;
						}
						if (! isset ( $offset )) {
							$offset = 0;
						}
						$lastOffset = ($numberOfPages - 1) * $limit;
						$startIndex = $offset;
						// next page
						$returnObject->links [1]->rel = "next";
						$returnObject->links [1]->type = "application/geo+json";
						$returnObject->links [1]->title = "next page";
						// $returnObject->links[1]->href = $_SERVER['REQUEST_URI']."&p=".($page + 1);
						$returnObject->links [1]->href = get2Rest ( $_SERVER ['REQUEST_URI'] . "&offset=" . ($offset + 1 * $limit) . "&limit=" . $limit );
						// for rlp
						$returnObject->links [2]->rel = "last";
						$returnObject->links [2]->type = "application/geo+json";
						$returnObject->links [2]->title = "last page";
						// $returnObject->links[1]->href = $_SERVER['REQUEST_URI']."&p=".($page + 1);
						$returnObject->links [2]->href = get2Rest ( $_SERVER ['REQUEST_URI'] . "&offset=" . $lastOffset . "&limit=" . $limit );
						// check if outputformat geojson is available - if - gml don't need to be parsed!!!!! TODO - where to become hits ????? - has to count in a special request!!!!!
						if (in_array ( 'application/json; subtype=geojson', explode ( ',', $ftOutputFormats ) ) && $nativeJson == true) {
							// if (false) {
							$features = $wfs->getFeaturePaging ( $ftName, $filter, "EPSG:4326", null, null, $limit, $startIndex, "2.0.0", 'application/json; subtype=geojson', $wfs_http_method );
							$gmlFeatureCache = $features;
							$geojsonList = json_decode ( $features );
							$geojsonBbox = array ();
							$geojsonIndex = 0;
							$minxFC = 90;
							$minyFC = 180;
							$maxxFC = - 90;
							$maxyFC = - 180;
							$minxF = 90;
							$minyF = 180;
							$maxxF = - 90;
							$maxyF = - 180;
							if ($f == 'html') {
								$geoJsonVariable = "";
								$geoJsonVariable = '<script>' . $newline;
							}
							// read geojson to calculate bboxes
							foreach ( $geojsonList->features as $feature ) {
								$minxF = 90;
								$minyF = 180;
								$maxxF = - 90;
								$maxyF = - 180;
								switch ($feature->geometry->type) {
									case "Polygon" :
										foreach ( $feature->geometry->coordinates [0] as $lonLat ) {
											$lon = $lonLat [0];
											$lat = $lonLat [1];
											if ($minxF > $lat) {
												$minxF = $lat;
											}
											if ($minyF > $lon) {
												$minyF = $lon;
											}
											if ($maxxF < $lat) {
												$maxxF = $lat;
											}
											if ($maxyF < $lon) {
												$maxyF = $lon;
											}
										}
										break;
									case "Point" :
										$lon = $feature->geometry->coordinates [0];
										$lat = $feature->geometry->coordinates [1];
										if ($minxF > $lat) {
											$minxF = $lat;
										}
										if ($minyF > $lon) {
											$minyF = $lon;
										}
										if ($maxxF < $lat) {
											$maxxF = $lat;
										}
										if ($maxyF < $lon) {
											$maxyF = $lon;
										}
										break;
									case "LineString" :
										foreach ( $feature->geometry->coordinates as $lonLat ) {
											$lon = $lonLat [0];
											$lat = $lonLat [1];
											if ($minxF > $lat) {
												$minxF = $lat;
											}
											if ($minyF > $lon) {
												$minyF = $lon;
											}
											if ($maxxF < $lat) {
												$maxxF = $lat;
											}
											if ($maxyF < $lon) {
												$maxyF = $lon;
											}
										}
										break;
								}
								// $e = new mb_exception("bbox feature: minxF:".$minxF." minyF:".$minyF." maxxF:".$maxxF." maxyF:".$maxyF."");
								if ($minxFC > $minxF) {
									$minxFC = $minxF;
								}
								if ($minyFC > $minyF) {
									$minyFC = $minyF;
								}
								if ($maxxFC < $maxxF) {
									$maxxFC = $maxxF;
								}
								if ($maxyFC < $maxyF) {
									$maxyFC = $maxyF;
								}
								$geojsonBbox [$geojsonIndex]->minx = $minxF;
								$geojsonBbox [$geojsonIndex]->miny = $minyF;
								$geojsonBbox [$geojsonIndex]->maxx = $maxxF;
								$geojsonBbox [$geojsonIndex]->maxy = $maxyF;
								$geomType = $feature->geometry->type;
								$geojsonIndex ++;
								// $e = new mb_exception("bbox featurecollection: minxFC:".$minxFC." minyFC:".$minyFC." maxxFC:".$maxxFC." maxyFC:".$maxyFC."");
							}
							/*
							 * log count of features, if logging is activated
							 */
							if ($admin != false && $admin->getWfsLogTag($wfsid) == 1) {
								//get price out of db
								$price = intval($admin->getWfsPrice($wfsid));
								$log_id = $admin->logWfsProxyRequest($wfsid, $userId, "OGC API Features Proxy", $price, 0, $ftName);
							} else {
								$log_id = false;
							}
							if ($log_id !== false) {
								$admin->updateWfsLog(1, '', '', $geojsonIndex, $log_id);
							}
							/*
							 * header('application/json');
							 * echo $features;
							 * die();
							 */
						} else {
							// $e = new mb_exception($filter);
						    //$e = new mb_exception("php/mod_linkedDataProxy.php: supported output formats: ".json_encode($ftOutputFormats));
							$features = $wfs->getFeaturePaging ( $ftName, $filter, "EPSG:4326", null, null, $limit, $startIndex, "2.0.0", false, $wfs_http_method );
							// transform to geojson to allow rendering !
							// $e = new mb_exception($features);
							$gmlFeatureCache = $features;
							$gml3Class = new Gml_3_Factory ();
							// create featuretype object
							// TODO
							// $e = new mb_exception("geom column type: ".$geomColumnName);
							// $e = new mb_exception("featuretype name: ".$ftName);
							// $memBeforeGmlParsing = memory_get_usage();
							// $e = new mb_exception("Memory before GML Object: ".((memory_get_usage() - $startmem) / 1000)." MB");
							//$e = new mb_exception($wfs." - ".$myFeatureType." - ".$geomColumnName);
							$gml3Object = $gml3Class->createFromXml ( $features, null, $wfs, $myFeatureType, $geomColumnName );
							// $e = new mb_exception("Memory for GML Object: ".((memory_get_usage() - $memBeforeGmlParsing) / 1000)." MB");
							// $e = new mb_exception("geojson from mb class: ".json_encode($gml3Object));
							$geojsonList = new stdClass ();
							$geojsonList->type = "FeatureCollection";
							$geojsonList->features = array ();
							$geojsonBbox = array ();
							$geojsonIndex = 0;
							$minxFC = 90;
							$minyFC = 180;
							$maxxFC = - 90;
							$maxyFC = - 180;
							// TODO write javascript object if to var if html is requested
							if ($f == 'html') {
								$geoJsonVariable = "";
								$geoJsonVariable = '<script>' . $newline;
							}
							// $e = new mb_exception("size of gml3Object: ".);
							foreach ( $gml3Object->featureCollection->featureArray as $mbFeature ) {
								// $e = new mb_exception("geojson from mb feature exporthandler: ".json_encode($mbFeature));
								// $e = new mb_exception("geoJson object no.: ".$geojsonIndex." - current Memory usage: ".((memory_get_usage() - $startmem) / 1000)." MB");
								// bbox
								try {
									$geojsonBbox [$geojsonIndex]->mbBbox = $mbFeature->getBbox ();
									// $e = new mb_exception('bbox: '.$geojsonBbox[$geojsonIndex]->mbBbox);
								} catch ( Exception $e ) {
									$e = new mb_exception ( 'Problem to resolve bbox from gml - set to default values!', $e->getMessage () );
									$geojsonBbox [$geojsonIndex]->mbBbox = "[(" . $minxFC . "," . $minyFC . ",,urn:ogc:def:crs:EPSG::4326)(" . $maxxFC . "," . $maxyFC . ",,urn:ogc:def:crs:EPSG::4326) urn:ogc:def:crs:EPSG::4326]";
								}
								// $e = new mb_exception('bbox: '.$geojsonBbox[$geojsonIndex]->mbBbox);
								// transform to simple bbox object for leaflet
								$bbox_new = explode ( ' ', str_replace ( ']', '', str_replace ( '[', '', $geojsonBbox [$geojsonIndex]->mbBbox ) ) );
								$bbox_new = explode ( '|', str_replace ( ')', '', str_replace ( '(', '', str_replace ( ')(', '|', $bbox_new [0] ) ) ) );
								$bbox_min = explode ( ',', $bbox_new [0] );
								$bbox_max = explode ( ',', $bbox_new [1] );
								$geojsonBbox [$geojsonIndex]->minx = $bbox_min [0];
								$geojsonBbox [$geojsonIndex]->miny = $bbox_min [1];
								$geojsonBbox [$geojsonIndex]->maxx = $bbox_max [0];
								$geojsonBbox [$geojsonIndex]->maxy = $bbox_max [1];
								if ($minxFC > $geojsonBbox [$geojsonIndex]->minx) {
									$minxFC = $geojsonBbox [$geojsonIndex]->minx;
								}
								if ($minyFC > $geojsonBbox [$geojsonIndex]->miny) {
									$minyFC = $geojsonBbox [$geojsonIndex]->miny;
								}
								if ($maxxFC < $geojsonBbox [$geojsonIndex]->maxx) {
									$maxxFC = $geojsonBbox [$geojsonIndex]->maxx;
								}
								if ($maxyFC < $geojsonBbox [$geojsonIndex]->maxy) {
									$maxyFC = $geojsonBbox [$geojsonIndex]->maxy;
								}
								// get geomtype
								$geomType = json_decode ( $mbFeature->toGeoJSON () )->geometry->type;
								$geojsonList->features [] = json_decode ( $mbFeature->toGeoJSON () );
								// free memory
								unset ( $gml3Object->featureCollection->featureArray [$geojsonIndex] );
								$geojsonIndex ++;
							}
							/*
							 * log count of features, if logging is activated
							 */
							if ($admin != false && $admin->getWfsLogTag($wfsid) == 1) {
								//get price out of db
								$price = intval($admin->getWfsPrice($wfsid));
								$log_id = $admin->logWfsProxyRequest($wfsid, $userId, "OGC API Features Proxy", $price, 0, $ftName);
							} else {
								$log_id = false;
							}
							if ($log_id !== false) {
								$admin->updateWfsLog(1, '', '', $geojsonIndex, $log_id);
							}
						}
						if ($f == 'html') {
							// alter keys to human readable values from json-schema if given
							$resolveJsonSchema = getJsonSchemaObject ( $geojsonList->features [0] );
						    $schemaObject = $resolveJsonSchema->schema;
							if ($resolveJsonSchema->success = true) {
								//$geojsonListView = mapFeatureKeys($geojsonList, $schemaObject);
								$geoJsonVariable .= "var feature_schema=". json_encode ( $schemaObject ) . ";";
							}
							$geoJsonVariable .= "var feature_" . $geomType . "=" . json_encode ( $geojsonList ) . ";";
							$geoJsonVariable .= $newline . "</script>" . $newline;
						}
						
						$usedProxyTime = microtime_float () - $proxyStartTime;
						$returnObject->numberMatched = $numberOfObjects;
						$returnObject->numberReturned = $geojsonIndex;
						$date = new DateTime ();
						$returnObject->timeStamp = date ( 'Y-m-d\TH:i:s.Z\Z', $date->getTimestamp () );
						$returnObject->genTime = $usedProxyTime;
						// resolve json-schema *********************************************************
						$resolveJsonSchema = getJsonSchemaObject ( $geojsonList->features [0] );
						$schemaObject = $resolveJsonSchema->schema;
						// json-ld
						$resolveJsonLd = getJsonLdObject ( $geojsonList->features [0] );
						$ldObject = $resolveJsonLd->schema;
						// add url to
						foreach ( $geojsonList->features as $feature ) {
							if ($resolveJsonSchema->success = true) {
								$feature->{'$schema'} = $resolveJsonSchema->url;
							}
							if ($resolveJsonLd->success = true) {
								$feature->{'$context'} = $resolveJsonLd->url;
							}
						}
						// *****************************************************************************
						$returnObject->features = $geojsonList->features;
					}
					
					// $e = new mb_exception("wfsid: ".$wfsid." - collection: ".$collection." - item: ".$item);
				} else {
					// $e = new mb_exception("wfsid: ".$wfsid." - collection: ".$collection." - item: ".$item);
					// ************************************************************************************************************************************
					// item part
					// ************************************************************************************************************************************
					// $e = new mb_exception("wfsid: ".$wfsid." - collection: ".$collection." - item: ".$item);
					if (in_array ( 'application/json; subtype=geojson', explode ( ',', $ftOutputFormats ) ) && $nativeJson == true) {
						$features = $wfs->getFeatureById ( $collection, 'application/json; subtype=geojson', $item, "2.0.0", "EPSG:4326" );
						$gmlFeatureCache = $features;
						$geojsonList = json_decode ( $features );
						$geojsonBbox = array ();
						$geojsonIndex = 0;
						$minxFC = 90;
						$minyFC = 180;
						$maxxFC = - 90;
						$maxyFC = - 180;
						$minxF = 90;
						$minyF = 180;
						$maxxF = - 90;
						$maxyF = - 180;
						if ($f == 'html') {
							$geoJsonVariable = "";
							$geoJsonVariable .= '<script>' . $newline;
						}
						// read geojson to calculate bboxes
						foreach ( $geojsonList->features as $feature ) {
							$minxF = 90;
							$minyF = 180;
							$maxxF = - 90;
							$maxyF = - 180;
							switch ($feature->geometry->type) {
								case "Polygon" :
									foreach ( $feature->geometry->coordinates [0] as $lonLat ) {
										$lon = $lonLat [0];
										$lat = $lonLat [1];
										if ($minxF > $lat) {
											$minxF = $lat;
										}
										if ($minyF > $lon) {
											$minyF = $lon;
										}
										if ($maxxF < $lat) {
											$maxxF = $lat;
										}
										if ($maxyF < $lon) {
											$maxyF = $lon;
										}
									}
									break;
								case "Point" :
									$lon = $feature->geometry->coordinates [0];
									$lat = $feature->geometry->coordinates [1];
									if ($minxF > $lat) {
										$minxF = $lat;
									}
									if ($minyF > $lon) {
										$minyF = $lon;
									}
									if ($maxxF < $lat) {
										$maxxF = $lat;
									}
									if ($maxyF < $lon) {
										$maxyF = $lon;
									}
									break;
								case "LineString" :
									foreach ( $feature->geometry->coordinates as $lonLat ) {
										$lon = $lonLat [0];
										$lat = $lonLat [1];
										if ($minxF > $lat) {
											$minxF = $lat;
										}
										if ($minyF > $lon) {
											$minyF = $lon;
										}
										if ($maxxF < $lat) {
											$maxxF = $lat;
										}
										if ($maxyF < $lon) {
											$maxyF = $lon;
										}
									}
									break;
							}
							if ($minxFC > $minxF) {
								$minxFC = $minxF;
							}
							if ($minyFC > $minyF) {
								$minyFC = $minyF;
							}
							if ($maxxFC < $maxxF) {
								$maxxFC = $maxxF;
							}
							if ($maxyFC < $maxyF) {
								$maxyFC = $maxyF;
							}
							$geojsonBbox [$geojsonIndex]->minx = $minxF;
							$geojsonBbox [$geojsonIndex]->miny = $minyF;
							$geojsonBbox [$geojsonIndex]->maxx = $maxxF;
							$geojsonBbox [$geojsonIndex]->maxy = $maxyF;
							$geomType = $feature->geometry->type;
							$geojsonIndex ++;
						}
						if ($admin != false && $admin->getWfsLogTag($wfsid) == 1) {
							//get price out of db
							$price = intval($admin->getWfsPrice($wfsid));
							$log_id = $admin->logWfsProxyRequest($wfsid, $userId, "OGC API Features Proxy", $price, 0, $ftName);
						} else {
							$log_id = false;
						}
						if ($log_id !== false) {
							$admin->updateWfsLog(1, '', '', $geojsonIndex, $log_id);
						}
					} else {
					    //use outputformat if supported is not in list!
					    //$e = new mb_exception("php/mod_linkedDataProxy.php - getfeaturebyid outputformats: ".json_encode($ftOutputFormats));
					    if (!in_array('text/xml; subtype=gml/3.1.1', $ftOutputFormats)) {
					        $forcedOutputFormat = false;
					    } else {
					        $forcedOutputFormat = 'text/xml; subtype=gml/3.1.1'; //TODO - maybe use another one ;-) 
					    }
					    //$e = new mb_exception("php/mod_linkedDataProxy.php item:". $item);
					    $features = $wfs->getFeatureById ( $collection, $forcedOutputFormat, $item, "2.0.0", "EPSG:4326" );
						//$e = new mb_exception($features);
						//use postgis to transform gml geometry to geojson - this maybe better ;-)
						
						// transform to geojson to allow rendering !
						// TODO test for ows:ExceptionReport!!!!
						$gml3Class = new Gml_3_Factory ();
						$gmlFeatureCache = $features;
						// create featuretype object
						// TODO
						$gml3Object = $gml3Class->createFromXml ( $features, null, $wfs, $myFeatureType, $geomColumnName );

						//$e = new mb_exception("after creation of object!");
						$geojsonList = new stdClass ();
						$geojsonList->type = "FeatureCollection";
						$geojsonList->features = array ();
						$geojsonBbox = array ();
						$geojsonIndex = 0;
						$minxFC = 90;
						$minyFC = 180;
						$maxxFC = - 90;
						$maxyFC = - 180;
						// TODO write javascript object if to var if html is requested
						if ($f == 'html') {
							$geoJsonVariable = "";
							$geoJsonVariable .= '<script>' . $newline;
						}
						//$e = new mb_exception("number of features: ".count($gml3Object->featureCollection->featureArray));
						foreach ( $gml3Object->featureCollection->featureArray as $mbFeature ) {
							// bbox
							$geojsonBbox [$geojsonIndex]->mbBbox = $mbFeature->getBbox ();
							// transform to simple bbox object for leaflet
							$bbox_new = explode ( ' ', str_replace ( ']', '', str_replace ( '[', '', $geojsonBbox [$geojsonIndex]->mbBbox ) ) );
							$bbox_new = explode ( '|', str_replace ( ')', '', str_replace ( '(', '', str_replace ( ')(', '|', $bbox_new [0] ) ) ) );
							$bbox_min = explode ( ',', $bbox_new [0] );
							$bbox_max = explode ( ',', $bbox_new [1] );
							$geojsonBbox [$geojsonIndex]->minx = $bbox_min [0];
							$geojsonBbox [$geojsonIndex]->miny = $bbox_min [1];
							$geojsonBbox [$geojsonIndex]->maxx = $bbox_max [0];
							$geojsonBbox [$geojsonIndex]->maxy = $bbox_max [1];
							if ($minxFC > $geojsonBbox [$geojsonIndex]->minx) {
								$minxFC = $geojsonBbox [$geojsonIndex]->minx;
							}
							if ($minyFC > $geojsonBbox [$geojsonIndex]->miny) {
								$minyFC = $geojsonBbox [$geojsonIndex]->miny;
							}
							if ($maxxFC < $geojsonBbox [$geojsonIndex]->maxx) {
								$maxxFC = $geojsonBbox [$geojsonIndex]->maxx;
							}
							if ($maxyFC < $geojsonBbox [$geojsonIndex]->maxy) {
								$maxyFC = $geojsonBbox [$geojsonIndex]->maxy;
							}
							// get geomtype
							$geomType = json_decode ( $mbFeature->toGeoJSON () )->geometry->type;
							$geojsonList->features [] = json_decode ( $mbFeature->toGeoJSON () );
							$geojsonIndex ++;
						}
						if ($admin != false && $admin->getWfsLogTag($wfsid) == 1) {
							//get price out of db
							$price = intval($admin->getWfsPrice($wfsid));
							$log_id = $admin->logWfsProxyRequest($wfsid, $userId, "OGC API Features Proxy", $price, 0, $ftName);
						} else {
							$log_id = false;
						}
						if ($log_id !== false) {
							$admin->updateWfsLog(1, '', '', $geojsonIndex, $log_id);
						}
					} // end if of supported and requested json format
					  // resolve json-schema *********************************************************
					$resolveJsonSchema = getJsonSchemaObject ( $geojsonList->features [0] );
					$schemaObject = $resolveJsonSchema->schema;
					// json-ld
					$resolveJsonLd = getJsonLdObject ( $geojsonList->features [0] );
					$ldObject = $resolveJsonLd->schema;
					// add url to
					foreach ( $geojsonList->features as $feature ) {
						if ($resolveJsonSchema->success = true) {
							$feature->{'$schema'} = $resolveJsonSchema->url;
						}
						if ($resolveJsonLd->success = true) {
							$feature->{'$context'} = $resolveJsonLd->url;
						}
					}
					// *****************************************************************************
					if ($f == 'html') {
						if ($resolveJsonSchema->success = true) {
							//$geojsonListView = mapFeatureKeys($geojsonList, $schemaObject);
							$geoJsonVariable .= "var feature_schema=". json_encode ( $schemaObject ) . ";";
						}
						$geoJsonVariable .= "var feature_" . $geomType . "=" . json_encode ( $geojsonList ) . ";";
						$geoJsonVariable .= $newline . "</script>" . $newline;
					}
					$usedProxyTime = microtime_float () - $proxyStartTime;
					$returnObject = $geojsonList->features [0];
					
					// integrate json-ld @context if it is resovable!*******************************************************************************
					/*
					 * $ldContextConnector = new Connector();
					 * $url = "http://localhost/mapbender/geoportal/".str_replace(":","__",$ftName).".jsonld";
					 * $file = $ldContextConnector->load($url);
					 * //$e = new mb_exception($file);
					 * $contextObject = json_decode($file);
					 * //$e = new mb_exception(json_encode($contextObject));
					 * $returnObject->properties->{'@context'} = $contextObject->{'@context'};
					 */
					// integrate json-ld @context if it is resovable!*******************************************************************************
					// integrate json-schema @id if it is resolvable! - {json-schema_0.7_id} attribute to avoid problems with gml encoding and @ in element name! *******************************************************************************
					// $schemaObject =
					
					// $e = new mb_exception(json_encode($contextObject));
					// $returnObject->properties->{'@context'} = $contextObject->{'@context'};
					// integrate json-ld @context if it is resovable!*******************************************************************************
					
					// add service title and collection title for navigation
					// for rlp
					$returnObject->serviceTitle = $wfs->title;
					$returnObject->collectionId = $myFeatureType->id;
					$returnObject->collectionName = $ftName;
					$returnObject->collectionTitle = $myFeatureType->title;
					// end rlp specific
					$returnObject->links [0]->href = get2Rest ( $_SERVER ['REQUEST_URI'] );
					$returnObject->links [0]->rel = "self";
					$returnObject->links [0]->type = "application/geo+json";
					$returnObject->links [0]->title = "this document";
					
					$returnObject->links [1]->href = get2Rest ( $_SERVER ['REQUEST_URI'] );
					$returnObject->links [1]->rel = "alternate";
					$returnObject->links [1]->type = "text/html";
					$returnObject->links [1]->title = "this document as HTML";
					
					$returnObject->links [2]->href = get2Rest ( $_SERVER ['REQUEST_URI'] );
					$returnObject->links [2]->rel = "alternate";
					$returnObject->links [2]->type = "application/gml+xml;profile=\"http://www.opengis.net/def/profile/ogc/2.0/gml-sf2\";version=3.2";
					$returnObject->links [2]->title = "this document as GML";
				}
			} else {
				$returnObject->success = false;
				$returnObject->message = "Collection/Featuretype not available in service!";
			}
		}
	}
}

// ************************************************************************************************************************************
// item part
// ************************************************************************************************************************************

switch ($f) {
	case "json" :
		//header ( "Content-type: application/json" );
		//define header type - if only wfsid is given, give application/json
		if (! isset ( $collection ) || $collection == "" || $collections == "all" || $collections == "api") {
			header ( "Content-type: application/json" );
		} else {
			header ( "Content-type: application/vnd.geo+json" );
		}
		echo json_encode ( $returnObject );
		break;
	case "xml" :
		header ( "Content-type: application/xml" );
		//Content-type: application/xhtml+xml; charset=UTF-8
		//header ( "application/gml+xml; version=3.2; profile=http://www.opengis.net/def/profile/ogc/2.0/gml-sf0" );
		echo $gmlFeatureCache;
		break;
	case "html" :
		$js1 = '<script>' . $newline;
		// https://stackoverflow.com/questions/5997450/append-to-url-and-refresh-page
		$js1 .= "	function checkActivation() {";
		$js1 .= "	    alert('test');";
		$js1 .= "	    var button = document.getElementById(\"filterAdd\");";
		$js1 .= "	    //activate button if that text is filled out";
		
		$js1 .= "	    "; // function myFunction(item) { var element = document.getElementById(item);element.classList.toggle(\'show\');";
		$js1 .= "	}";
		
		$js1 .= "	function URL_add_parameter(url, param, value){" . " ";
		$js1 .= "	    var hash       = {};";
		$js1 .= "	    var parser     = document.createElement('a');";
		$js1 .= "	    parser.href    = url;";
		$js1 .= "	    var parameters = parser.search.split(/\?|&/);";
		$js1 .= "	    for(var i=0; i < parameters.length; i++) {";
		$js1 .= "	        if(!parameters[i])";
		$js1 .= "	            continue;";
		$js1 .= "	        var ary      = parameters[i].split('=');";
		$js1 .= "	        hash[ary[0]] = ary[1];";
		$js1 .= "	    }";
		$js1 .= "	    hash[param] = value;";
		$js1 .= "	    var list = [];  ";
		$js1 .= "	    Object.keys(hash).forEach(function (key) {";
		$js1 .= "	        list.push(key + '=' + hash[key]);";
		$js1 .= "	    });";
		$js1 .= "	    parser.search = '?' + list.join('&');";
		$js1 .= "	    return parser.href;";
		$js1 .= "	}";
		$js1 .= "	function URL_remove_parameter(url, param){";
		$js1 .= "	    var hash       = {};";
		$js1 .= "	    var parser     = document.createElement('a');";
		$js1 .= "	    parser.href    = url;";
		$js1 .= "	    var parameters = parser.search.split(/\?|&/);";
		$js1 .= "	    for(var i=0; i < parameters.length; i++) {";
		$js1 .= "	        if(!parameters[i])";
		$js1 .= "	            continue;";
		$js1 .= "	        var ary      = parameters[i].split('=');";
		$js1 .= "	        hash[ary[0]] = ary[1];";
		$js1 .= "	    }";
		$js1 .= "	    hash[param] = 0;";
		$js1 .= "	    var list = [];  ";
		$js1 .= "	    Object.keys(hash).forEach(function (key) {";
		$js1 .= "		if (key != param) {";
		$js1 .= "	        	list.push(key + '=' + hash[key]);";
		$js1 .= "		}";
		$js1 .= "	    });";
		$js1 .= "	    parser.search = '?' + list.join('&');";
		$js1 .= "	    return parser.href;";
		$js1 .= "	}";
		$js1 .= $newline . '</script>' . $newline;
		
		$js2 = '<script>' . $newline;
		//remove mapbox osm and add bkg topplus web open
		$js2 .= "var map = L.map('map', {center: [50, 7.44], zoom: 7, crs: L.CRS.EPSG4326});";
		$js2 .= "L.tileLayer.wms('https://sgx.geodatenzentrum.de/wms_topplus_open?',{";
        $js2 .= "	layers: 'web',";
        $js2 .= "	format: 'image/png',";
        $js2 .= "	attribution: 'BKG - 2021 - <a href=\'https://sg.geodatenzentrum.de/web_public/Datenquellen_TopPlus_Open.pdf\'  target=\'_blank\'>Datenquellen<a>'";
        $js2 .= "}).addTo(map);";
		/*
		$js2 .= "	var map = L.map('map').setView([50, 7.44], 7);";
		$js2 .= "	L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?";
		$js2 .= "access_token=pk.eyJ1IjoibWFwYm94IiwiYSI6ImNpejY4NXVycTA2emYycXBndHRqcmZ3N3gifQ.rJcFIG214AriISLbB6B5aw', {";
		$js2 .= "		maxZoom: 18,";
		$js2 .= "		attribution: 'Map data &copy; <a href=\"https://www.openstreetmap.org/\">OpenStreetMap</a> contributors, ' +";
		$js2 .= "			'<a href=\"https://creativecommons.org/licenses/by-sa/2.0/\">CC-BY-SA</a>, ' +";
		$js2 .= "			'Imagery © <a href=\"https://www.mapbox.com/\">Mapbox</a>',";
		$js2 .= "		id: 'mapbox.light'";
		$js2 .= "	}).addTo(map);";*/

		if (! isset ( $wfsid ) || ! isset ( $ft )) {
			$js2 .= 'document.getElementById("map").style.display = "none"; ';
			// $js2 .= 'document.getElementById("bboxButtons").style.display = "none"; ';
		}
		$js2 .= $newline . '</script>' . $newline;
		$newline = " ";
		// define header and navigation bar
		$html = '';
		$html .= '<!DOCTYPE html>' . $newline;
		$html .= '<html>' . $newline;
		$html .= '<head>' . $newline;
		$html .= '<title>' . $title . '</title>' . $newline;
		$html .= '<meta http-equiv="X-UA-Compatible" content="IE=edge">' . $newline;
		$html .= '<meta charset="utf-8" />' . $newline;
		$html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . $newline;
		// $html .= '<link rel="shortcut icon" type="image/x-icon" href="" />';
		// leaflet css
		$html .= '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.5.1/dist/leaflet.css" integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ==" crossorigin=""/>' . $newline;
		// leaflet js
		$html .= '<script src="https://unpkg.com/leaflet@1.5.1/dist/leaflet.js" integrity="sha512-GffPMF3RvMeYyc1LWMHtK8EbPv0iNZ8/oTtHPx9/cc2ILxQ+u905qIwdpULaqDkyBKgOaB57QTMg7ztg8Jm2Og==" crossorigin=""></script>' . $newline;
		// bootstrap
		if ($useInternalBootstrap == true) {
			if ($behindRewrite == true) {
				$html .= '<link rel="stylesheet" href="' . MAPBENDER_PATH . '/extensions/bootstrap-4.0.0-dist/css/bootstrap.min.css">' . $newline;
			} else {
				$html .= '<link rel="stylesheet" href="../extensions/bootstrap-4.0.0-dist/css/bootstrap.min.css">' . $newline;
			}
		} else {
			$html .= '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css" integrity="sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb" crossorigin="anonymous">' . $newline;
		}
		$html .= '<link rel="stylesheet" href="' . $cssFile . '">' . $newline;
		// own styles - mapviewer ...
		$html .= '<style>
	#map {
		width: 600px;
		height: 400px;
	}
</style>' . $newline;
		// ************************************************************************************************************************************
		$html .= '<body>' . $newline;
		// ************************************************************************************************************************************
		// navbar
		$html .= '<nav class="navbar navbar-light bg-light navbar-expand-sm">' . $newline;
		$html .= '<div class="container">' . $newline;
		$html .= '<div id="navbar" class="navbar-collapse collapse d-flex justify-content-between align-items-center">' . $newline;
		$html .= '<ol class="breadcrumb bg-light my-0 pl-0">' . $newline;
		//
		if (! isset ( $wfsid )) { // && (!isset($collection) || ($collections != 'all'))) {
			$html .= '<li class="breadcrumb-item active">Datasets</li>' . $newline;
			$html .= '</ol>';
		} else {
			if (! isset ( $collection ) || $collections == 'all') {
				$html .= '<li class="breadcrumb-item"><a href="' . get2Rest ( rtrim ( delTotalFromQuery ( array (
						"f",
						"wfsid",
						"nativeJson" 
				), $_SERVER ['REQUEST_URI'] ), '?' ) ) . '">Datasets</a></li>' . $newline; // TODO - use base uri
				$html .= '<li class="breadcrumb-item active">' . $returnObject->title . '</li>' . $newline;
				$html .= '</ol>';
			} else {
				if (! isset ( $item ) || $items == 'all') {
					$html .= '<li class="breadcrumb-item"><a href="' . get2Rest ( rtrim ( delTotalFromQuery ( array_merge ( array (
							"f",
							"wfsid",
							"collection",
							"collections",
							"item",
							"items",
							"limit",
							"offset",
							"bbox",
							"nativeJson" 
					), $stringFilterActive ), $_SERVER ['REQUEST_URI'] ), '?' ) ) . '">Datasets</a></li>' . $newline; // TODO - use base uri
					$html .= '<li class="breadcrumb-item"><a href="' . get2Rest ( rtrim ( delTotalFromQuery ( array_merge ( array (
							"f",
							"collection",
							"collections",
							"item",
							"items",
							"limit",
							"offset",
							"bbox",
							"nativeJson" 
					), $stringFilterActive ), $_SERVER ['REQUEST_URI'] ), '?' ) ) . '">' . $returnObject->serviceTitle . '</a></li>' . $newline;
					$html .= '<li class="breadcrumb-item active">' . $returnObject->collectionTitle . '</li>' . $newline;
					$html .= '</ol>';
				} else {
					$html .= '<li class="breadcrumb-item"><a href="' . get2Rest ( rtrim ( delTotalFromQuery ( array_merge ( array (
							"f",
							"wfsid",
							"collection",
							"collections",
							"item",
							"items",
							"limit",
							"offset",
							"bbox",
							"nativeJson" 
					), $stringFilterActive ), $_SERVER ['REQUEST_URI'] ), '?' ) ) . '">Datasets</a></li>' . $newline; // TODO - use base uri
					$html .= '<li class="breadcrumb-item"><a href="' . get2Rest ( rtrim ( delTotalFromQuery ( array_merge ( array (
							"f",
							"collection",
							"collections",
							"item",
							"items",
							"limit",
							"offset",
							"bbox",
							"nativeJson" 
					), $stringFilterActive ), $_SERVER ['REQUEST_URI'] ), '?' ) ) . '">' . $returnObject->serviceTitle . '</a></li>' . $newline;
					$html .= '<li class="breadcrumb-item"><a href="' . get2Rest ( rtrim ( delTotalFromQuery ( array_merge ( array (
							"f",
							"item",
							"limit",
							"offset",
							"bbox",
							"nativeJson" 
					), $stringFilterActive ), $_SERVER ['REQUEST_URI'] ), '?' ) . '&items=all' ) . '">' . $returnObject->collectionTitle . '</a></li>' . $newline;
					$html .= '<li class="breadcrumb-item active">' . $returnObject->id . '</li>' . $newline;
					$html .= '</ol>';
				}
			}
			// other formats ! for collection, item, ...
			// if (!isset($item)) {
			$html .= '<ul class="list-separated m-0 p-0 text-muted">' . $newline;
			$html .= '    <li><a href="' . get2Rest ( rtrim ( delTotalFromQuery ( "f", $_SERVER ['REQUEST_URI'] ), '?' ) . '&f=json' ) . '" target="_blank">GeoJSON</a></li>' . $newline;
			if (isset ( $collection ) || $collections == 'all') {
				$html .= '    <li><a href="' . get2Rest ( rtrim ( delTotalFromQuery ( "f", $_SERVER ['REQUEST_URI'] ), '?' ) . '&f=xml' ) . '" target="_blank">GML</a></li>' . $newline;
			} else {
				$html .= '    <li><a href="' . get2Rest ( rtrim ( delTotalFromQuery ( "f", $_SERVER ['REQUEST_URI'] ), '?' ) . '&f=xml' ) . '" target="_blank">XML</a></li>' . $newline;
			}
			$html .= '</ul> ' . $newline;
			// }
			//
		}
		//
		$html .= '</ol>' . $newline;
		$html .= '<ul class="list-separated m-0 p-0 text-muted">' . $newline;
		$html .= '</ul>' . $newline;
		$html .= '</div>' . $newline;
		$html .= '<!--button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">' . $newline;
		$html .= '<span class="navbar-toggler-icon"></span>' . $newline;
		$html .= '</button-->' . $newline;
		$html .= '</div>' . $newline;
		$html .= '</nav>' . $newline;
		//
		// logic
		if (! isset ( $wfsid )) {
			// header for proxy
			$html .= "";
			$html .= '<div class="container py-4">' . $newline;
			$html .= '    <div itemscope itemtype="http://schema.org/DataCatalog">' . $newline;
			$html .= '        <h1 itemprop="name">' . $title . '</h1>' . $newline;
			$html .= '        <p itemprop="description">' . $description . '</p>' . $newline;
			$html .= '        <p itemprop="url" class="d-none">' . $datasource_url . '</p>' . $newline;
			$html .= '        <br/>' . $newline;
			$html .= '        <br/>' . $newline;
			$html .= '        <br/>' . $newline;
			$html .= '        <ul class="list-unstyled space-after">' . $newline;
			foreach ( $returnObject->service as $service ) {
				$html .= '            <li itemprop="dataset" itemscope itemtype="http://schema.org/Dataset">' . $newline;
				$html .= '                <h2>' . $newline;
				$html .= '                    <a itemprop="url" href="' . get2Rest ( rtrim ( $_SERVER ['REQUEST_URI'], '?' ) . '?wfsid=' . $service->id ) . '">' . $newline;
				$html .= '                        <span itemprop="name">' . $service->title . ' (WFS ' . $service->version . ')</span>' . $newline;
				$html .= '                    </a>' . $newline;
				$html .= '                </h2>' . $newline;
				$html .= '                <span itemprop="description">' . $service->description . '</span>' . $newline;
				$html .= '                <span itemprop="sameAs" class="d-none">/ad-links</span>' . $newline;
				$html .= '            </li>' . $newline;
			}
			$html .= '        </ul>' . $newline;
		} else {
			if (! isset ( $collection ) || $collections == 'all') {
				// $e = new mb_exception(json_encode($returnObject));
				// show service and collection info
				// service
				$html .= "";
				$html .= '<div class="container py-4">' . $newline;
				$html .= '    <div itemscope itemtype="http://schema.org/Dataset">' . $newline;
				$html .= '        <h1 itemprop="name">' . $returnObject->title . '</h1>' . $newline;
				$html .= '        <span itemprop="description">' . $returnObject->description . '</span>' . $newline;
				$html .= '        <p itemprop="url" class="d-none">' . get2Rest ( $_SERVER ['REQUEST_URI'] ) . '</p>' . $newline; // TODO canonical url
				$html .= '        <div itemprop="includedInDataCatalog" itemscope itemtype="http://schema.org/Datacatalog" class="d-none">' . $newline;
				$html .= '            <div itemprop="url">https://www.ldproxy.nrw.de/' . get2Rest ( $_SERVER ['REQUEST_URI'] ) . '</div>' . $newline; // TODO canonical url
				$html .= '        </div>' . $newline;
				// ul 0 for keywords ...
				// ul 1..n for distribution - each a download url to a wfs featuretype in different formats!
				// new div for collections:
				// collection
				$html .= '        <div class="row my-3">' . $newline;
				$html .= '            <div class="col-md-2 font-weight-bold">Collections</div>' . $newline;
				$html .= '            <div class="col-md-10">' . $newline;
				$html .= '                <ul class="list-unstyled">' . $newline;
				foreach ( $returnObject->collections as $collection ) {
					// get url to further page
					foreach ( $collection->links as $link ) {
						if ($link->rel == 'item') {
							$collectionHtmlUrl = delTotalFromQuery ( "f", $link->href ) . "&f=html";
							break;
						}
					}
					$html .= '                    <li>' . $newline;
					$html .= '                        <a href="' . $collectionHtmlUrl . '">' . $collection->title . '</a>' . $newline;
					$html .= '                    </li>' . $newline;
				}
				$html .= '            </div>' . $newline;
				$html .= '        </div>' . $newline;
				// further information about the service API TODO
				$html .= '        <div class="row my-3">' . $newline;
				$html .= '            <div class="col-md-2 font-weight-bold">API Definition</div>' . $newline;
				$html .= '            <div class="col-md-10">' . $newline;
				$html .= '                <a href="' . get2Rest ( $_SERVER ['REQUEST_URI'] . "&collections=api" ) . '">' . get2Rest ( $_SERVER ['REQUEST_URI'] . "&collections=api" ) . '</a>' . $newline;
				$html .= '            </div>' . $newline;
				$html .= '        </div>' . $newline;
				// further information about the service DATASOURCE TODO
				$html .= '        <div class="row my-3">' . $newline;
				$html .= '            <div class="col-md-2 font-weight-bold">' . _mb ( 'Data source' ) . '</div>' . $newline;
				$html .= '            <div class="col-md-10">' . $newline;
				$html .= '                <a itemprop="isBasedOn" href="' . $returnObject->accessUrl . '" target="_blank">' . $returnObject->accessUrl . '</a>' . $newline;
				$html .= '            </div>' . $newline;
				$html .= '        </div>' . $newline;
				// further information about the service PROVIDER TODO
				$html .= '        <div class="row my-3">' . $newline;
				$html .= '            <div class="col-md-2 font-weight-bold">' . _mb ( 'Provider' ) . '</div>' . $newline;
				$html .= '            <div class="col-md-10">' . $newline;
				$html .= '                <ul class="list-unstyled" itemprop="creator" itemscope itemtype="http://schema.org/Organization">' . $newline;
				$html .= '                    <li itemprop="name">' . $returnObject->provider . '</li>' . $newline;
				$html .= '                    <li itemprop="url"><a href="' . $returnObject->providerHomepage . '" target="_blank">' . $returnObject->providerHomepage . '</a></li>' . $newline;
				$html .= '                    <li itemprop="contactPoint" itemscope itemtype="http://schema.org/ContactPoint">' . $newline;
				$html .= '                        <span class="d-none" itemprop="contactType">technical support</span>' . $newline;
				$html .= '                        <ul class="list-unstyled">' . $newline;
				$html .= '                            <li itemprop="email">' . $returnObject->providerEmail . '</li>' . $newline;
				$html .= '                            <li itemprop="url" class="d-none">' . $returnObject->providerHomepage . '</li>' . $newline;
				$html .= '                        </ul>' . $newline;
				$html .= '                    </li>' . $newline;
				$html .= '                </ul>' . $newline;
				$html .= '            </div>' . $newline;
				$html .= '        </div>' . $newline;
				// further information about the service LICENSE TODO
				$html .= '        <div class="row my-3">' . $newline;
				$html .= '        <div class="col-md-2 font-weight-bold">' . _mb ( 'License' ) . '</div>' . $newline;
				$html .= '         <div class="col-md-10">' . $newline;
				$html .= '        <div itemprop="license">' . $returnObject->license . '</div>' . $newline;
				$html .= '        </div>' . $newline;
				$html .= '        </div>' . $newline;
				$html .= '        <div itemprop="temporalCoverage" class="d-none">2018-05-18T14:45:11.573Z/2019-08-05T06:27:56.536Z</div>' . $newline; // TODO
				$html .= '        <div itemprop="spatialCoverage" itemscope itemtype="http://schema.org/Place" class="d-none"><div itemprop="geo" itemscope itemtype="http://schema.org/GeoShape"><div itemprop="box">50.237351 5.612726 52.528630 9.589634</div>' . $newline; // TODO
				$html .= '        </div>' . $newline;
				$html .= '        </div>' . $newline;
				$html .= '    </div>' . $newline;
				$html .= '</div>' . $newline;
			} else {
				// collection is selected - show items
				// if (!isset($item) || $items == 'all') { //new for items and itemlists!
				
				$html .= '<div class="container py-4">' . $newline;
				if (! isset ( $item ) || $items == "all") {
					$html .= '    <div>' . $newline;
					$html .= '        <h1>' . $returnObject->collectionTitle . ' (' . $numberOfObjects . ')' . '</h1>' . $newline;
					$html .= '        <span></span>' . $newline;
					$html .= '        <br/>' . $newline;
					$html .= '        <br/>' . $newline;
					$html .= '    </div>' . $newline;
					// TODO - further filter options
					$html .= '<div id="app-wrapper" class="mb-5">' . $newline;
					$html .= '<div class="row mb-3">' . $newline;
					$html .= '<div class=" flex-row justify-content-start align-items-center flex-wrap col-md-3">' . $newline;
					$html .= '<span class="mr-2 font-weight-bold">Filter</span>' . $newline;
					if (isset ( $bbox ) && $bbox != "") {
						$html .= '<div class="mr-1 my-1 btn-group"><button disabled="" style="opacity: 1;" class="py-0 btn btn-primary btn-sm disabled">bbox≈' . $bbox . '</button><button type="button" aria-haspopup="true" aria-expanded="false" class="py-0 btn btn-danger btn-sm" onclick="location.href = URL_remove_parameter(URL_remove_parameter(location.href, \'bbox\'), \'offset\');return false;">×</button></div>' . $newline;
					}
					if (isset ( $nativeJson ) && $nativeJson == true) {
						$html .= '<div class="mr-1 my-1 btn-group"><button disabled="" style="opacity: 1;" class="py-0 btn btn-primary btn-sm disabled">nativeJson=true</button><button type="button" aria-haspopup="true" aria-expanded="false" class="py-0 btn btn-danger btn-sm" onclick="location.href = URL_remove_parameter(location.href, \'nativeJson\');return false;">×</button></div>' . $newline;
					}
					// for each other set parameter show***********************************************
					// variable with parameters:
					foreach ( $stringFilterArray as $stringFilter ) {
						$html .= '<div class="mr-1 my-1 btn-group"><button disabled="" style="opacity: 1;" class="py-0 btn btn-primary btn-sm disabled">' . $stringFilter->elementName . '=' . $stringFilter->elementFilter . '</button><button type="button" aria-haspopup="true" aria-expanded="false" class="py-0 btn btn-danger btn-sm" onclick="location.href = URL_remove_parameter(URL_remove_parameter(location.href, \'' . $stringFilter->elementName . '\'), \'offset\');return false;">×</button></div>' . $newline;
					}
					// $stringFilterArray[$stringFilterIndex]->elementName = $ftAllowedAttribute;
					// $stringFilterArray[$stringFilterIndex]->elementFilter = $testMatch;
					// ********************************************************************************
					$html .= '<button type="button" id="edit_filter_button" class="py-0 btn btn-outline-secondary btn-sm collapse show" onclick="var elements = [\'edit_filter_button\',  \'cancel_filter_button\', \'filter_div\']; elements.forEach(myFunction); function myFunction(item) { var element = document.getElementById(item);element.classList.toggle(\'show\'); };">' . _mb ( 'Edit' ) . '</button>';
					// $html .= '<button type="button" id="apply_filter_button" class="py-0 btn btn-outline-secondary btn-sm collapse" onclick="">Apply</button>';
					$html .= '<button type="button" id="cancel_filter_button" class="py-0 btn btn-outline-secondary btn-sm collapse" onclick="var elements = [\'edit_filter_button\', \'cancel_filter_button\', \'filter_div\']; elements.forEach(myFunction); function myFunction(item) { var element = document.getElementById(item);element.classList.toggle(\'show\'); };">' . _mb ( 'Cancel' ) . '</button>' . $newline;
					// bbox filter part from ldproxy
					$html .= '<div id="filter_div" class="collapse">' . $newline;
					// nativeJson Filter - to use if some memory error occur -
					if (in_array ( 'application/json; subtype=geojson', explode ( ',', $ftOutputFormats ) )) {
						$html .= '    <form class="">' . $newline;
						$html .= '        <p class="text-muted text-uppercase" title="Use nativeJson if errors occur - it may also be faster, but the objects don\'t have persistent IDs!">' . _mb ( 'Serverside format' ) . '</p>' . $newline;
						$html .= '		    <div class="col-md-2">' . $newline;
						$html .= '			<button type="button" class="btn btn-primary btn-sm" onclick="location.href = URL_add_parameter(location.href, \'nativeJson\', \'true\');return false;">geoJson</button>' . $newline;
						$html .= '		    </div>' . $newline;
						$html .= '	    </form>' . $newline;
					}
					// paging options
					// allowedLimits
					$html .= '    <form class="">' . $newline;
					$html .= '        <p class="text-muted text-uppercase">' . _mb ( 'Results per page' ) . '</p>' . $newline;
					$html .= '                <select id="rppSelection" name="field" type="select" class="mr-2 text-muted form-control-sm form-control" onchange="location.href = URL_add_parameter(location.href, \'limit\', document.getElementById(\'rppSelection\').value);">' . $newline;
					$html .= '                    <option value="" class="d-none">' . $limit . '</option>' . $newline;
					foreach ( $allowedLimits as $rpp ) {
						$html .= '                    <option value="' . $rpp . '">' . $rpp . '</option>' . $newline;
					}
					$html .= '               </select>' . $newline;
					$html .= '	    </form>' . $newline;
					//
					$html .= '    <form class="">' . $newline;
					$html .= '        <p class="text-muted text-uppercase">bbox</p>' . $newline;
					$html .= '		<div class="row">' . $newline;
					$html .= '		    <div class="col-md-5">' . $newline;
					$html .= '		        <div class="form-group">' . $newline;
					$html .= '			    <input name="minLng" id="minLng" readonly="" class="mr-2 form-control-sm form-control" value="5.767822265625001" type="text">' . $newline;
					$html .= '			</div>' . $newline;
					$html .= '		    </div>' . $newline;
					$html .= '		    <div class="col-md-5">' . $newline;
					$html .= '		        <div class="form-group">' . $newline;
					$html .= '			    <input name="minLat" id="minLat" readonly="" class="mr-2 form-control-sm form-control" value="50.317408112618715" type="text">' . $newline;
					$html .= '		        </div>' . $newline;
					$html .= '		    </div>' . $newline;
					$html .= '	        </div>' . $newline;
					$html .= '	        <div class="row">' . $newline;
					$html .= '		    <div class="col-md-5">' . $newline;
					$html .= '		        <div class="form-group">' . $newline;
					$html .= '			    <input name="maxLng" id="maxLng" readonly="" class="mr-2 form-control-sm form-control" value="9.459228515625002" type="text">' . $newline;
					$html .= '		        </div>' . $newline;
					$html .= '		    </div>' . $newline;
					$html .= '		    <div class="col-md-5">' . $newline;
					$html .= '		        <div class="form-group">' . $newline;
					$html .= '  		            <input name="maxLat" id="maxLat" readonly="" class="mr-2 form-control-sm form-control" value="52.52958999943304" type="text">' . $newline;
					$html .= '		        </div>' . $newline;
					$html .= '		    </div>' . $newline;
					$html .= '		    <div class="col-md-2">' . $newline;
					$html .= '			<button type="button" class="btn btn-primary btn-sm" onclick="location.href = URL_add_parameter(URL_remove_parameter(location.href, \'p\'), \'bbox\', map.getBounds().getWest()+\',\'+map.getBounds().getSouth()+\',\'+map.getBounds().getEast()+\',\'+map.getBounds().getNorth());return false;">' . _mb ( 'Add' ) . '</button>' . $newline;
					$html .= '		    </div>' . $newline;
					$html .= '          </div>' . $newline;
					$html .= '	    </form>' . $newline;
					if (is_array ( $ftAllowedAttributesArray ) && count ( $ftAllowedAttributesArray ) > 0) {
						$html .= '<form class="">' . $newline;
						$html .= '    <p class="text-muted text-uppercase">' . _mb ( 'field' ) . '</p>' . $newline;
						$html .= '    <div class="row">' . $newline;
						$html .= '        <div class="col-md-5">' . $newline;
						$html .= '            <div class="form-group">' . $newline;
						$html .= '                <select id="attributeSelection" name="field" type="select" class="mr-2 text-muted form-control-sm form-control" onchange="document.getElementById(\'filterValue\').value = \'\';">' . $newline;
						$html .= '                    <option value="" class="d-none">none</option>' . $newline;
						foreach ( $ftAllowedAttributesArray as $ftAllowedAttribute ) {
							if (isset($schemaObject)) {
								//$e = new mb_exception("schemaObject set!");
								//exchange titles of options with values from schema and maybe add examples	
								if (isset($schemaObject->properties->{$ftAllowedAttribute}) && $schemaObject->properties->{$ftAllowedAttribute}->title != "" ) {
									$ftAllowedAttributeTitle = $schemaObject->properties->{$ftAllowedAttribute}->title;
									$ftAllowedAttributeDescription = $schemaObject->properties->{$ftAllowedAttribute}->description;
									$ftAllowedAttributeDescription .= " - Attribute: ".$ftAllowedAttribute." - type: [".$schemaObject->properties->{$ftAllowedAttribute}->type."]";
								} else {
									$ftAllowedAttributeTitle = $ftAllowedAttribute;
									$ftAllowedAttributeDescription = $ftAllowedAttribute;
								}
								
							} else {
								$ftAllowedAttributeTitle = $ftAllowedAttribute;
								$ftAllowedAttributeDescription = $ftAllowedAttribute;
							}
							$html .= '                    <option title="'.$ftAllowedAttributeDescription.'" value="' . $ftAllowedAttribute . '">' . $ftAllowedAttributeTitle . '</option>' . $newline;
						}
						$html .= '               </select>' . $newline;
						$html .= '            </div>' . $newline;
						$html .= '        </div>' . $newline;
						$html .= '        <div class="col-md-5">' . $newline;
						$html .= '            <div class="form-group">' . $newline;
						$html .= '                <input id="filterValue" name="filterValue" placeholder="filter pattern" class="mr-2 form-control-sm form-control" value="" type="text">' . $newline;
						$html .= '                    <small class="form-text text-muted">' . _mb ( 'Use * as wildcard' ) . '</small>' . $newline;
						$html .= '                    <small class="form-text text-muted" id="filterNameErrMsg"></small>' . $newline;
						$html .= '            </div>' . $newline;
						$html .= '        </div>' . $newline;
						$html .= '        <div class="col-md-2">' . $newline;
						$html .= '            <button id="filterValueAddButton" name="filterValueAddButton" type="button" disabled="" class="btn btn-primary btn-sm disabled" onclick="var attributeSelection = document.getElementById(\'attributeSelection\'); var filterValue = document.getElementById(\'filterValue\'); var selectionValue = attributeSelection.options[attributeSelection.selectedIndex].value; location.href = URL_add_parameter(URL_remove_parameter(location.href, \'p\'), selectionValue, filterValue.value );return false;">' . _mb ( 'Add' ) . '</button>' . $newline;
						$html .= '        </div>' . $newline;
						$html .= '    </div>' . $newline;
						// add script
						$html .= '<script>';
						$html .= 'const filterValue = document.getElementById(\'filterValue\');';
						$html .= 'const filterErrMsgHolder = document.getElementById(\'filterNameErrMsg\');';
						$html .= 'const filterValueAddButton = document.getElementById(\'filterValueAddButton\');';
						$html .= 'const attributeSelection = document.getElementById(\'attributeSelection\');';
						$html .= 'function checkFilterValue() {';
						$html .= 'var inputValue = filterValue.value;';
						$html .= '    if (attributeSelection.options[attributeSelection.selectedIndex].value == "") {';
						$html .= '        filterErrMsgHolder.innerHTML =';
						$html .= '            \'Please select an attribute on the left side before setting the filter value\';';
						$html .= '        return false;';
						$html .= '    } else if (inputValue.length < 1) {';
						$html .= '        filterErrMsgHolder.innerHTML =';
						$html .= '            \'Please enter a text with at least 1 letters\';';
						$html .= '        return false;';
						$html .= '    } else if (!(/^\S{1,}$/.test(inputValue))) {';
						$html .= '        filterErrMsgHolder.innerHTML =';
						$html .= '            \'Name cannot contain whitespace\';';
						$html .= '        return false;';
						$html .= '    } else if(!(/^[a-zA-Z*0-9\-]+$/.test(inputValue)))';
						$html .= '    {';
						$html .= '       filterErrMsgHolder.innerHTML=';
						$html .= '                \'Only alphabets or * are allowed\'';
						$html .= '    }';
						// $html .= ' else if(!(/^(?:(\w)(?!\1\1))+$/.test(inputValue)))';
						// $html .= ' {';
						// $html .= ' filterErrMsgHolder.innerHTML=';
						// $html .= ' \'per 3 alphabets allowed\'';
						// $html .= ' }';
						$html .= '    else {';
						$html .= '        filterErrMsgHolder.innerHTML = \'\';';
						$html .= '        return true;';
						$html .= '    }       ';
						$html .= '}';
						$html .= 'filterValue.addEventListener(\'keyup\', function (event) {';
						$html .= '    isValidFilter = checkFilterValue();';
						$html .= '    if ( isValidFilter && attributeSelection.options[attributeSelection.selectedIndex].value != "") {';
						$html .= '        filterValueAddButton.classList.remove("disabled");';
						$html .= '        filterValueAddButton.disabled = false;';
						$html .= '    } else {';
						// $html .= ' filterValueAddButton.classList.add("disabled");';
						$html .= '        filterValueAddButton.disabled = true;';
						$html .= '   }';
						$html .= '});';
						$html .= '</script>';
						$html .= '</form>' . $newline;
					}
					$html .= '</div>' . $newline;
					$html .= '</div>' . $newline;
					$html .= '</div>' . $newline;
					$html .= '</div>' . $newline;
					if ($map_position == "below_filter") {
						$html .= '<div id="map"></div>' . $newline;
					}
				}
				// Navigation elements
				$html .= '    <div class="row">' . $newline;
				$html .= '        <div class="col-md-6">' . $newline;
				if (! isset ( $item ) || $items == 'all') {
					// generate page navigation**********************************************************************************************************************************************************
					$nav = "";
					$nav .= '            <nav>' . $newline;
					$nav .= '                <ul class="pagination mb-4">' . $newline;
					// get next link from returned object
					foreach ( $returnObject->links as $link ) {
						if ($link->rel == 'next') {
							$nextItemsHtmlUrl = delTotalFromQuery ( "f", $link->href ) . "&f=html";
						}
						if ($link->rel == 'last') {
							$lastItemsHtmlUrl = delTotalFromQuery ( "f", $link->href ) . "&f=html";
						}
					}
					// calculate current page number from offset
					//
					$page = floor ( $offset / $limit );
					$nav .= '                    <li class="page-item"><a class="page-link" href="' . delTotalFromQuery ( array (
							'offset' 
					), $nextItemsHtmlUrl ) . '&offset=0">' . _mb ( "first page" ) . '</a></li>' . $newline;
					if ($offset > 0) {
						$nav .= '                    <li class="page-item">';
					} else {
						$nav .= '                    <li class="page-item disabled">';
					}
					$nav .= '<a class="page-link" href="' . delTotalFromQuery ( array (
							'offset' 
					), $nextItemsHtmlUrl ) . '&offset=' . ($offset - $limit) . '">‹</a></li>' . $newline;
					$nav .= '                    <li class="page-item active"><a class="page-link" href="">' . ($page + 1) . ' (' . ($numberOfPages) . ')</a></li>' . $newline;
					// only activate next when page < numberOfPages
					if ($page < ($numberOfPages - 1)) {
						$nav .= '                    <li class="page-item"><a class="page-link" href="' . $nextItemsHtmlUrl . '">›</a></li>' . $newline;
					} else {
						$nav .= '                    <li class="page-item disabled"><a class="page-link" href="' . $nextItemsHtmlUrl . '">›</a></li>' . $newline;
					}
					// last page
					$nav .= '                    <li class="page-item"><a class="page-link" href="' . $lastItemsHtmlUrl . '">' . _mb ( "last page" ) . '</a></li>' . $newline;
					$nav .= '                </ul>' . $newline;
					$nav .= '            </nav>' . $newline;
					// end navigation elements
					$html .= $nav;
					// end page navigation**********************************************************************************************************************************************************
					$html .= '           <ul class="list-unstyled">' . $newline;
					$objIndex = 0;
					foreach ( $returnObject->features as $feature ) {
						$html .= '                <li>' . $newline;
						$html .= '                    <div  itemscope itemtype="http://schema.org/Place">' . $newline;
						$html .= '                        <h4 class="mt-3 mb-1"><a href="' . get2Rest ( delTotalFromQuery ( array (
								'items',
								'offset',
								'limit',
								'bbox' 
						), $_SERVER ['REQUEST_URI'] ) . '&item=' . $feature->id ) . '" target="_blank"><span itemprop="name">' . $feature->id . '</span></a></h4><a href=""  onclick="zoomToExtent(' . $geojsonBbox [$objIndex]->minx . "," . $geojsonBbox [$objIndex]->miny . "," . $geojsonBbox [$objIndex]->maxx . "," . $geojsonBbox [$objIndex]->maxy . ');return false;">' . _mb ( 'zoom to' ) . '</a>' . $newline;
						$html .= '                        <span class="d-none" itemprop="sameAs">https://www.ldproxy.nrw.de/topographie/collections/ax_bergbaubetrieb/items/DENWAT01D000CcF0</span>' . $newline;
						// foreach attribute
						foreach ( $feature->properties as $key => $value ) {
							if (isset ( $schemaObject->properties->{$key}->title )) {
								$attributeTitle = $schemaObject->properties->{$key}->title;
							} else {
								$attributeTitle = $key;
							}
							if (isset ( $schemaObject->properties->{$key}->description )) {
								$attributeDescription = $schemaObject->properties->{$key}->description;
							} else {
								$attributeDescription = $attributeTitle;
							}
							// inject semantic context if ldObject given
							
							$html .= '                        <div class="row my-1">' . $newline;
							$html .= '                            <div class="col-md-6 font-weight-bold text-truncate" title="' . $attributeDescription . '">' . $attributeTitle . '</div>' . $newline;
							// semantic annotations
							if (isset ( $ldObject->{'@context'}->{$key} )) {
								$uri = $ldObject->{'@context'}->{$key};
								$schemaOrgArray = explode ( "/", str_replace ( "https://", "", $uri ) );
								$schemaOrgObject = $schemaOrgArray [1];
								$schemaOrgAttribute = $schemaOrgArray [2];
								// $semAttribution = "vocab=\"https://schema.org/\" typeof=\"$schemaOrgObject\" property=\"$schemaOrgAttribute\"";
								// TODO - check semantics !!!! $semAttribution = "itemscope=\"\" itemtype=\"http://schema.org/".$schemaOrgObject."\" itemprop=\"$schemaOrgAttribute\"";
							} else {
								$semAttribution = "";
							}
							if (gettype ( $value ) == "string") {
								$html .= '                            <div class="col-md-6" ' . $semAttribution . '>' . string2html ( $value ) . '</div>' . $newline;
							} else {
								$html .= '                            <div class="col-md-6" ' . $semAttribution . '>' . json_encode ( $value ) . '</div>' . $newline;
							}
							$html .= '                        </div>' . $newline;
						}
						$html .= '                    </div>' . $newline;
						$html .= '                </li>' . $newline;
						$objIndex ++;
					}
					$html .= '            </ul>' . $newline;
					$html .= $nav;
				} else {
					// only one feature is found!
					if ($map_position == "below_filter") {
						$html .= '<div id="map"></div>' . $newline;
					}
					$feature = $returnObject;
					$html .= '                    <div  itemscope itemtype="http://schema.org/Place">' . $newline;
					$html .= '                        <h1 itemprop="name">' . $feature->id . '</h1>' . $newline;
					$html .= '                        <span class="d-none" itemprop="url">' . $_SERVER ['REQUEST_URI'] . '</span>' . $newline;
					// foreach attribute
					foreach ( $feature->properties as $key => $value ) {
						
						if (isset($schemaObject) && isset ( $schemaObject->properties->{$key}->title )) {
							$attributeTitle = $schemaObject->properties->{$key}->title;
						} else {
							$attributeTitle = $key;
						}
						if (isset ( $schemaObject->properties->{$key}->description )) {
							$attributeDescription = $schemaObject->properties->{$key}->description;
						} else {
							$attributeDescription = $attributeTitle;
						}
						$html .= '                        <div class="row my-1">' . $newline;
						$html .= '                            <div class="col-md-6 font-weight-bold text-truncate" title="' . $attributeDescription . '">' . $attributeTitle . '</div>' . $newline;
						// semantic annotations
						if (isset ( $ldObject->{'@context'}->{$key} )) {
							$uri = $ldObject->{'@context'}->{$key};
							$schemaOrgArray = explode ( "/", str_replace ( "https://", "", $uri ) );
							$schemaOrgObject = $schemaOrgArray [1];
							$schemaOrgAttribute = $schemaOrgArray [2];
							// $semAttribution = "vocab=\"https://schema.org/\" typeof=\"$schemaOrgObject\" property=\"$schemaOrgAttribute\"";
							// TODO - check semantics !!!! $semAttribution = "itemscope=\"\" itemtype=\"http://schema.org/".$schemaOrgObject."\" itemprop=\"$schemaOrgAttribute\"";
						} else {
							$semAttribution = "";
						}
						if (gettype ( $value ) == "string") {
							$html .= '                            <div class="col-md-6" ' . $semAttribution . '>' . string2html ( $value ) . '</div>' . $newline;
						} else {
							$html .= '                            <div class="col-md-6" ' . $semAttribution . '>' . json_encode ( $value ) . '</div>' . $newline;
						}
						$html .= '                        </div>' . $newline;
					}
					$html .= '                    </div>' . $newline;
				}
				$html .= '    </div>' . $newline;
				$html .= '<!-- functions to handle url parameters -->' . $newline;
				$js1 = '    <script>' . $newline;
				// https://stackoverflow.com/questions/5997450/append-to-url-and-refresh-page
				$js1 .= "	function URL_add_parameter(url, param, value){";
				$js1 .= "	    var hash       = {};";
				$js1 .= "	    var parser     = document.createElement('a');";
				$js1 .= "	    parser.href    = url;";
				$js1 .= "	    var parameters = parser.search.split(/\?|&/);";
				$js1 .= "	    for(var i=0; i < parameters.length; i++) {";
				$js1 .= "	        if(!parameters[i])";
				$js1 .= "	            continue;";
				$js1 .= "	        var ary      = parameters[i].split('=');";
				$js1 .= "	        hash[ary[0]] = ary[1];";
				$js1 .= "	    }";
				$js1 .= "	    hash[param] = value;";
				$js1 .= "	    var list = [];  ";
				$js1 .= "	    Object.keys(hash).forEach(function (key) {";
				$js1 .= "	        list.push(key + '=' + hash[key]);";
				$js1 .= "	    });";
				$js1 .= "	    parser.search = '?' + list.join('&');";
				$js1 .= "	    return parser.href;";
				$js1 .= "	}";
				$js1 .= "	function URL_remove_parameter(url, param){";
				$js1 .= "	    var hash       = {};";
				$js1 .= "	    var parser     = document.createElement('a');";
				$js1 .= "	    parser.href    = url;";
				$js1 .= "	    var parameters = parser.search.split(/\?|&/);";
				$js1 .= "	    for(var i=0; i < parameters.length; i++) {";
				$js1 .= "	        if(!parameters[i])";
				$js1 .= "	            continue;";
				$js1 .= "	        var ary      = parameters[i].split('=');";
				$js1 .= "	        hash[ary[0]] = ary[1];";
				$js1 .= "	    }";
				$js1 .= "	    hash[param] = 0;";
				$js1 .= "	    var list = [];  ";
				$js1 .= "	    Object.keys(hash).forEach(function (key) {";
				$js1 .= "		if (key != param) {";
				$js1 .= "	        	list.push(key + '=' + hash[key]);";
				$js1 .= "		}";
				$js1 .= "	    });";
				$js1 .= "	    parser.search = '?' + list.join('&');";
				$js1 .= "	    return parser.href;";
				$js1 .= "	}";
				$js1 .= $newline . '    </script>' . $newline;
				$html .= '<!-- functions to initialize map -->' . $newline;
				$js2 = '    <script>' . $newline;
				//remove mapbox osm and add bkg topplus web open
				$js2 .= "var map = L.map('map', {center: [50, 7.44], zoom: 7, crs: L.CRS.EPSG4326});";
				$js2 .= "L.tileLayer.wms('https://sgx.geodatenzentrum.de/wms_topplus_open?',{";
        		$js2 .= "	layers: 'web',";
        		$js2 .= "	format: 'image/png',";
        		$js2 .= "	attribution: 'BKG - 2021 - <a href=\'https://sg.geodatenzentrum.de/web_public/Datenquellen_TopPlus_Open.pdf\'  target=\'_blank\'>Datenquellen<a>'";
        		$js2 .= "}).addTo(map);";
				/*
				$js2 .= "	var map = L.map('map').setView([50, 7.44], 7);";
				$js2 .= "	L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?";
				$js2 .= "access_token=pk.eyJ1IjoibWFwYm94IiwiYSI6ImNpejY4NXVycTA2emYycXBndHRqcmZ3N3gifQ.rJcFIG214AriISLbB6B5aw', {";
				$js2 .= "		maxZoom: 18,";
				$js2 .= "		attribution: 'Map data &copy; <a href=\"https://www.openstreetmap.org/\">OpenStreetMap</a> contributors, ' +";
				$js2 .= "			'<a href=\"https://creativecommons.org/licenses/by-sa/2.0/\">CC-BY-SA</a>, ' +";
				$js2 .= "			'Imagery © <a href=\"https://www.mapbox.com/\">Mapbox</a>',";
				$js2 .= "		id: 'mapbox.light'";
				$js2 .= "	}).addTo(map);";*/
				if (! isset ( $wfsid ) || ! isset ( $collection )) {
					$js2 .= 'document.getElementById("map").style.display = "none"; ';
					// $js2 .= 'document.getElementById("bboxButtons").style.display = "none"; ';
				}
				$js2 .= $newline . '    </script>' . $newline;
				// add first scripts to html
				$html .= $js1;
				$html .= '    <div class="col-md-6">' . $newline;
				if ($map_position == 'side') {
					$html .= '        <div id="map"></div>  ' . $newline;
				}
				$html .= "<!-- add special function that require map after the mapframe div -->" . $newline;
				
				$html .= $js2;
				$html .= "<!-- add geojson object -->" . $newline;
				$html .= $geoJsonVariable;
				$html .= '<!-- functions to render vectors and extent managing -->' . $newline;
				
				$js3 = "    <script>" . $newline;
				$js3 .= "	function onEachFeature(feature, layer) {";
				//$js3 .= "	//alert('on each feature');";
				//$js3 .= "	//alert(JSON.stringify(feature_schema));";
				$js3 .= "		var popupContent = \"<p><b>\"+ feature.id + \" (\" +";
				$js3 .= "				feature.geometry.type + \")</b></p>\";";
				$js3 .= "		if (feature.properties && feature.properties.popupContent) {";
				$js3 .= "			popupContent += feature.properties.popupContent;";
				$js3 .= "		}";
				$js3 .= "		for (var key in feature.properties){";
				$js3 .= "		    	var value = feature.properties[key];";
				$js3 .= "				if (!!feature_schema && feature_schema.properties[key] && feature_schema.properties[key].title != '') {key = feature_schema.properties[key].title;}";
				$js3 .= "		   	popupContent += \"<br><b>\"+key+\"</b>: \"+value;";
				$js3 .= "		}";
				$js3 .= "		layer.bindPopup(popupContent);";
				$js3 .= "	}";
				// zoom to featurecollection
				// $e = new mb_exception($minxFC.",".$minyFC.",".$maxxFC.",".$maxyFC);
				$js3 .= "		map.fitBounds([";
				$js3 .= "   				[" . $minxFC . "," . $minyFC . "],";
				$js3 .= "   				[" . $maxxFC . "," . $maxyFC . "]";
				$js3 .= "			]);";
				$js3 .= "	L.geoJSON([feature_" . $geomType . "], {";
				$js3 .= "		style: function (feature) {";
				$js3 .= "			return feature.properties && feature.properties.style;";
				$js3 .= "		},";
				$js3 .= "		onEachFeature: onEachFeature,";
				$js3 .= "		pointToLayer: function (feature, latlng) {";
				$js3 .= "			return L.circleMarker(latlng, {";
				$js3 .= "				radius: 8,";
				$js3 .= "				fillColor: \"#ff7800\",";
				$js3 .= "				color: \"#000\",";
				$js3 .= "				weight: 1,";
				$js3 .= "				opacity: 1,";
				$js3 .= "				fillOpacity: 0.8";
				$js3 .= "			});";
				$js3 .= "		}";
				$js3 .= "	}).addTo(map);";
				$js3 .= "	map.on('moveend', function() { ";
				$js3 .= "		var bounds = map.getBounds();";
				$js3 .= "		var minLng = document.getElementById('minLng');";
				$js3 .= "		minLng.value = bounds._southWest.lng;";
				$js3 .= "		var minLat = document.getElementById('minLat');";
				$js3 .= "		minLat.value = bounds._southWest.lat;";
				$js3 .= "		var maxLng = document.getElementById('maxLng');";
				$js3 .= "		maxLng.value = bounds._northEast.lng;";
				$js3 .= "		var maxLat = document.getElementById('maxLat');";
				$js3 .= "		maxLat.value = bounds._northEast.lat;";
				$js3 .= "	});";
				$js3 .= "	function zoomToExtent(minx,miny,maxx,maxy) {";
				$js3 .= "		map.fitBounds([";
				$js3 .= "   				[minx, miny],";
				$js3 .= "   				[maxx, maxy]";
				$js3 .= "			]);";
				$js3 .= "	}";
				$js3 .= $newline . "    </script>" . $newline;
				// add geojson object from page
				// $html .= $geojsonVariable.$newline;
				$html .= $js3;
				/*
				 * } else {
				 * //item is set !
				 * //$html .= '<div id="feature_table">';
				 * //header("Content-Type: application/gml+xml;version=3.1");
				 * header("Content-Type: ".$outputFormat);
				 * echo $wfs->getFeatureById($collection, $outputFormat, $item, "2.0.0", "EPSG:4326");
				 * die();
				 * }
				 */
			}
		}
		// ************************************************************************************************************************************
		$html .= '    </div>' . $newline;
		$html .= '</div>' . $newline;
		$html .= '</div>' . $newline;
		// ************************************************************************************************************************************
		// footer
		$html .= '<footer class="footer bg-light py-4 d-flex flex-column justify-content-around align-items-center">' . $newline;
		$html .= '    <div class="container d-flex flex-row justify-content-between align-items-center w-100">' . $newline;
		$html .= '        <span>' . $newline;
		$html .= '            <span class="text-muted small mr-2">powered by</span>' . $newline;
		$html .= '            <a class="navbar-brand" href="https://git.osgeo.org/gitea/GDI-HE/Geoportal-Hessen" target="_blank">Geoportal-Hessen</a>' . $newline;
		$html .= '        </span>' . $newline;
		$html .= '        <span>' . $newline;
		if (! isset ( $collections ) || $collection == 'all') {
			$html .= '            <span><a class="small mr-2" href="' . $legal_notice_link . '" target="_blank">' . _mb ( 'Legal Notice' ) . '</a></span>' . $newline;
			$html .= '            <span><a class="small" href="' . $privacy_notice_link . '" target="_blank">' . _mb ( 'Privacy Notice' ) . '</a></span>' . $newline;
		}
		$html .= '        </span>' . $newline;
		$html .= '    </div>' . $newline;
		$html .= '</footer>' . $newline;
		// ************************************************************************************************************************************
		$html .= '</body>' . $newline;
		$html .= '</html>' . $newline;
		//
		header ( "text/html" );
		echo $html;
		die ();
}
die ();
?>
