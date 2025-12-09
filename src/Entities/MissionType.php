<?php

namespace App\Entities;

/**
 * Сущность типа миссии.
 * Определяет шаблон миссии: название, описание, цель, награду, требования.
 */
class MissionType
{
    /**
     * @param string $id Уникальный идентификатор типа миссии.
     * @param string $name Название типа миссии.
     * @param string $description Описание типа миссии.
     * @param string $objective Описание цели миссии (например, "Собрать 10 яблок").
     * @param Reward $reward Награда за выполнение миссии.
     * @param array $requirements Массив требований для генерации миссии (например, минимальный уровень).
     */
    public function __construct(
        private readonly string $id,
        private readonly string $name,
        private readonly string $description,
        private readonly string $objective,
        private readonly Reward $reward,
        private readonly array $requirements = []
    ) {
        // Валидация, если нужно
        if (empty($this->id)) {
            throw new \InvalidArgumentException('MissionType ID cannot be empty');
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getObjective(): string
    {
        return $this->objective;
    }

    public function getReward(): Reward
    {
        return $this->reward;
    }

    public function getRequirements(): array
    {
        return $this->requirements;
    }
}