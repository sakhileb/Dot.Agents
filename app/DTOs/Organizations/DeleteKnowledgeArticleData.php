<?php

declare(strict_types=1);

namespace App\DTOs\Organizations;

readonly class DeleteKnowledgeArticleData
{
    public function __construct(
        public int $articleId,
    ) {}

    public static function fromId(int $articleId): self
    {
        return new self(articleId: $articleId);
    }
}
