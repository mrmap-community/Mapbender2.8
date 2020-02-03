<?php

$noimage = "images/noimage.jpg";

$resource = new \Geoportal\Suche\SearchData($params, "wmc");
$data     = $resource->getData();
$results  = $resource->getResults();

$paginationTemplate = new \Geoportal\Suche\Template("_pagination.php");
$paginationPartial  = $paginationTemplate->renderView($data);

$resultsTemplate = new \Geoportal\Suche\Template("Wmc/_results.php");
$resultsPartial  = $resultsTemplate->renderView($results);

?>
<div class="search-cat<?php echo in_array('wmc', $params['resources']) ? ' active' : '' ?>">
    <div class="search-header">
        <img href="" title="">
        <div class="source--title -js-title -js-accordion">
            <span class="accordion icon closed"></span>
            <?php echo $resource->getResourceTitle(); ?>
            <span class="source--title--result">
                (<?php echo $resource->getResultsCount(); ?>)
            </span>
        </div><!-- end .source--title -->
    </div><!-- end .search-header -->
    <div class="wmc search--body hide">
        <?php if( count($params['wmc']['keywords']['tagCloud']['tags']) > 1 ): ?>
        <div data-result="wmc" class="keywords -js-keywords">
            <div class="keywords--headline">Schlagwortsuche</div>
            <div class="keywords--container hide">
                <?php foreach ($params['wmc']['keywords']['tagCloud']['tags'] as $keyword) { ?>
                    <span class="keywords--item -js-keywords--item -js-keyword" data-params="<?php echo $keyword['url'] ?>" title="<?php echo $keyword['title'] ?>" style="font-size: <?php echo $keyword['weight'] ?>px;"><?php echo $keyword['title'] ?></span>
                <?php } ?>
            </div><!-- end .keywords--container -->
        </div><!-- end .keywords -->
        <?php endif; ?>
        <div>
            <div class="center">
                <?php
                    if( $resource->getResultsCount() > 0) {
                        $paginationPartial->render();
                    }
                    else {
                        $text = "Keine Ergebnisse gefunden, bitte versuchen Sie einen anderen Suchbegriff...";
                        echo "<p>". $text ."</p>";
                    }
                ?>
            </div><!-- end .center -->
            <?php
                if( $resource->getResultsCount() > 0) {
                    $resultsPartial->render();
                }
            ?>
        </div><!-- end .div -->
    </div><!-- end .wmc .search--body -->
</div><!-- end .search-cat -->
