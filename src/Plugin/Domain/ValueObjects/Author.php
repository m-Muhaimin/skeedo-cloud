<?php

declare(strict_types=1);

namespace Plugin\Domain\ValueObjects;

use JsonSerializable;

class Author implements JsonSerializable
{
    public readonly AuthorName $name;
    public readonly Email $email;
    public readonly Url $homepage;
    public readonly Role $role;

    public function __construct(
        string $name,
        ?string $email = null,
        ?string $homepage = null,
        ?string $role = null
    ) {
        $this->name = new AuthorName($name);
        $this->email = new Email($email);
        $this->homepage = new Url($homepage);
        $this->role = new Role($role);
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'homepage' => $this->homepage,
            'role' => $this->role
        ];
    }
}
