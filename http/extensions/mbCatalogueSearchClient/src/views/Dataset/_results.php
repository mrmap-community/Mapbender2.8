<?php
    $noimage = "images/noimage.jpg";
include __DIR__ . '/../../conf/parameters.php';
?>

<div class="search-results">
<?php foreach ($params as $row) { ?>
    <div class="result--item -js-result-dataset">
        <a class="result--item--title" title='zu <?php echo $row['title']; ?>' href='<?php echo $row['mdLink'] ?>' target="_blank"> <?php echo $row['title']; ?></a>
        <div class="img-area">
            <img class="img-preview" src="<?php echo isset($row['previewURL']) ? $row['previewURL'] : $noimage ?>">
            <img class="img-logo" src="<?php echo isset($row['logoUrl']) ? $row['logoUrl'] : $noimage ?>">
            <img class="img-symbollink" src="<?php echo isset($row['symbolLink']) ? $row['symbolLink'] : $noimage ?>">
        </div>
        <dl>
            <dt>Zuständige Stelle:</dt>
            <dd><?php echo $row['respOrg'] ?></dd>
        </dl>
        <dl>
            <dt>Datum der Metadaten:</dt>
            <dd><?php echo $row['date'] ?></dd>
        </dl>
        <dl>
            <dt>Zeitliche Ausdehnung:</dt>
            <dd><?php echo $row['timeBegin'] ?> bis <?php echo $row['timeEnd'] ?></dd>
        </dl>
        <p><?php echo $row['abstract'] ?></p>
                <?php
                if(isset($row['coupledResources']['layer'][0]['srv']['layer'])) {
                    foreach ($row['coupledResources']['layer'][0]['srv']['layer'] as $layer) { ?>
                            <div>
                                <?php if (isset($layer['downloadOptions'])) { foreach ($layer['downloadOptions'] as $dloption) {
                                ?>
                                <a class='button' href='<?= $configuration['search']['geoportal']['downloadUrl'] . $dloption['uuid'] ?>&outputFormat=html&languageCode=de'>Herunterladen</a>
                                <?php } } ?>
                                <a class='button' href='<?= $configuration['search']['geoportal']['showMapUrl'] ?>LAYER[id]=<?= $layer['id'] ?>'>Anzeigen</a>
                                <a class='button' href='<?= $configuration['search']['geoportal']['showMapUrl'] ?>LAYER[zoom]=1&LAYER[id]=<?= $layer['id'] ?>'>Anzeigen mit Zoom</a>
                            </div>
                        <?php
                    }
                } ?>
    </div><!-- end .result--item -->
<?php } ?>
</div><!-- end .search-results -->
