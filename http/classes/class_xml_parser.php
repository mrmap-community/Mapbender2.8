<?php


/**
 * Parsing XML documents.
 * @author A.R.Pour
 * @version 0.1
 */

class XMLParser {
    private static $xp;
    private static $doc;
    private static $schema = array();
    private static $removeEmptyValues = true;
    
    /**
     * Returns the version of the class.
     * 
     * @return float
     */
    public static function getVersion() {
        return 0.1;
    }
       
    public static function loadXMLFromFile($filename) {
        if(file_exists($filename) and is_readable($filename)) {
            return self::loadXMLFromString(
                file_get_contents($filename)
            );
        }
        return false;
    }
    
    public static function loadXMLFromString($string) {
        self::$doc = new \DOMDocument();

        if( !@self::$doc->loadXML($string) || 
            !@self::$xp = new \DOMXPath(self::$doc)) {
            return false;
        }

        return true;
    }
    

    public static function loadJsonSchemaFromString($string) {
        $array = self::objectToArray(
            json_decode($string)
        );
        
        if(is_array($array) AND !empty($array)) {
            self::$schema = json_decode(json_encode(
                self::merge_recursive(self::objectToArray(self::$schema), $array)
            ));
            return true;
        }

        return false;
    }


    public static function loadJsonSchema($filename) {
        if(file_exists($filename) and is_readable($filename)) {            
            $array = self::objectToArray(
                json_decode(file_get_contents($filename))
            );
            
            if(is_array($array) AND !empty($array)) {
                self::$schema = json_decode(json_encode(
                    self::merge_recursive(self::objectToArray(self::$schema), $array)
                ));
                return true;
            }
        }

        return false;
    }
    
    public static function registerNamespaces($namespaces) {
        if(!empty($namespaces) and is_object(self::$xp) and get_class(self::$xp) === "DOMXPath") {
            foreach($namespaces AS $namespaceKey => $namespaceValue) {
                self::$xp->registerNamespace($namespaceKey,$namespaceValue);
            }
            return true;
        }
        return false;
    }

    public static function parse() {
        if(isset(self::$schema->cmd)) {
            self::parseCommands(self::$schema->cmd);
        }        
        return self::parseRecursive(self::$schema);
    }
    
    private static function objectToArray($object) {
        $array = array();
        $object = (array)$object;
        
        if(!empty($object)) {
            foreach($object as $key => $val) {
                $array[$key] = (is_array($val) || is_object($val)) ? self::objectToArray($val) : $val;
            }
        }
        return $array;
    }
    
    private static function parseRecursive($object, $name = "", $context = null, $path = "", $recursive = false, $asArray = false) {
        $result = array();

        foreach($object as $key => $val) {
            switch($key) {
                case "cmd" : 
                    continue;
                case "path" : 
                    $path .= $val;
                    continue;
                case "asArray" : 
                    $asArray = $val;
                    continue;
                case "recursive":
                    $recursive = true;
                    continue;
                case "data" :
                    $result = self::parseData($val, $name, $path, $context, $recursive, $asArray);
                    continue;
                default :
                    if(is_object($val)) {
                        $tmp = self::parseRecursive($val, $key, $context, $path, $recursive);
                    } else if(is_array($val) && count($val) >= 2) {
                        $tmp = self::getValue($path.$val[0], $context);
                        
                        switch($val[1]) {
                            case "commaSeparated"  : 
                                if(is_array($tmp)) {
                                    $tmp = implode(", ", $tmp); 
                                }
                                break;
                            case "raw" :
                               	$nodes = self::$xp->query($val[0], $context);
                               	if($nodes->length > 0) {
                               		$tmp = $context->ownerDocument->saveXML($nodes->item(0));
                               	}
                               	break;
                        }
                        
                    } else {
                        $tmp = self::getValue($path.$val, $context); 
                    }
                    
                    if(self::$removeEmptyValues && ($tmp === "" || $tmp === array())) {
                        continue;
                    }
                    
                    $result[$key] = $tmp;
            }
        }
        
        return $result;
    }
    
    private static function parseData($data, $name, $path, $context, $recursive, $asArray = false) {
        if(!is_object($data)) return array();
        $tmp = array();
        
        if($context == null) {
            $nodes = self::$xp->query($path);
        } else {
            $nodes = self::$xp->query($path, $context);
        }
        
        if($nodes) {
            foreach($nodes as $node) {
                $dataRecTmp = array();
                
                if($recursive) {
                    $dataRecTmp = self::parseData($data, $name, $path, $node, $recursive);
                }
                
                $dataTmp = self::parseRecursive($data, $name, $node);
                
                $tmp[] = empty($dataRecTmp) ? $dataTmp : array_merge(
                    $dataTmp,
                    array($name => $dataRecTmp)
                );
            }
        }

        return $asArray ? $tmp : self::getCleanArray($tmp);

    }
    
    private static function parseCommands($commands) {
        foreach($commands as $key => $val) {
            switch($key) {
                case "addNamespaces" :
                    self::registerNamespaces($val);
                    break;
                case "removeEmptyValues" :
                    if($val) self::$removeEmptyValues = true;
                    else self::$removeEmptyValues = false;
                    break;
            }
        }
    }
    
    private static function getValue($xpath, $context = null) {
        $result = array();
        $nodes = self::$xp->query($xpath, $context);
        
        if($nodes) {
            foreach($nodes as $node) {
                switch($node->nodeType) {
                    case XML_ATTRIBUTE_NODE:
                        $result[] = $node->value;
                        break;
                    case XML_TEXT_NODE:
                        $result[] = $node->wholeText;
                        break;
                    default:
                }
            }
        }

        return self::getCleanArray($result);
    }
    
    private static function getCleanArray($array) {
        if(count($array) === 1) {
            return $array[0];
        }
        
        return $array;
    }
    
    
    private static function merge_recursive( $arr1, $arr2 ) {
        foreach( array_keys( $arr2 ) as $key ) {
            if( isset( $arr1[$key] ) && is_array( $arr1[$key] ) && is_array( $arr2[$key] ) ) {
                $arr1[$key] = self::merge_recursive( $arr1[$key], $arr2[$key] );
            } else {
                $arr1[$key] = $arr2[$key];
            }
        }
        return $arr1;
    }
}