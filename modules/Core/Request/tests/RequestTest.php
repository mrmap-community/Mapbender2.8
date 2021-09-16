<?php

use PHPUnit\Framework\TestCase;
use Mapbender\Core\Request\Request;


class RequestTest extends TestCase
{
    /**
     * cookies
     */
    public function test_cookies_should_give_empty_array_for_missing_cookies()
    {
        $_SERVER = [];
        $obj = new Request();

        $actual = $obj->cookies();
        $expected = [];

        $this->assertEquals($expected, $actual, "Unset cookie parameter should result in empty cookie array.");
    }

    public function test_cookies_should_give_empty_array_for_empty_cookies_string()
    {
        $_SERVER["COOKIES"] = "";
        $obj = new Request();

        $actual = $obj->cookies();
        $expected = [];

        $this->assertEquals($expected, $actual, "Empty cookie string should result in empty cookie array.");
    }

    public function test_cookies_should_create_correct_array_for_single_cookie()
    {
        $_SERVER["COOKIES"] = "foo=1";
        $obj = new Request();

        $actual = $obj->cookies();
        $expected = ["foo" => 1];

        $this->assertEquals($expected, $actual, "Empty cookie string should result in empty cookie array.");
    }

    public function test_cookies_should_create_correct_array_for_multiple_cookies()
    {
        $_SERVER["COOKIES"] = "foo=1; bar=2";
        $obj = new Request();

        $actual = $obj->cookies();
        $expected = ["foo" => 1, "bar" => 2];

        $this->assertEquals($expected, $actual, "Empty cookie string should result in empty cookie array.");
    }

    /**
     * query_params
     */
    public function test_query_params_should_give_empty_array_for_missing_option()
    {
        $_SERVER = [];
        $obj = new Request();

        $actual = $obj->query_params();
        $expected = [];

        $this->assertEquals($expected, $actual);
    }

    public function test_query_params_should_give_empty_array_for_empty_string()
    {
        $_SERVER["QUERY_STRING"] = "";
        $obj = new Request();

        $actual = $obj->query_params();
        $expected = [];

        $this->assertEquals($expected, $actual);
    }

    public function test_query_params_should_create_correct_array_for_single_value()
    {
        $_SERVER["QUERY_STRING"] = "foo=1";
        $obj = new Request();

        $actual = $obj->query_params();
        $expected = ["foo" => 1];

        $this->assertEquals($expected, $actual);
    }

    public function test_query_params_should_create_correct_array_for_multiple_values()
    {
        $_SERVER["QUERY_STRING"] = "foo=1&bar=2";
        $obj = new Request();

        $actual = $obj->query_params();
        $expected = ["foo" => 1, "bar" => 2];

        $this->assertEquals($expected, $actual);
    }

    public function test_query_params_should_work_with_issing_values()
    {
        $_SERVER["QUERY_STRING"] = "foo=1&bar=&baz=&s=true";
        $obj = new Request();

        $actual = $obj->query_params();
        $expected = ["foo" => 1, "bar" => "", "baz" => "", "s" => "true"];

        $this->assertEquals($expected, $actual);
    }
}
