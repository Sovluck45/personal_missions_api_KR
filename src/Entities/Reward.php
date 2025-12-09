<?php

namespace App\Entities;

/**
 * Сущность награды.
 * Описывает тип и количество награды, которую можно получить за выполнение миссии.
 */
class Reward
{
    /**
     * @param string $type Тип награды (например, 'experience', 'gold', 'item').
     * @param int $amount Количество награды.
     */
    public function __construct(
        private readonly string $type,
        private readonly int $amount
    ) {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Reward amount cannot be negative');
        }
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAmount(): int
    {
        return $amount;
    }
}