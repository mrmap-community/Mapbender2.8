<?php

declare(strict_types=1);

namespace Mapbender\Core\User;

require_once "/opt/geoportal/mapbender/lib/class_Mapbender.php";


class User implements UserInterface
{
    protected $username;
    protected $userid;
    protected $usermail;

    private $authenticated;

    public function __construct()
    {
        $this->getMapbenderUser();
        $this->permissions = new UserPermissions();
    }

    public function isAuthenticated(): bool
    {
        return $this->authenticated;
    }

    public function getUserId(): int
    {
        if (!isset($this->userid)) return -1;
        return (int)$this->userid;
    }

    public function getUserName(): string
    {
        if (!isset($this->username)) return "";
        return $this->username;
    }

    public function getUserMail(): string
    {
        if (!$this->usermail) return "";
        return $this->usermail;
    }

    public function getUserEMail(): string
    {
        return $this->getUserMail();
    }

    protected function getMapbenderUser()
    {
        session_start();
        $this->userid = \Mapbender::session()->get('mb_user_id');
        $this->username = \Mapbender::session()->get('mb_user_name');
        $this->usermail = \Mapbender::session()->get('mb_user_email');

        (is_numeric($this->userid) && $this->userid != 2) ? $this->authenticated = true : $this->authenticated = false;
    }
}
