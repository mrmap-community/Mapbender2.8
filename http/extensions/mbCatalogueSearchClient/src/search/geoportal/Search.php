<?php

class Search
{
    /**
     * @var \Geoportal\Suche\Configuration
     */
    private $conf;

    /**
     * Search constructor.
     * @param $conf
     */
    public function __construct($conf)
    {
        $this->conf = $conf;
    }

    /**
     * @param $term
     * @return mixed
     */
    public function autocomplete($term)
    {
        $params = array(
            'searchText' => $term,
            'maxResults' => $this->conf->get('search:geoportal:autocomplete:maxResults'),
        );

        return $this->buildQueryAndDecodeJsonContent(
            $this->conf->get('search:geoportal:autocomplete:url'), $params
        );
    }

    /**
     * @param $terms
     * @param $page
     * @param $data
     * @param $resources
     * @param $extended
     * @return array
     */
    public function find($terms, $page, $data, $resources, $extended)
    {
        $result = array();
        $params = array_merge(
            $extended,
            array(
                'searchText'      => $terms,
                'outputFormat'    => 'json',
                'resultTarget'    => 'webclient',
                'searchPages'     => '',
                'searchResources' => '',
                'searchId'        => md5(microtime(true))
            )
        );

        $pages[$data] = $page;

        foreach ($resources as $searchitem) {
            $params['searchPages']     = isset($pages[$searchitem]) ? $pages[$searchitem] : 1;
            $params['searchResources'] = $searchitem;
            $result[$searchitem]       = $this->buildQueryAndDecodeJsonContent(
                $this->conf->get('search:geoportal:searchUrl'), $params
            );
        }

        return $result;
    }

    /**
     * @param $url
     * @param $params
     * @return mixed
     */
    private function buildQueryAndDecodeJsonContent($url, $params) {
        return json_decode(file_get_contents($url . http_build_query($params)), true);
    }
}
