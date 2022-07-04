<?php

use PHPUnit\Framework\TestCase;
use Mapbender\Core\User\UserPermissions;


class UserPermissionTest extends TestCase
{
    protected function setUp()
    {
        $user = $this->createMock('Mapbender\Core\User\User');
        $user->method("isAuthenticated")->willReturn(false);
        $user->method("getUserId")->willReturn(2);
        $this->obj = new UserPermissions($user);
    }

    public function test_hasPermission()
    {
        $actual = $this->obj->hasPermission("Fake Permission");
        $this->assertFalse($actual, "Should not have a non-existing permission");
    }

    public function test_getPermissions()
    {
        $actual = $this->obj->getPermissions();
        $this->assertEmpty($actual, "Should not any permissions");
    }

    public function test_addPermission()
    {
        $this->obj->addPermission("Foo");

        $actual = $this->obj->hasPermission("Foo");
        $this->assertTrue($actual, "Should have permissions 'Foo' set");
        
        $actual = $this->obj->getPermissions();
        $this->assertContains("Foo", $actual, "Should contain the set permission");
        
        $this->assertEquals(1, count($actual), "Should contain 1 permission");
    }
    
    public function test_removePermission()
    {
        $this->obj->addPermission("A");
        $this->obj->addPermission("B");
        $this->obj->addPermission("C");
        $this->obj->addPermission("D");

        $permissions = $this->obj->getPermissions();
        $this->assertEquals(4, count($permissions), "Should contain 4 permissions");
        $this->assertContains("C", $permissions, "Should still contain permission 'C'");
        
        $this->obj->removePermission("C");
        $permissions = $this->obj->getPermissions();
        $this->assertNotContains("C", $permissions, "Should not contain permission 'C' any more");
    }
}
