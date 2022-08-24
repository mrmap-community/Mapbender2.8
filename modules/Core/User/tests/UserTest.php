<?php

use PHPUnit\Framework\TestCase;
use Mapbender\Core\User\User;


class UserTest extends TestCase
{
    /**
     * creation
     */
    public function test_user_creation_without_cookies()
    {
        $obj = new User();

        $actual = $obj->isAuthenticated();
        $this->assertFalse($actual, "Should return false, as the user is not authenticated");
        $this->assertEquals(2, $obj->getUserId(), "Should result in the guest user, with ID=2");
        $this->assertEquals('guest', $obj->getUserName(), "Should result in the user 'guest'");
        $this->assertEquals('', $obj->getUserMail(), "Should result in the guest user's mail, which is not set");
    }
}
