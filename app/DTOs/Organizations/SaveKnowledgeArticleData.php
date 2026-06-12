<?php

namespace App\DTOs\Organizations;

readonly class SaveKnowledgeArticleData
{
    public function __construct(
        public int $knowledgeBaseId,
        public string $title,
        public string $content,
        public ?string $summary = null,
        public ?string $category = null,
        public ?int $existingId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            knowledgeBaseId: (int) $data['knowledge_base_id'],
            title: $data['title'],
            content: $data['content'],
            summary: $data['summary'] ?? null,
            category: $data['category'] ?? null,
            existingId: isset($data['existing_id']) ? (int) $data['existing_id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'knowledge_base_id' => $this->knowledgeBaseId,
            'title' => $this->title,
            'content' => $this->content,
            'summary' => $this->summary,
            'category' => $this->category,
        ];
    }
}
