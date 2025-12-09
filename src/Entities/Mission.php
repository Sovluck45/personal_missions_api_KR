<?php

namespace App\Entities;

class Mission
{
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';

    public function __construct(
        public readonly string $id,
        public readonly string $userId,
        public readonly string $missionTypeId,
        public string $status,
        public int $progress,
        public readonly \DateTimeImmutable $createdAt,
        public readonly \DateTimeImmutable $expiresAt,
        public bool $rewardsClaimed,
        public readonly int $objectiveValue // <-- Добавлено
    ) {
    }
}