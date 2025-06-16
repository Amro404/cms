<?php
namespace Src\Domain\User\DTOs;

class CreateUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?array $roles = null,
        public ?array $permissions = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            $data['name'],
            $data['email'],
            $data['password'],
            $data['roles'] ?? null,
            $data['permissions'] ?? null,
        );
    }
}
