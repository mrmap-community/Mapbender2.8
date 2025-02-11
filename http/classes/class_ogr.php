<?php
#
# MIT - Copyright 2022 https://github.com/mrmap-community/
#
# Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation
# files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy,
# modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the 
# Software is furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
# WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
# HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, 
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
#
/******************************************************************************
 * $Id$
 *
 * Project:  https://github.com/mrmap-community/Mapbender2.8
 * Purpose:  Class for wrapping GDAL/OGR functionalities 
 * Author:   Armin Retterath, armin.retterath@gmail.com
 *
 ******************************************************************************/

require_once(dirname(__FILE__)."/../../core/globalSettings.php");

/**
 * A class for providing gdal/ogr functions to mapbender. The functions may be invoked by cli (exec) or directly used 
 * if PHP_OGR is available. To install PHP_OGR see: https://github.com/dvzgeo/php_ogr
 */
class Ogr {
    var $useModule; # boolean
    var $moduleAvailable; # boolean
    var $ramdiskAvailable; # boolean
    var $ramdiskPath; # string
    var $useRamdisk; # boolean
    var $logRuntime; # boolean
    
    /**
     * @constructor
     */
    public function __construct() {
        $this->moduleAvailable = false;
        $this->moduleAvailable = extension_loaded('ogr');        
        $this->ramdiskAvailable = false;
        if (DEFINED("RAMDISK") && RAMDISK != "") {
            $this->ramdiskAvailable = true;
            $this->ramdiskPath = RAMDISK;
        }
        $this->useModule = false;
        $this->useRamdisk = false;
        $this->logRuntime = false;
    }
    /*
     * 
     */
    function microtime_float() {
        list ( $usec, $sec ) = explode ( " ", microtime () );
        return (( float ) $usec + ( float ) $sec);
    }
    
