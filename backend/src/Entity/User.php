<?php

namespace App\Entity;

use ArrayAccess;

/**
 * Class representing the currently logged-in user.
 *
 * @implements ArrayAccess<array-key, mixed>
 */
final class User implements ArrayAccess
{
    public function __construct(
        private readonly array $data
    ) {
    }

    public function isAdmin(): bool
    {
        return $this->data['is_admin'] === 1;
    }

    public function isMod(bool $strict = false): bool
    {
        if (!$strict && $this->isAdmin()) {
            return true;
        }

        return $this->data['is_mod'] === 1;
    }

    public function isTeam(): bool
    {
        return $this->data['is_team'] === 1;
    }

    public function isDj(): bool
    {
        return $this->data['is_dj'] === 1;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('Cannot modify user object.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('Cannot modify user object.');
    }
}
