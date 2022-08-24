<?php

declare(strict_types=1);

namespace Mapbender\Core\User;


interface UserInterface
{
    public function isAuthenticated(): bool;

    public function getUserId(): int;
    public function getUserName(): string;
    public function getUserMail(): string;
}
