<?php

namespace App\DTOs\Social;

readonly class ApproveSocialPostData
{
    public function __construct(
        public int $postId,
        public int $approverId,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            postId: (int) $data['post_id'],
            approverId: (int) $data['approver_id'],
        );
    }

    public function toArray(): array
    {
        return [
            'post_id' => $this->postId,
            'approver_id' => $this->approverId,
        ];
    }
}
