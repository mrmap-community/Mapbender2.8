<?php

namespace Geoportal\Suche;

class FacetRehasher
{

    private static function appendFacet($resource, &$facets)
    {
        if (!$resource) {
            return;
        }

        foreach ($resource['categories']['searchMD']['category'] as $cat) {
            if (!array_key_exists($cat['title'], $facets)) {
                $facets[$cat['title']] = array();
            }
            foreach ($cat['subcat'] as $sub) {
                if (!array_key_exists($sub['title'], $facets[$cat['title']])) {
                    $facets[$cat['title']][$sub['title']] = array('count' => 0, 'id' => $sub['id']);
                }
                $facets[$cat['title']][$sub['title']]['count'] += $sub['count'];
            }
        }
    }

    public static function rehashFacets($params)
    {
        $facets = array();

        static::appendFacet($params['dataset'], $facets);
        static::appendFacet($params['wms'], $facets);
        static::appendFacet($params['wfs'], $facets);
        static::appendFacet($params['wmc'], $facets);

        return $facets;
    }
}
