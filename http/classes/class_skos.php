<?php
require_once dirname(__FILE__) . "/../../core/globalSettings.php";
require_once dirname(__FILE__) . "/../classes/class_connector.php";
require_once dirname(__FILE__) . "/../classes/class_Uuid.php";

class Skos {
    var $uploadUrl; // url from which the resource has been registered
    var $languageCodes; // array with supported language codes : e.g. array('en','de','fr');
    var $conceptScheme; // ConceptScheme object of the skos representation
    var $resolveSuccess;
    public function __construct($uploadUrl) {
        // mandatory
        $this->uploadUrl = $uploadUrl;
        $this->resolveSuccess = false;
        $this->languageCodes = array (
            "en"
        );
        // import skos rdf from uploadUrl
    }
    public function importFromSkosRdf() {
        // try to connect to url and read skos
        $skosConnector = new connector ();
        $skosConnector->set ( "timeOut", "5" );
        $skosConnector->load ( $this->uploadUrl );
        $skosXml = $skosConnector->file;
        if (isset ( $skosXml ) && $skosXml != '') {
            // some file have been found
            $skosRdf = $skosXml;
            libxml_use_internal_errors ( true );
            try {
                $skosXml = simplexml_load_string ( $skosRdf );
                if ($skosXml === false) {
                    foreach ( libxml_get_errors () as $error ) {
                        $err = new mb_exception ( "classes/class_skos.php:" . $error->message );
                    }
                    throw new Exception ( "classes/class_skos.php:" . 'Cannot parse SKOS XML!' );
                    return false;
                }
            } catch ( Exception $e ) {
                $err = new mb_exception ( "classes/class_skos.php:" . $e->getMessage () );
                return false;
            }
            if ($skosXml !== false) {
                $this->resolveSuccess = true;
                // echo "skos parsed successfully!";
                $skosXml->registerXPathNamespace ( "dc", "http://purl.org/dc/elements/1.1/" );
                $skosXml->registerXPathNamespace ( "xsi", "http://www.w3.org/2001/XMLSchema-instance" );
                $skosXml->registerXPathNamespace ( "rdf", "http://www.w3.org/1999/02/22-rdf-syntax-ns#" );
                $skosXml->registerXPathNamespace ( "rdfs", "http://www.w3.org/2000/01/rdf-schema#" );
                $skosXml->registerXPathNamespace ( "owl", "http://www.w3.org/2002/07/owl#" );
                $skosXml->registerXPathNamespace ( "skos", "http://www.w3.org/2004/02/skos/core#" );
                $skosXml->registerXPathNamespace ( "dct", "http://purl.org/dc/terms/" );
                $skosXml->registerXPathNamespace ( "foaf", "http://xmlns.com/foaf/0.1/" );
                $skosXml->registerXPathNamespace ( "dcat", "http://www.w3.org/ns/dcat#" );
                $skosXml->registerXPathNamespace ( "adms", "http://www.w3.org/ns/adms#" );
                $skosXml->registerXPathNamespace ( "vcard", "http://www.w3.org/2006/vcard/ns#" );
                $skosXml->registerXPathNamespace ( "voaf", "http://labs.mondeca.com/vocab/voaf#" );
                $skosXml->registerXPathNamespace ( "vann", "http://purl.org/vocab/vann/" );
                
                # $rootConceptSchemeUri = $skosXml->xpath ( '/rdf:RDF/skos:ConceptScheme/@rdf:about' );
                # new 2023-05-25 - altered xml from inspire registry - no conceptScheme but Concept
                /*
                 * 
                 */
                # $rootConceptSchemeUri = $skosXml->xpath ( '/rdf:RDF/rdf:Description[rdf:type/@rdf:resource="http://www.w3.org/2004/02/skos/core#ConceptScheme"]/@rdf:about' );
                # $e = new mb_exception(json_encode($rootConceptSchemeUri[0]));
                # $e = new mb_exception(json_encode($rootConceptSchemeUri[0]->attributes('about')));
                # old scheme - 2020
                $schemingSystem = "1";
                $conceptSchemeBasePath = '/rdf:RDF/skos:ConceptScheme';
                
                $this->conceptScheme = new ConceptScheme ();
                
                //check if such theme exists - read identifier
                $identifier = ( string ) $skosXml->xpath ( $conceptSchemeBasePath . '/dct:identifier' ) [0];
                if (!isset($identifier) || $identifier == "") {
                    $schemingSystem = "2";
                    $conceptSchemeBasePath = '/rdf:RDF/rdf:Description[rdf:type/@rdf:resource="http://www.w3.org/2004/02/skos/core#ConceptScheme"]';
                    $this->conceptScheme->identifier = ( string ) $skosXml->xpath ( $conceptSchemeBasePath . '/dct:identifier' ) [0];
                    $e = new mb_exception("classes/class_skos.php: identifier not found in schemingSystem 1, use schemingSystem 2!");
                
                } else {
                    $this->conceptScheme->identifier = $identifier;
                    $e = new mb_exception("classes/class_skos.php: use schemingSystem 1");
                }
                              
                switch ($schemingSystem) {
                    case "1":
                        
                        break;
                    case "2":
                        
                        break;
                }
                    
                // extract multilingual titles
                foreach ( $this->languageCodes as $languageCode ) {
                    $this->conceptScheme->prefLabel [$languageCode] = ( string ) $skosXml->xpath ( $conceptSchemeBasePath . '/skos:prefLabel[@xml:lang="' . $languageCode . '"]' ) [0];
                }
                foreach ( $this->languageCodes as $languageCode ) {
                    $this->conceptScheme->definition [$languageCode] = ( string ) $skosXml->xpath ($conceptSchemeBasePath . '/skos:definition[@xml:lang="' . $languageCode . '"]' ) [0];
                }
                $conceptLevel = 0;
                // extract top level concepts from skos structure
                foreach ( $skosXml->xpath ( $conceptSchemeBasePath .'/skos:hasTopConcept/@rdf:resource' ) as $topConcept ) {
                    $this->conceptScheme->hasTopConcept [] = ( string ) $topConcept [0];
                }

                // iterate tree recursively
                $conceptObject = new concept ();
                $conceptObject->conceptArray = array ();
                foreach ( $this->conceptScheme->hasTopConcept as $topConceptId ) {
                    // $e = new mb_exception("work on top scheme:".$topConceptId);
                    array_push ( $conceptObject->conceptArray, $this->extractSubConcepts ( $skosXml, $topConceptId ) );
                }
                $this->conceptScheme->conceptArray = $conceptObject->conceptArray;
            }
        }
    }
    public function exportSkos($format = "json") {
        header ( 'Content-Type: application/json' );
        echo json_encode ( $this );
    }
    
