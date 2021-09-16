<?php

use PHPUnit\Framework\TestCase;
use Mapbender\Core\View\View;


class ViewTest extends TestCase
{
    public function test_public_attributes()
    {
        $path = "templates";
        $obj = new View();
        
        $this->assertObjectHasAttribute("context", $obj);
        $this->assertObjectHasAttribute("template_name", $obj);

        $obj->template_path = $path;
        $this->assertObjectHasAttribute("template_path", $obj);
    }

    public function test_get_context()
    {
        $obj = new View();
        
        $this->assertEmpty($obj->get_context(), "Should return an empty array");
        
        $obj->context = [1, 2, 3];
        $this->assertContains(1, $obj->get_context(), "Should contain '1'");
        $this->assertEquals(2, $obj->get_context()[1], "Should have correct elements");
        $this->assertEquals(3, count($obj->get_context()), "Should contain 3 elements");
    }
}
