<?php

declare(strict_types=1);

namespace Mapbender\Core\Request;

use Mapbender\Core\User\User;

class Request implements RequestInterface
{
    private $query_parameters;
    private $cookie_store;

    public function __construct()
    {
        $this->query_parameters = $this->extractQueryParameters();
        $this->cookie_store = $this->extractCookies();
        $this->user = $this->createUser();
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->POST = $_POST;
        $this->GET = $_GET;
        $this->data = $this->extractRequestData();
    }

    public function query_params(): array
    {
        return $this->query_parameters;
    }

    public function cookies(): array
    {
        return $this->cookie_store;
    }

    private function createUser(): User
    {
        return new User();
    }

    private function extractQueryParameters(): array
    {
        $result = array();
        if (!array_key_exists("QUERY_STRING", $_SERVER) || $_SERVER["QUERY_STRING"] === '') return $result;

        $queries = explode("&", $_SERVER["QUERY_STRING"]);
        foreach ($queries as $query) {
            $q = explode("=", $query);
            $parameter = $q[0];
            $value = $q[1];
            $result[$parameter] = $value;
        }
        return $result;
    }

    private function extractCookies(): array
    {
        if (!array_key_exists("COOKIES", $_SERVER)) return [];
        if ($_SERVER["COOKIES"] === '') return [];
        
        $result = array();
        $cookies = explode("; ", $_SERVER["COOKIES"]);
        foreach ($cookies as $pair) {
            $cookie = explode("=", $pair);
            $result[$cookie[0]] = $cookie[1];
        }
        return $result;
    }

    private function extractRequestData(): array
    {
        $data = [];
        if (isset($_GET) && is_array($_GET)) {
            foreach ($_GET as $key => $val) {
                $data[$key] = $val;
            }
        }
        if (isset($_POST) && is_array($_POST)) {
            foreach ($_POST as $key => $val) {
                $data[$key] = $val;
            }
        }
        return $data;
    }
}
