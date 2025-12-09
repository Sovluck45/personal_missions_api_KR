<?php

namespace App\Entities;

/**
 * Сущность пользователя.
 * Хранит основную информацию о пользователе системы.
 */
class User
{
    /**
     * @param string $id Уникальный идентификатор пользователя.
     * @param string $username Имя пользователя.
     * @param string $email Адрес электронной почты.
     * @param int $level Уровень пользователя.
     * @param int $experience Количество опыта.
     * @param \DateTimeImmutable $createdAt Дата создания пользователя.
     * @param \DateTimeImmutable|null $lastLoginAt Дата последнего входа (может быть null).
     */
    public function __construct(
        private readonly string $id,
        private readonly string $username,
        private readonly string $email,
        private int $level = 1,
        private int $experience = 0,
        private readonly \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
        private readonly ?\DateTimeImmutable $lastLoginAt = null
    ) {
        // Можно добавить валидацию здесь, если нужно
        if ($this->level < 1) {
            throw new \InvalidArgumentException('Level must be at least 1');
        }
        if ($this->experience < 0) {
            throw new \InvalidArgumentException('Experience cannot be negative');
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getExperience(): int
    {
        return $this->experience;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    /**
     * Добавляет опыт пользователю.
     * @param int $amount Количество опыта для добавления.
     */
    public function addExperience(int $amount): void
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Experience amount to add cannot be negative');
        }
        $this->experience += $amount;
        // Здесь можно добавить логику повышения уровня при достижении порога
    }

    /**
     * Увеличивает уровень пользователя.
     */
    public function levelUp(): void
    {
        $this->level++;
    }
}