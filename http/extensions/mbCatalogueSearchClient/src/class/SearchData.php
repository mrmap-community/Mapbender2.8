<?php

namespace Geoportal\Suche;

/**
 * @TODO: More abstraction e.g. an abstraction layer that handles most of the request stuff
 * @TODO: Move pagination parameters to own pagination module / class
 * @TODO: Move SearchData class to search Folder
 * @TODO: General: Write controllers for params and templating
 */
class SearchData
{
    /**
     * @var
     */
    private $data;
    private $resourceName;

    private $activePage;
    private $startPage;
    private $endPage;
    private $resultsPerPage;

    private $resultsCount;
    private $maxResultsCount;

    private $results;
    private $resourceTitle;
    private $pages;

    /**
     * SearchData constructor.
     * @param array $data
     * @param string $resourceName
     */
    public function __construct($data = array(), $resourceName = "")
    {
        $this->data = $data;
        $this->resourceName = $resourceName;
        $this->calculation($data, $resourceName);
    }

    /**
     * @param array $data
     * @param string $name
     */
    private function calculation($data = array(), $name = "") {
        $this->activePage      = $this->calculateActivePage($data, $name);
        $this->startPage       = $this->calculateStartPage($this->activePage);
        $this->endPage         = $this->calculateEndPage($data, $name, $this->activePage);
        $this->resultsPerPage  = $this->calculateResultsPerPage($data, $name);
        $this->maxResultsCount = $this->calculateMaxResults($data, $name);
        $this->resultsCount    = $this->calculateResults($data, $name);
        $this->pages           = $this->calculatePages();
        $this->results         = $this->getSrv($data, $name);
        $this->resourceTitle   = $this->getTitle($data, $name);
    }

    /**
     * @param array $data
     * @param string $name
     * @return mixed
     */
    private function calculateActivePage($data = array(), $name = "") {
        return $data[$name][$name][$name]['md']['p'];
    }

    /**
     * @param $activePage
     * @return int
     */
    private function calculateStartPage($activePage) {
        return ($activePage - 5) < 1 ? 1 : $activePage - 6;
    }

    /**
     * @param array $data
     * @param $name
     * @param $activePage
     * @return mixed
     */
    private function calculateEndPage($data = array(), $name, $activePage) {
        /** @Todo: Move to pagination module for calculation the endpage based an different params */
        $resultsPerPage = $data[$name][$name][$name]['md']['rpp'];
        if ( ($activePage + 6) > $resultsPerPage ) {
            return $resultsPerPage;
        }
        else {
            return ($activePage + 6);
        }
    }

    /**
     * @param array $data
     * @param string $name
     * @return mixed
     */
    private function calculateResultsPerPage($data = array(), $name = "") {
        return $data[$name][$name][$name]['md']['rpp'];
    }

    /**
     * @param array $data
     * @param string $name
     * @return mixed
     */
    private function calculateMaxResults($data = array(), $name = "") {
        return $data[$name]['categories']['searchMD']['n'];
    }

    /**
     * @param array $data
     * @param string $name
     * @return mixed
     */
    private function calculateResults($data = array(), $name = "") {
        return $data[$name][$name][$name]['md']['nresults'];
    }

    /**
     * Calculate results per page and therefore the number of pages
     * @return float
     */
    private function calculatePages() {
        return ceil($this->resultsCount / $this->resultsPerPage);
    }

    /**
     * @param array $data
     * @param string $name
     * @return mixed
     */
    private function getSrv($data = array(), $name = "") {
        return $data[$name][$name][$name]['srv'];
    }

    /**
     * @param array $data
     * @param string $name
     * @return mixed
     */
    private function getTitle($data = array(), $name = "") {
        return $data['allResources'][$name];
    }

    /**
     * @return array
     */
    public function getData() {
        return array(
            'activePage'   => $this->activePage,
            'startPage'    => $this->startPage,
            'endPage'      => $this->endPage,
            'pages'        => $this->pages,
            'name'         => $this->resourceName,
            'rpp'          => $this->resultsPerPage,
            'resultsCount' => $this->resultsCount,
            'i'            => $this->startPage
        );
    }

    /**
     * @return mixed
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * @return mixed
     */
    public function getResourceTitle()
    {
        return $this->resourceTitle;
    }

    /**
     * @return mixed
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @return int
     */
    public function getMaxResultsCount() {
        return (int) $this->maxResultsCount;
    }

    /**
     * @return int
     */
    public function getResultsCount() {
        return (int) $this->resultsCount;
    }

    /**
     * @return string
     */
    public function getResourceName()
    {
        return $this->resourceName;
    }

    /**
     * @return mixed
     */
    public function getActivePage()
    {
        return $this->activePage;
    }

    /**
     * @param mixed $activePage
     */
    public function setActivePage($activePage)
    {
        $this->activePage = $activePage;
    }

    /**
     * @return mixed
     */
    public function getStartPage()
    {
        return $this->startPage;
    }

    /**
     * @param mixed $startPage
     */
    public function setStartPage($startPage)
    {
        $this->startPage = $startPage;
    }

    /**
     * @return mixed
     */
    public function getEndPage()
    {
        return $this->endPage;
    }

    /**
     * @param mixed $endPage
     */
    public function setEndPage($endPage)
    {
        $this->endPage = $endPage;
    }

}
