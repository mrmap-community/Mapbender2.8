<?php

declare(strict_types=1);

namespace Mapbender\Core\User;


interface UserPermissionsInterface
{
    public function hasPermission(string $permissionName): bool;

    public function getPermissions(): array;

    public function addPermission(string $permissionName);

    public function removePermission(string $permissionName);
}
