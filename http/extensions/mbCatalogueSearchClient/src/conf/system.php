<?php
/**
 * @TODO: Create class for loading the configuration (maybe dependend on the environment) and creating the right url
 * @TODO: Create an auto cloass loader for including the php files and more important to use namespaces
 * @TODO: Change project structure (dirs) to be able to use namespaces (for what they have been included so far?)
 */

function getEnvironment() {
    $hostUrl = isset($_SERVER['SERVER_NAME'])? $_SERVER['SERVER_NAME'] : '';
    $regEx = "/localhost/i";
    preg_match($regEx, $hostUrl, $matches);

    //return dev if is localhost else return prod
    return (count($matches) > 0)?'dev' : 'prod';
}

function setReporting($toggle) {
    if($toggle) {
        error_reporting(E_ALL);
        ini_set("display_errors", 1);
    }
    else{
        error_reporting(0);
        @ini_set('display_errors', 0);
    }
}

// enable reporting when in dev env
if (getEnvironment() === 'dev') {
    setReporting(true);
}
else{
    setReporting(false);
}

require_once __DIR__ . '/parameters.php';
require_once __DIR__ . '/../class/Template.php';
require_once __DIR__ . '/../class/ArrayParser.php';
require_once __DIR__ . '/../class/Configuration.php';
require_once __DIR__ . '/../class/SearchData.php';

use Geoportal\Suche\Template;
use Geoportal\Suche\ArrayParser;
use Geoportal\Suche\Configuration;

$conf       = new Configuration($configuration, new ArrayParser());
$templating = new Template();

// Get base url
$uri = parse_url($_SERVER["REQUEST_URI"]);
$uri = $uri["path"];
$uri = strstr($uri, ".php") ? dirname($uri) : $uri;
$uri = strrpos($uri, '/') === strlen($uri) - 1 ? $uri : $uri . '/';
$protocol = isset($_SERVER['HTTPS'] ) ? 'https://' : 'http://';

$conf
    ->set('rootdir', realpath(__DIR__ . '/../../') . '/')
    ->set('template:base', $conf->get('rootdir') . 'src/views/base.php')
    ->set('system:basedir', $uri)
    ->set('system:baseurl', $protocol . $_SERVER["SERVER_NAME"] . $uri);

unset($protocol, $uri);
