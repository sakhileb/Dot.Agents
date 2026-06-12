<?php

declare(strict_types=1);

namespace App\DTOs\Organizations;

readonly class DeleteDepartmentData
{
    public function __construct(
        public int $departmentId,
    ) {}

    public static function fromId(int $departmentId): self
    {
        return new self(departmentId: $departmentId);
    }
}