    public function getIdentifierArray() {
        $result = array();
        $result[] = $this->conceptScheme->identifier;
        $identifiersFromConceptArray = $this->identifiersToArray($this->conceptScheme->conceptArray);
        if (is_array($identifiersFromConceptArray) && count($identifiersFromConceptArray) > 0) {
            foreach ($identifiersFromConceptArray as $identifier) {
                $result[] = $identifier;
            }
        }
        return array_unique($result);
    }
    
    public function identifiersToArray($array) {
        $result = array();
        foreach ($array as $concept) {
            $result[] = $concept->identifier;
            //$e = new mb_exception("type of conceptArray: ".gettype($concept->conceptArray)." - count: ".count($concept->conceptArray));
            if (is_array($concept->conceptArray) && count($concept->conceptArray) > 0) {
                foreach($this->identifiersToArray($concept->conceptArray) as $identifier){
                    $result[] = $identifier;
                }
            }
        }
        return $result;
    }
    
    // returns an object with an array of subconcepts - if available
    public function extractSubConcepts($xmlObject, $conceptUri) {
        
        $e = new mb_exception("Invoke extractSubConcepts with uri: ".$conceptUri);
        $conceptObject = new Concept ();
        $conceptObject->identifier = $conceptUri;
        // get translations
        foreach ( $this->languageCodes as $languageCode ) {
            $conceptObject->prefLabel [$languageCode] = ( string ) ($xmlObject->xpath ( '/rdf:RDF/rdf:Description[@rdf:about="' . $conceptUri . '"]/skos:prefLabel[@xml:lang="' . $languageCode . '"]' ) [0]);
        }
        $conceptObject->conceptArray = array ();
        
        
        
        
        // iterate over concepts with broader = $conceptUri
        $i = 0;
        foreach ( $xmlObject->xpath ( '/rdf:RDF/rdf:Description[skos:broader/@rdf:resource="' . $conceptUri . '"]/@rdf:about' ) as $subConcept ) {
            // $e = new mb_exception("subConcept found: ".(string)$subConcept[0]);
            
            $conceptObject->conceptArray [$i]->identifier = ( string ) $subConcept [0];
            
            // extract title of subconcept
            foreach ( $this->languageCodes as $languageCode ) {
                $conceptObject->conceptArray [$i]->prefLabel [$languageCode] = ( string ) ($xmlObject->xpath ( '/rdf:RDF/rdf:Description[@rdf:about="' . $conceptObject->conceptArray [$i]->identifier . '"]/skos:prefLabel[@xml:lang="' . $languageCode . '"]' ) [0]);
            }
            
            // $e = new mb_exception("Search recursive for elements with parent: ".(string)$subConcept[0]);
            
            $subConceptObject = $this->extractSubConcepts ( $xmlObject, ( string ) $subConcept [0] );
            
            array_push ( $conceptObject->conceptArray, $subConceptObject );
            $i ++;
        }
        if ($i == 0) {
            //try to resolve remote xml from identifier
            $e = new mb_exception("classes/class_skos.php: try to resolve linked concept: " . $conceptUri);
            // try to connect to url and read skos
            $skosConnector = new connector ();
            $skosConnector->set ( "timeOut", "5" );
            $skosConnector->load ( $conceptUri );
            $skosXml = $skosConnector->file;
            if (isset ( $skosXml ) && $skosXml != '') {
                // some file have been found
                $skosRdf = $skosXml;
                libxml_use_internal_errors ( true );
                try {
                    $skosXml = simplexml_load_string ( $skosRdf );
                    if ($skosXml === false) {
                        foreach ( libxml_get_errors () as $error ) {
                            $err = new mb_exception ( "classes/class_skos.php:" . $error->message );
                        }
                        throw new Exception ( "classes/class_skos.php:" . 'Cannot parse SKOS XML!' );
                        return false;
                    }
                } catch ( Exception $e ) {
                    $err = new mb_exception ( "classes/class_skos.php:" . $e->getMessage () );
                    return false;
                }
                if ($skosXml !== false) {
                    $this->resolveSuccess = true;
                    // echo "skos parsed successfully!";
                    $skosXml->registerXPathNamespace ( "dc", "http://purl.org/dc/elements/1.1/" );
                    $skosXml->registerXPathNamespace ( "xsi", "http://www.w3.org/2001/XMLSchema-instance" );
                    $skosXml->registerXPathNamespace ( "rdf", "http://www.w3.org/1999/02/22-rdf-syntax-ns#" );
                    $skosXml->registerXPathNamespace ( "rdfs", "http://www.w3.org/2000/01/rdf-schema#" );
                    $skosXml->registerXPathNamespace ( "owl", "http://www.w3.org/2002/07/owl#" );
                    $skosXml->registerXPathNamespace ( "skos", "http://www.w3.org/2004/02/skos/core#" );
                    $skosXml->registerXPathNamespace ( "dct", "http://purl.org/dc/terms/" );
                    $skosXml->registerXPathNamespace ( "foaf", "http://xmlns.com/foaf/0.1/" );
                    $skosXml->registerXPathNamespace ( "dcat", "http://www.w3.org/ns/dcat#" );
                    $skosXml->registerXPathNamespace ( "adms", "http://www.w3.org/ns/adms#" );
                    $skosXml->registerXPathNamespace ( "vcard", "http://www.w3.org/2006/vcard/ns#" );
                    $skosXml->registerXPathNamespace ( "voaf", "http://labs.mondeca.com/vocab/voaf#" );
                    $skosXml->registerXPathNamespace ( "vann", "http://purl.org/vocab/vann/" );
                    
                    //extract relevant information
                    //$conceptObject->conceptArray [$i]->identifier = ( string ) $conceptUri;
                    
                    // extract title of subconcept
                    foreach ( $this->languageCodes as $languageCode ) {
                        $conceptObject->prefLabel [$languageCode] = ( string ) ($skosXml->xpath ( '/rdf:RDF/rdf:Description[@rdf:about="' . $conceptObject->identifier . '"]/skos:prefLabel[@xml:lang="' . $languageCode . '"]' ) [0]);
                    }
                    
                    // $e = new mb_exception("Search recursive for elements with parent: ".(string)$subConcept[0]);
                    //only use first hierarchy ... 
                    //$subConceptObject = $this->extractSubConcepts ( $skosXml, ( string ) $conceptUri );
                    
                    //array_push ( $conceptObject->conceptArray, $subConceptObject );
                }
            }
        }
        //iterate over remotely defined schemes
        /*foreach ( $xmlObject->xpath ( '/rdf:RDF/rdf:Description[skos:broader/@rdf:resource="' . $conceptUri . '"]/@skos:inScheme' ) as $subConcept ) {
            // $e = new mb_exception("subConcept found: ".(string)$subConcept[0]);
            
            $conceptObject->conceptArray [$i]->identifier = ( string ) $subConcept [0];
            
            // extract title of subconcept
            foreach ( $this->languageCodes as $languageCode ) {
                $conceptObject->conceptArray [$i]->prefLabel [$languageCode] = ( string ) ($xmlObject->xpath ( '/rdf:RDF/rdf:Description[@rdf:about="' . $conceptObject->conceptArray [$i]->identifier . '"]/skos:prefLabel[@xml:lang="' . $languageCode . '"]' ) [0]);
            }
            
            // $e = new mb_exception("Search recursive for elements with parent: ".(string)$subConcept[0]);
            
            $subConceptObject = $this->extractSubConcepts ( $xmlObject, ( string ) $subConcept [0] );
            
            array_push ( $conceptObject->conceptArray, $subConceptObject );
            $i ++;
        }*/
        // $e = new mb_exception("returned concept object: ".json_encode($conceptObject));
        return $conceptObject;
    }
    public function skosAlreadyInDB() {
        $sql = <<<SQL
SELECT * FROM custom_category_origin WHERE uri = $1
SQL;
        $v = array (
            $this->conceptScheme->identifier
        );
        $t = array (
            's'
        );
        $res = db_prep_query ( $sql, $v, $t );
        while ( $row = db_fetch_array ( $res ) ) {
            $customCategoryId [] = $row ['id'];
        }
        if (count ( $customCategoryId ) > 0 && count ( $customCategoryId ) < 2) {
            return $customCategoryId [0];
        } else {
            return false;
        }
    }
    public function persistSkosToDb() {
        // check if uri already exists in custom_category_origin
        if (! ($this->skosAlreadyInDB ())) {
            // insert record for skos
            $sql = <<<SQL
INSERT INTO custom_category_origin (upload_url, uri, type, uuid) VALUES ($1, $2, $3, $4)
SQL;
            $uuid = new Uuid ();
            $v = array (
                $this->uploadUrl,
                $this->conceptScheme->identifier,
                'skos',
                $uuid
            );
            $t = array (
                's',
                's',
                's',
                's'
            );
            $res = db_prep_query ( $sql, $v, $t );
            // return inserted id
            $sql = <<<SQL
SELECT id FROM custom_category_origin WHERE uuid = $1
SQL;
            $v = array (
                $uuid
            );
            $t = array (
                's'
            );
            $res = db_prep_query ( $sql, $v, $t );
            $row = db_fetch_array ( $res );
            $e = new mb_exception ( "id of inserted skos classification scheme: " . $row ['id'] );
            $classificationSchemeId = $this->skosAlreadyInDB ();
        } else {
            $e = new mb_exception ( "skos based scheme with id - " . $this->skosAlreadyInDB () . " - already registered in db!" );
            $classificationSchemeId = $this->skosAlreadyInDB ();
            // only update some metadata like timestamp and uploadUrl, ...
        }
        // insert single entries with hierarchy
        $this->insertConceptObjectToDb ( $this->conceptScheme, $classificationSchemeId, '' );
        // update?
        // $sql = "SELECT * FROM custom_category_origin WHERE uri = $1";
    }
    public function insertConceptObjectToDb($conceptObject, $categoryOriginId, $categoryParentUri) {
        $this->insertSkosCategoryEntry ( $conceptObject, $categoryOriginId, $categoryParentUri );
        if (count ( $conceptObject->conceptArray ) > 0) {
            foreach ( $conceptObject->conceptArray as $concept ) {
                $this->insertConceptObjectToDb ( $concept, $categoryOriginId, $conceptObject->identifier );
            }
        }
        //delete deprecated entries
        $this->deleteDeprecatedSkosCategoryEntries($categoryOriginId);
    }
    public function skosCategoryEntryAlreadyInDB($conceptIdentifier) {
        $sql = <<<SQL
SELECT * FROM custom_category WHERE custom_category_key = $1
SQL;
        $v = array (
            $conceptIdentifier
        );
        $t = array (
            's'
        );
        $res = db_prep_query ( $sql, $v, $t );
        while ( $row = db_fetch_array ( $res ) ) {
            $customCategoryEntryId [] = $row ['custom_category_id'];
        }
        if (count ( $customCategoryEntryId ) > 0 && count ( $customCategoryEntryId ) < 2) {
            return $customCategoryEntryId [0];
        } else {
            return false;
        }
    }
    public function deleteDeprecatedSkosCategoryEntries($categoryOriginId) {
        //get all identifiers from skos
        //$this->getIdentifierArray();
        //set those to be deleted, where identifier not in new list
        $sql = "UPDATE custom_category SET deletedate = now() WHERE fkey_custom_category_origin_id = ".$categoryOriginId;
        $sql .= " AND custom_category_key NOT IN ('".implode('\',\'', $this->getIdentifierArray())."')";
        $res = db_query ( $sql );
    }
    public function insertSkosCategoryEntry($conceptObject, $categoryOriginId, $categoryParentUri) {
        if (! ($this->skosCategoryEntryAlreadyInDB ( $conceptObject->identifier ))) {
            //default language is english
            $sql = <<<SQL
INSERT INTO custom_category (custom_category_key, custom_category_code_en, custom_category_code_de, custom_category_parent_key, fkey_custom_category_origin_id, createdate, deletedate) VALUES ($1, $2, $3, $4, $5, $6, $7)
SQL;
            $v = array (
                $conceptObject->identifier,
                $conceptObject->prefLabel ['en'],
                $conceptObject->prefLabel ['de'],
                $categoryParentUri,
                $categoryOriginId,
                'now()',
                null
            );
            $t = array (
                's',
                's',
                's',
                's',
                'i',
                's',
                's'
            );
            $res = db_prep_query ( $sql, $v, $t );
            // $row = db_fetch_array($res);
        } else {
            # TODO: solve problem, when only one languageCode is given - don't overwrite old codes! In the new version of the INSPIRE
            # registry - only one language is provided per skos/rdf url!
            $e = new mb_exception ( "Skos concept with identifier " . $this->skosCategoryEntryAlreadyInDB ( $conceptObject->identifier ) . " already registered in database - it will not be added twice!" );
            // update it based on it's key
            $sql = <<<SQL
UPDATE custom_category SET custom_category_code_en = $1, custom_category_parent_key = $2, fkey_custom_category_origin_id = $3, custom_category_code_de = $5 , deletedate = $6 WHERE custom_category_key = $4
SQL;
            $v = array (
                $conceptObject->prefLabel ['en'],
                $categoryParentUri,
                $categoryOriginId,
                $conceptObject->identifier,
                $conceptObject->prefLabel ['de'],
                null
            );
            $t = array (
                's',
                's',
                'i',
                's',
                's',
                's'
            );
            $res = db_prep_query ( $sql, $v, $t );
            $e = new mb_exception ( "Skos concept with uri " . $conceptObject->identifier . " updated!" ); // Original json: ".json_encode($conceptObject));
        }
    }
}

class ConceptScheme {
    var $identifier; // uri - same as @rdf:about
    var $created;
    var $issued;
    // var $uploadUrl; //url from which the resource has been registered
    var $prefLabel; // associative array with one entry for each supported language code
    var $definition; // associative array with one entry for each supported language code
    var $hasTopConcept; // array with uris of topmost concepts in the scheme
    var $conceptArray; // array with the Concept objects below the scheme
    public function __construct() {
        $this->identifier = "";
        $this->conceptArray = array ();
    }
}

class Concept {
    var $identifier; // uri - same as @rdf:about od Description element in skos rdf xml
    // var $parent; //uri of the parent concept or conceptScheme
    var $prefLabel; // associative array with one entry for each supported language code
    var $conceptArray; // array with the Concept objects below the scheme (childs in hierarchy)
    public function __construct() {
        $this->identifier = "";
        $this->conceptArray = array ();
    }
}
?>