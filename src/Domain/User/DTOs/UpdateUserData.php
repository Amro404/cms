<?php
namespace Src\Domain\User\DTOs;

class UpdateUserData
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $password = null,
        public ?array $roles = null,
        public ?array $permissions = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            $data['name'] ?? null,
            $data['email'] ?? null,
            $data['password'] ?? null,
            $data['roles'] ?? null,
            $data['permissions'] ?? null,
        );
    }
}
