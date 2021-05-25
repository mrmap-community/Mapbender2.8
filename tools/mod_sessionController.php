<?php 
/*
 * Conroller to read and delete sessions from the session storage from shell.
 * Sometimes the session information is not directly available, because it is stored in memcached
 * Functions from ../lib/class_Mapbender_session.php are used!
 * php mod_sessionController.php sessionId=xyz method={exists|delete}
 * to use this script adopt your php.ini to know the memcached storage!!!
 * 
 */
require_once(dirname(__FILE__)."/../http/classes/class_mb_exception.php");
require_once(dirname(__FILE__)."/../http/classes/class_mb_warning.php");
require_once(dirname(__FILE__)."/../http/classes/class_mb_notice.php");
//parse arguments
$arguments = $argv;
array_shift($arguments);
foreach($arguments as $value) {
    $pieces = explode('=',$value);
    if(count($pieces) >= 2) {
        $real_key = $pieces[0];
        array_shift($pieces);
        $real_value = implode('=', $pieces);
        $real_arguments[$real_key] = $real_value;
    }
}
//***************************************************************
//read values
$sessionId = $real_arguments['sessionId'];
if (!in_array($real_arguments['method'], array("exists", "get", "delete"))) {
    echo "No allowed method given as param (method=exists/get/delete) - no success!";
    die();
} else {
    $method = $real_arguments['method'];
}
if (empty($real_arguments['sessionId']) || $real_arguments['sessionId'] =="") {
    echo "No sessionId given as param (sessionId=xyz) - no success!";
    die();
} else {
    $sessionId = $real_arguments['sessionId'];
}

switch ($method) {
    case "exists":
        $result = storageExists($sessionId);
        if ($result){
            echo "Session ".$sessionId." exists in storage"."\n";
        } else {
            echo "Session ".$sessionId." was not found in session storage"."\n";
            
        }
        break;
    case "get":
        $result = storageGet($sessionId);
        if ($result){
            echo "Session content for ".$sessionId.": \n". $result;
        } else {
            echo "Session ".$sessionId." was not found in session storage"."\n";
            
        }
        break;     
    case "delete":
        $result = storageDestroy($sessionId);
        if ($result){
            echo "Session content for ".$sessionId." deleted!";
        } else {
            echo "Session ".$sessionId." was not found in session storage"."\n";
            
        }
        break;
}


/*
 * destroy session file or object on server
 */
function storageDestroy($id) {
    switch (ini_get('session.save_handler')) {
        case "memcache":
            $memcache_obj = new Memcache;
            if (defined("MEMCACHED_IP") && MEMCACHED_IP != "" && defined("MEMCACHED_PORT") && MEMCACHED_PORT != "") {
                $memcache_obj->connect(MEMCACHED_IP, MEMCACHED_PORT);
            } else {
                //use standard options
                $memcache_obj->connect('localhost', 11211);
            }
            new mb_notice("sessions stored via memcache");
            $session = $memcache_obj->get($id);
            if ($session !== false){
                $memcache_obj->delete($id);
                $memcache_obj->close();
                return true;
            } else {
                $memcache_obj->close();
                return false;
            }
            break;
        case "memcached":
            $memcached_obj = new Memcached;
            if (defined("MEMCACHED_IP") && MEMCACHED_IP != "" && defined("MEMCACHED_PORT") && MEMCACHED_PORT != "") {
                $memcached_obj->addServer(MEMCACHED_IP, MEMCACHED_PORT);
            } else {
                //use standard options
                $memcached_obj->addServer('localhost', 11211);
            }
            new mb_notice("sessions stored via memcacheD");
            $prefix = ini_get('memcached.sess_prefix');
            if (empty($prefix) || $prefix =='') {
                $prefix = "memc.sess.key.";
            }
            $session = $memcached_obj->get($prefix.$id);
            //$session = $memcached_obj->get($id);
            if ($session !== false){
                //$memcached_obj->delete($id);
                $memcached_obj->delete($prefix.$id);
                //$memcached_obj->close();
                return true;
            } else {
                //$memcached_obj->close();
                return false;
            }
            break;
        case "files":
            //check if file exists
            if(file_exists(ini_get('session.save_path')."/sess_".$id)) {
                return @unlink(ini_get('session.save_path')."/sess_".$id);
            } else {
                return false;
            }
            break;
    }
}

