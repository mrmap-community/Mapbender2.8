<?php

/**
 * @TODO: Create more MVC type structure
 * @TODO: Create helper functions for this class
 */

require_once __DIR__ . '/../src/conf/system.php';

header('Content-type: application/json');

$result            = array();
$result['request'] = $_REQUEST;

// Find search from source
// -------------------------
$searchFolder = isset($result['request']['source']) ? __DIR__
    . '/../src/search/'
    . preg_replace('/[^a-z]/', '', strtolower($result['request']['source'])) . '/' : null;

if (is_null($searchFolder)) {
    $result['error'] = 'Search not found!';
    echo json_encode($result);
    exit;
}

if (!file_exists($searchFolder . 'Search.php')) {
    $result['error'] = 'Search Class not found!';
    echo json_encode($result);
}

// -------------------------
include $searchFolder . 'Search.php';
$search = new Search($conf);

// if request comes from autocomplete search
if ($result['request']['type'] === 'autocomplete') {
    $response = $search->autocomplete($result['request']['terms']);
    $result['response'] = $response;
} else {
    parse_str(urldecode($result['request']['extended']), $extended);
    $resources  = json_decode($result['request']['resources'], true);
    $resources  = $resources !== null ? $resources : array_keys($conf->get('search:geoportal:resources'));
    $keywords   = json_decode($result['request']['keywords'], true);
    $searchText = implode(' ', array_merge(array($result['request']['terms']), $keywords ? $keywords : array()));
    $response   = $search->find(
        $searchText,
        $result['request']['page-geoportal'],
        $result['request']['data-geoportal'],
        $resources,
        $extended
    );

    $result['html']['content'] = $templating->parse(
        $searchFolder . 'template.php',
        array(
            'keywords'     => $keywords,
            'resources'    => $resources,
            'allResources' => $conf->get('search:geoportal:resources'),
            'dataset'      => $response['dataset'],
            'wms'          => $response['wms'],
            'wfs'          => $response['wfs'],
            'wmc'          => $response['wmc']
        )
    );

    $result['response'] = $response;
}

echo json_encode($result);
