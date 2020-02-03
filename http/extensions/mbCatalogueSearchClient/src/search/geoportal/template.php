<?php

$noimage = "images/noimage.jpg";

$filterTemplate = new \Geoportal\Suche\Template("_filterarea.php");
$filterPartial = $filterTemplate->renderView($params);

$datasetCatView = new \Geoportal\Suche\Template("Dataset/_category.php");
$datasetCatView->renderView($params);

$wmsCatView = new \Geoportal\Suche\Template("Wms/_category.php");
$wmsCatView->renderView($params);

$wmcCatView = new \Geoportal\Suche\Template("Wmc/_category.php");
$wmcCatView->renderView($params);

$wfsCatView = new \Geoportal\Suche\Template("Wfs/_category.php");
$wfsCatView->renderView($params);

$facets = new \Geoportal\Suche\Template('facets.php');
$facets->renderView($params);
?>

<?php $facets->render(); ?>

<?php $filterPartial->render(); ?>

<?php $datasetCatView->render(); ?>

<?php $wmsCatView->render(); ?>

<?php $wmcCatView->render(); ?>

<?php $wfsCatView->render(); ?>
