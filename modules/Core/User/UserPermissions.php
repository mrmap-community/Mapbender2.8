<?php

declare(strict_types=1);

namespace Mapbender\Core\User;

require_once '/opt/geoportal/mapbender/http/classes/class_administration.php';


class UserPermissions implements UserPermissionsInterface
{
    protected $permissions;

    public function __construct()
    {
        $this->permissions = array();
    }

    public function hasPermission(string $permissionName): bool
    {
        return in_array($permissionName, $this->permissions);
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function addPermission(string $permissionName)
    {
        $index = array_search($permissionName, $this->permissions, true);

        if (!$index) array_push($this->permissions, $permissionName);
    }

    public function removePermission(string $permissionName)
    {
        $index = array_search($permissionName, $this->permissions, true);
        if (!$index) return;

        unset($this->permissions[$index]);
    }
}
