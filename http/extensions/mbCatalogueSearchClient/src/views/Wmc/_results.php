<?php
$noimage = "images/noimage.jpg";
include __DIR__ . '/../../conf/parameters.php';
?>

<div class="search-results">
    <?php foreach ($params as $row) { ?>
        <div class="result--item -js-result-wmc">
            <?php if (isset($row['mdLink'])) { ?>
                <a class="result--item--title" title='zu <?php echo $row['title']; ?>' href='<?php echo $row['mdLink'] ?>' target="_blank"> <?php echo $row['title']; ?></a>
            <?php } ?>
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
            <p><?php echo $row['abstract'] ?></p>
            <div>
                <?php if(isset($row['layer'][0]['layer'])) {
                    foreach ($row['layer'][0]['layer'] as $layer) { ?>
                        <div class="result-item-layer">
                            <a title='zu <?php echo $layer['title']; ?>' href='<?php echo $layer['mdLink'] ?>' target="_blank"> <?php echo $layer['title']; ?></a>
                            <div class="img-area">
                                <img class="img-preview" src="<?php echo isset($layer['previewURL']) ? $layer['previewURL'] : $noimage ?>">
                                <img class="img-logo" src="<?php echo isset($layer['logoUrl']) ? $layer['logoUrl'] : $noimage ?>">
                                <img class="img-symbollink" src="<?php echo isset($layer['symbolLink']) ? $layer['symbolLink'] : $noimage ?>">
                            </div>
                            <dl>
                                <dt>Zuständige Stelle:</dt>
                                <dd><?php echo isset($layer['respOrg']) ? $layer['respOrg'] : '' ?></dd>
                            </dl>
                            <dl>
                                <dt>Datum der Metadaten:</dt>
                                <dd><?php echo isset($layer['date']) ? $layer['date'] : '' ?></dd>
                            </dl>
                            <p><?php echo isset($layer['abstract']) ? $layer['abstract'] : '' ?></p>
                            <div>
                                <?php if (isset($layer['downloadOptions'])) { foreach ($layer['downloadOptions'] as $dlopttion) { ?>
                                    title:<?php echo isset($dlopttion['title']) ? $dlopttion['title'] : '' ?>
                                <?php } } ?>
                            </div><!-- end .div -->
                        </div><!-- end .result-item-layer -->
                    <?php } ?>
                <?php } ?>
                <a class='button' href='<?= $configuration['search']['geoportal']['showMapUrl'] ?>WMC=<?= $row['id'] ?>'>Anzeigen</a>
            </div><!-- end .div -->
        </div><!-- end .result--item -->
    <?php } ?>
</div><!-- end .search-results -->
