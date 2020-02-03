<?php
// make sure to update the path to where you cloned the projects to!
//https://github.com/matthiasmullie/minify/issues/83 MIT Licensed
$path = dirname(__FILE__);
$extension = '-master';
//$extension = '';
require_once $path . '/minify'.$extension.'/src/Minify.php';
require_once $path . '/minify'.$extension.'/src/CSS.php';
require_once $path . '/minify'.$extension.'/src/JS.php';
require_once $path . '/minify'.$extension.'/src/Exception.php';
require_once $path . '/minify'.$extension.'/src/Exceptions/BasicException.php';
require_once $path . '/minify'.$extension.'/src/Exceptions/FileImportException.php';
require_once $path . '/minify'.$extension.'/src/Exceptions/IOException.php';
require_once $path . '/path-converter'.$extension.'/src/ConverterInterface.php';
require_once $path . '/path-converter'.$extension.'/src/Converter.php';
?>
