<?php
require_once(__DIR__ . '/../class/FacetRehasher.php');
$facets = \Geoportal\Suche\FacetRehasher::rehashFacets($params);
?>
<span class='-js-show-facets -js-accordion extended-search-header'>
    <span class='accordion icon closed fs-20px'></span>
    Kategorien
</span>
<div class='-js-facets hide facet-list'>
<?php
foreach ($facets as $name => $facet) {
?>
<div class='-js-facet' data-name='<?= $name ?>'>
     <h2><?= $name ?></h2>
     <ul>
     <?php
     foreach ($facet as $subname => $vals) {
     ?>
<li class='-js-subfacet' data-name='<?= $subname ?>' data-id='<?= $vals['id'] ?>'>
<?= $subname . ' (' . $vals['count'] . ')' ?>
</li>
<?php
}
?>
     </ul>
</div>
<?php
}
?>
</div>