/*
 * read session information from server
 */
function storageGet($id) {
    switch (ini_get('session.save_handler')) {
        case "memcache":
            $memcache_obj = new Memcache;
            if (defined("MEMCACHED_IP") && MEMCACHED_IP != "" && defined("MEMCACHED_PORT") && MEMCACHED_PORT != "") {
                $memcache_obj->connect(MEMCACHED_IP, MEMCACHED_PORT);
            } else {
                //use standard options
                $memcache_obj->connect('localhost', 11211);
            }
            new mb_notice("sessions stored via memcache");
            $session = $memcache_obj->get($id);
            if ($session !== false){
                $value = $memcache_obj->get($id);
                $memcache_obj->close();
                return $value;
            } else {
                $memcache_obj->close();
                return false;
            }
            break;
        case "memcached":
            $memcached_obj = new Memcached;
            if (defined("MEMCACHED_IP") && MEMCACHED_IP != "" && defined("MEMCACHED_PORT") && MEMCACHED_PORT != "") {
                $memcached_obj->addServer(MEMCACHED_IP, MEMCACHED_PORT);
            } else {
                //use standard options
                $memcached_obj->addServer('localhost', 11211);
            }
            new mb_notice("sessions stored via memcacheD");
            $prefix = ini_get('memcached.sess_prefix');
            if (empty($prefix) || $prefix =='') {
                $prefix = "memc.sess.key.";
            }
            $session = $memcached_obj->get($prefix.$id);
            //$session = $memcached_obj->get($id);
            if ($session !== false){
                //$memcached_obj->delete($prefix.$id);
                //$memcached_obj->close();
                return $session;
            } else {
                //$memcached_obj->close();
                return false;
            }
            break;
        case "files":
            //check if file exists
            if(file_exists(ini_get('session.save_path')."/sess_".$id)) {
                //return @unlink(ini_get('session.save_path')."/sess_".$id);
                //read content and return it
                return file_get_contents(ini_get('session.save_path')."/sess_".$id);
            } else {
                return false;
            }
            break;
    }
}

function storageExists($id) {
    switch (ini_get('session.save_handler')) {
        case "memcache":
            $memcache_obj = new Memcache;
            if (defined("MEMCACHED_IP") && MEMCACHED_IP != "" && defined("MEMCACHED_PORT") && MEMCACHED_PORT != "") {
                $memcache_obj->connect(MEMCACHED_IP, MEMCACHED_PORT);
            } else {
                //use standard options
                $memcache_obj->connect('localhost', 11211);
            }
            new mb_notice("sessions stored via memcache");
            $session = $memcache_obj->get($id);
            $memcache_obj->close();
            if ($session !== false){
                return true;
            } else {
                return false;
            }
            break;
        case "memcached":
            $memcached_obj = new Memcached;
            if (defined("MEMCACHED_IP") && MEMCACHED_IP != "" && defined("MEMCACHED_PORT") && MEMCACHED_PORT != "") {
                $memcached_obj->addServer(MEMCACHED_IP, MEMCACHED_PORT);
            } else {
                //use standard options
                $memcached_obj->addServer('localhost', 11211);
            }
            new mb_notice("sessions stored via memcacheD");
            $prefix = ini_get('memcached.sess_prefix');
            if (empty($prefix) || $prefix =='') {
                $prefix = "memc.sess.key.";
            }
            echo "\nSearch for: ".$prefix.$id."\n";
            //Maybe the prefix for session information in memcached is only available via apache module!
            $session = $memcached_obj->get($prefix.$id);
            //
            //$session = $memcached_obj->get($id);
            //$memcached_obj->close();
            if ($session !== false){
                return true;
            } else {
                return false;
            }
            break;
        case "files":
            //check if file exists
            if(file_exists(ini_get('session.save_path')."/sess_".$id)) {
                return true;
            } else {
                return false;
            }
            break;
    }
}	

?>