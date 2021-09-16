<?php

use PHPUnit\Framework\TestCase;
use Mapbender\Core\Template\Template;


class TemplateTest extends TestCase
{
    public function test_empty_creation()
    {
        $req = $this->createMock('Mapbender\Core\Request\Request');
        $req->method("query_params")->willReturn(array());
        $req->method("cookies")->willReturn(array());
        $obj = new Template("");

        $this->expectOutputString("");
        $obj->render($req, "");
    }

    public function test_mocked_template()
    {
        $req = $this->createMock('Mapbender\Core\Request\Request');
        $req->method("query_params")->willReturn(array());
        $req->method("cookies")->willReturn(array());
        $dir = __DIR__;
        $filename = "test_mocked_template";
        $obj = new Template($dir);

        $this->expectOutputString("<!DOCTYPE html>\n<html></html>");
        $obj->render($req, $filename);
    }

    public function test_mocked_template_with_context()
    {
        $req = $this->createMock('Mapbender\Core\Request\Request');
        $req->method("query_params")->willReturn(array());
        $req->method("cookies")->willReturn(array());
        $dir = __DIR__;
        $filename = "test_mocked_template_with_context";
        $obj = new Template($dir);
        $context = ["Foo" => "Bar"];

        $this->expectOutputString("<!DOCTYPE html>\n<html>Bar</html>");
        $obj->render($req, $filename, $context);
    }

    public function test_mocked_template_with_query_params()
    {
        $req = $this->createMock('Mapbender\Core\Request\Request');
        $req->method("query_params")->willReturn(["Foo" => "Bar"]);
        $req->method("cookies")->willReturn(array());
        $dir = __DIR__;
        $filename = "test_mocked_template_with_query_params";
        $obj = new Template($dir);

        $this->expectOutputString("<!DOCTYPE html>\n<html>Bar</html>");
        $obj->render($req, $filename);
    }
}
