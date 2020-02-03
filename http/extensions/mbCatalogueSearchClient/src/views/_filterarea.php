<div class="filterarea -js-filterarea">
    <div>
<?php
$filters = null;
if (array_key_exists('dataset', $params)) {
    $filters = $params['dataset']['filter']['searchFilter'];
}
if (array_key_exists('wms', $params)) {
    $filters = $params['wms']['filter']['searchFilter'];
}
if (array_key_exists('wfs', $params)) {
    $filters = $params['wfs']['filter']['searchFilter'];
}
if (array_key_exists('wmc', $params)) {
    $filters = $params['wmc']['filter']['searchFilter'];
}
$types = array(
    'Suchbegriff(e):' => 'searchText',
    'INSPIRE Themen:' => 'inspireThemes',
    'ISO Kategorien:' => 'isoCategories',
    'RP Kategorien:' => 'customCategories',
    'Räumliche Einschränkung:' => 'searchBbox',
    'Anbietende Stelle(n):' => 'registratingDepartments',
    'Registrierung/Aktualisierung von:' => 'regTimeBegin',
    'Registrierung/Aktualisierung bis:' => 'regTimeEnd',
    'Datenaktualität von:' => 'timeBegin',
    'Datenaktualität bis:' => 'timeEnd'
);
foreach ($types as $typeName => $type) {
    if (count($filters[$type]['item']) > 0) {
        ?>
        <div>
        <h4><?= $typeName ?></h4>
        <div class="search--list -js-keywords" data-id='geoportal-<?= $type ?>'>
        <?php
        foreach ($filters[$type]['item'] as $item) {
            ?>
            <span class="search--list--item -js-term"><?= $item['title'] ?>
            <span class="icon-cross fs-10px"></span>
            </span>
<?php
        } ?>
        </div><!-- end .search--list -->
    </div><!-- end .div-->
<?php
    }
}
?>
        <div>
            <h4>Art der Ressource:</h4>
            <div class="resource--list -js-resources">
                <?php foreach ($params['allResources'] as $key => $title) { ?>
                    <span class="resource--list--item -js-resource" data-resource="<?php echo $key ?>"
                          class="-js-resource<?php echo !in_array($key, $params['resources']) ? ' inactive'
                              : '' ?>"><?php echo $title ?></span>
                <?php } ?>
            </div><!-- end .resource--list -->
        </div><!-- end .div -->
    </div>
    <div>
        Treffer pro Seite:
        <select id='geoportal-maxResults'>
            <option selected='selected' value='<?= $filters['maxResults']['title'] ?>'
    data-url='<?= $filters['origUrl'] ?>'><?= $filters['maxResults']['title'] ?></option>
            <?php
            foreach ($filters['maxResults']['item'] as $option) {
            ?>
                <option value='<?= $option['title'] ?>'
            data-url='<?= $option['url'] ?>'><?= $option['title'] ?></option>
            <?php
            }
            ?>
        </select>
    </div>
</div><!-- end .filterarea -->