    public function getFormat($geometryFilename) {
        $result = shell_exec('ogrinfo '.$geometryFilename);
        //get successful or not
        $e = new mb_exception("classes/class_ogr.php: reult of ogrinfo: " . $result);
        if (strpos($result, 'successful') !== false) {
            //using driver `GeoJSON' successful
            preg_match_all('#using driver `(.*?)\' successful#', $result, $match);
            $e = new mb_exception("classes/class_ogr.php: match: " . json_encode($match));
            if (count($match[1]) == 1) {
                return $match[1][0];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    
    /**
     * Function to transform given GML features to geojson
     */
    public function transform($inputFilename, $inputFormat, $targetFormat, $targetCrs) {
        switch ($targetFormat) {
            case "GeoJSON":
                $appendix = 'geojson';
                break;
            case "GML":
                $appendix = 'gml';
                break;
        }
        if ($this->useRamdisk && $this->ramdiskAvailable) {
            $tmpDir = $this->ramdiskPath;
        } else {
            $tmpDir = TMPDIR;
        }
        $filenameUniquePart = "ogr_transform_".time()."_".uniqid();
        $targetFilename = $tmpDir . "/" . $filenameUniquePart . "." . $appendix;
        $e = new mb_exception("classes/class_ogr.php: result of ogrcommand: " . 'ogr2ogr -t_srs "'. $targetCrs .'" -f "' . $targetFormat . '" '.$targetFilename.' '. $inputFilename.' -lco WRITE_BBOX=YES');
        exec('ogr2ogr -t_srs "'. $targetCrs .'" -f "' . $targetFormat . '" '.$targetFilename.' '. $inputFilename.' -lco WRITE_BBOX=YES', $output);
        
        if($h = fopen($targetFilename, "r")){
            $result = fread($h, filesize($targetFilename));
            fclose($h);
            return $result;
        } else {
            return false;
        }
    }
    
    /**
     * Function to transform given GML features to geojson 
     */
    public function gml2Geojson($gmlFeatures) {
        if ($this->logRuntime) { $timeBegin = microtime_float (); }
        //$e = new mb_exception("classes/class_ogr.php: gml2Geojson invoked!");
        if ($this->useRamdisk && $this->ramdiskAvailable) {
            $tmpDir = $this->ramdiskPath;
        } else {
            $tmpDir = TMPDIR;
        }
        $filenameUniquePart = "ldp_gml_".time()."_".uniqid(); //will be set to new one cause ?
        $filenameGml = $tmpDir."/".$filenameUniquePart.".gml";
        $filenameGfs = $tmpDir."/".$filenameUniquePart.".gfs";
        if($h = fopen($filenameGml,"w")){
            if(!fwrite($h, $gmlFeatures)){
                $e = new mb_exception("classes/class_ogr.php: could not write gml file to tmp folder");
                return false;
            } else {
                //$e = new mb_exception("classes/class_ogr.php: wrote gml file to " . $filenameGml);
                unset($gmlFeatures);
            }
            fclose($h);
        }
        $filenameGeojson = $tmpDir."/".$filenameUniquePart.".geojson";
        exec('ogr2ogr -a_srs "EPSG:4326" -dim 2 -f "GeoJSON" '.$filenameGeojson.' '. $filenameGml.' -lco WRITE_BBOX=YES', $output);
        //read geojson
        if($h = fopen($filenameGeojson, "r")){
            $geojson = fread($h, filesize($filenameGeojson));
            if(!$geojson){
                $e = new mb_exception("classes/class_ogr.php: could not read geojson ".$filenameGeojson." from tmp folder");
                unlink($filenameGml);
                unlink($filenameGfs);
                return false;
            } else {
                //unklink tmp files
                unlink($filenameGeojson);
                unlink($filenameGml);
                unlink($filenameGfs);
            }
            fclose($h);
        }
        if ($this->logRuntime) {
            $timeEnd = microtime_float() - $timeBegin;
            $e = new mb_exception("php/classes/class_ogr.php: time for transformation via ogr2ogr: " . $timeEnd);
        }
        return $geojson;
        //return str_replace('urn:ogc:def:crs:OGC:1.3:CRS84', 'EPSG:4326', $geojson);
    }
    /**
     * Function to count features of vector files which, layername is needed!
     * The vector files may be downloaded from mapbender wfs datasources and therefor the $format
     * will be the value of the WFS Parameter FORMAT. The function try to map the format strings to 
     * typical ogr driver names. This is needed to do some further processing
     */
    function ogrCountFeatures($features, $format, $layername, $extentTempDir=false){
        if ($this->logRuntime) { $timeBegin = $this->microtime_float (); }
        if ($this->useRamdisk && $this->ramdiskAvailable) {
            $tmpDir = $this->ramdiskPath;
        } else {
            $tmpDir = TMPDIR;
        }
        //extension is used for security proxies which lives in own folders!
        if ($extentTempDir) {
            $tmpDir = str_replace("../", "../../http/", $tmpDir);
        }
        $filenameUniquePart = "ogr_count_features_" .md5($format). "_".time()."_".uniqid(); //
        
        $mapWfsFormat = array(          
            "application/json; subtype=geojson" => "GeoJSON",
            "json" => "GeoJSON",
            "application/json; subtype=geojson" => "GeoJSON",

            "application/vnd.google-earth.kml+xml" => "KML",
            "application/vnd.google-earth.kml xml" => "KML",
            "KML" => "KML",
            
            "text/xml; subtype=gml/3.1.1" => "GML",
            "text/xml; subtype=gml/2.1.2" => "GML",
            "text/xml; subtype=gml/3.1.1" => "GML",
            "application/gml+xml; version=3.2" => "GML",
            "GML2" => "GML",
            "gml3" => "GML",
            "gml32" => "GML",
            "text/xml; subtype=gml/3.2" => "GML",
            "text/xml; subtype=gml/3.2.1" => "GML",
            "GML2-GZIP" => "GML",
            "application/xml" => "GML",
            "GML" => "GML",
            # fix for qgis sending empty format string
            "" => "GML",
            
            "csv" => "CSV",
            "text/csv" => "CSV",
            
            "application/zip" => "ESRI Shapefile",
            "SHAPEZIP" => "ESRI Shapefile",
            "SHAPE-ZIP" => "ESRI Shapefile",
            
            "text/javascript" => "UNSUPPORTED",
            "excel" => "UNSUPPORTED",
            "application/json" => "UNSUPPORTED",
            "excel2007" => "UNSUPPORTED"
        ); 
        
        if (array_key_exists($format, $mapWfsFormat)) {
            $ogrDriver = $mapWfsFormat[$format];
        } else {
            $ogrDriver = "UNSUPPORTED";
            $e = new mb_exception("classes/class_ogr.php: outputFormat " . $format . " not known to function!");
        }
        switch ($ogrDriver) {
            case "GML":
                $filenameFeatures = $tmpDir."/".$filenameUniquePart . ".gml";
                //schema will be autogenerated by ogr
                $filenameSchema = $tmpDir."/".$filenameUniquePart . ".gfs";
                break;
            case "ESRI Shapefile":
                //unzip file to temporal directory and use new filename to count features in shape
                $filenameFeatures = $tmpDir."/".$filenameUniquePart . ".zip";
                //https://stackoverflow.com/questions/8889025/unzip-a-file-with-php           
                break;
            case "CSV":
                $filenameFeatures = $tmpDir."/".$filenameUniquePart . ".csv";
                break;
            case "GeoJSON":
                $filenameFeatures = $tmpDir."/".$filenameUniquePart . ".geojson";
                break;
            case "UNSUPPORTED":
                $e = new mb_exception("classes/class_ogr.php: outputFormat not known to function - please use another format - objects could not be counted!");
                return false;
                break;
            default:
                break;
        }
        
        if($h = fopen($filenameFeatures,"w")){
            if(!fwrite($h, $features)){
                $e = new mb_exception("classes/class_ogr.php: could not write gml file to tmp folder!");
                return false;
            } else {
                //$e = new mb_exception("classes/class_ogr.php: wrote gml file to " . $filenameFeatures);
                unset($features);
            }
            fclose($h);
        } else {
            $e = new mb_exception("classes/class_ogr.php: could open file to write!");
        }
        //delete namespace from featuretype_name, cause ogr don't use namespaces for "Layer name"
        if (strpos($layername, ':') != false) {
            $layername = explode(':', $layername)[1];
        }
        if ($ogrDriver == "ESRI Shapefile") {
            //$zip = new ZipArchive;
            $zipTmpFolder = uniqid() . "_zip";
           
            $tmpFolderCreated = mkdir($tmpDir."/" . $zipTmpFolder);
            if ($tmpFolderCreated) {
                //$e = new mb_exception("classes/class_ogr.php: tmp folder: " . $zipTmpFolder . " created!");       
            } else {
                $e = new mb_exception("classes/class_ogr.php: tmp folder: " . $zipTmpFolder . " could not be created!");
            }
            exec("unzip " . $filenameFeatures . " -d " . $tmpDir."/" . $zipTmpFolder . '/');          
        }
        //use shell_exec to get back textual representation of ogrinfo
        if ($ogrDriver == "ESRI Shapefile") {
            $result = shell_exec('ogrinfo -so ' . $tmpDir."/" . $zipTmpFolder . '/' .$layername . ".shp"  . ' ' . $layername);
        } else {
            $result = shell_exec('ogrinfo -so ' . $filenameFeatures . ' ' . $layername);
        }
        //$e = new mb_exception("classes/class_ogr.php: ogr:  " . 'ogrinfo -so ' . $filenameFeatures . ' ' . $layername);
        /*ogrinfo -so /tmp/test_wfs.xml fluren_rlp
        parse result - example:
        using driver `GML' successful.
        Feature Count: 65
        $exp = "/using driver `(+.)' successful./";
        */
        //parse text from ogrinfo - try to read driver and featurecount
        $expDriver = "/using driver `(.+)' successful\./";
        $expCount = "/Feature Count:[\s](\d+)/";
        //match for driver
        preg_match($expDriver, $result, $matches, PREG_OFFSET_CAPTURE);
        if (!empty($matches)) {
            $driver = $matches[1][0];
            //$e = new mb_exception("classes/class_ogr.php: found driver: " . $driver);
        } else {
            $e = new mb_exception("classes/class_ogr.php: Could not identify ogr driver to open datasource!");
            return false;
        }
        //match for count
        preg_match($expCount, $result, $matches, PREG_OFFSET_CAPTURE);
        if (!empty($matches)) {
            $count = $matches[1][0];
            //$e = new mb_exception("classes/class_ogr.php: count successfull!");
        } else {
            $count = 0;
            $e = new mb_exception("classes/class_ogr.php: no features found - count was 0!");
        }
        //delete temporary files
        if ($ogrDriver == "ESRI Shapefile") {
            exec("rm -r " . $tmpDir."/" . $zipTmpFolder . '/');
            unlink($filenameFeatures);
        } else {
            unlink($filenameFeatures);
            unlink($filenameSchema);
        }
        
        if ($this->logRuntime) {
            $timeEnd = $this->microtime_float() - $timeBegin;
            $e = new mb_exception("classes/class_ogr.php: Used ogr driver: " . $driver);
            $e = new mb_exception("classes/class_ogr.php: Number of objects: " . $count);
            $e = new mb_exception("classes/class_ogr.php: time for ogrinfo: " . $timeEnd);
        }
        return $count;
    }
    
}
?>
