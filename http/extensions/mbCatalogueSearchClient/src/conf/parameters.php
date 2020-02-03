<?php

$configuration = array(
    'title'       => 'Geoportal Suche',
    'placeholder' => 'Bitte Suchbegriff eingeben...',
    'search'      => array(
        'geoportal' => array(
            'autocomplete' => array(
                'url' => 'http://www.geoportal.rlp.de/mapbender/geoportal/mod_getCatalogueKeywordSuggestion.php?',
                'maxResults' => 15
            ),

            'searchUrl' => 'http://www.geoportal.rlp.de/mapbender/php/mod_callMetadata.php?',
            'downloadUrl' => 'http://geoportal.rlp.de/mapbender/php/mod_getDownloadOptions.php?id=',
            'showMapUrl' => 'http://geoportal.rlp.de/portal/karten.html?',
            'title'     => 'Interaktive Daten',
            'class'     => 'src/search/geoportal/Search',
            'form' => array(
                'map' => array(
                    'center'  => array( //only EPSG:4326 is supported
                        'lat' => 49.9,
                        'lon' => 7.3
                    ),
                    'zoom' => 7,
                    'wms'  => array(
                        'url'    => 'http://osm-demo.wheregroup.com/service',
                        'layers' => array('osm'),
                        'format' => 'image/png',
                    )
                )
            ),
            'resources' => array(
                'dataset' => 'Datensätze',
                'wms'     => 'Darstellungsdienste',
                'wfs'     => 'Such- und Download- und Erfassungsmodule',
                'wmc'     => 'Kartenzusammenstellungen'
            )
        )
    )
);
