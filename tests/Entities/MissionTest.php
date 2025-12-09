<?php

namespace App\Tests\Entities;

use App\Entities\Mission;
use App\Entities\MissionType;
use App\Entities\Reward;
use App\Entities\User;
use PHPUnit\Framework\TestCase;

class MissionTest extends TestCase
{
    public function testIsExpired(): void
    {
        $now = new \DateTimeImmutable();
        $pastDate = $now->modify('-1 hour');
        $futureDate = $now->modify('+1 hour');

        $missionExpired = new Mission(
            id: 'm1',
            userId: 'u1',
            missionTypeId: 't1',
            expiresAt: $pastDate // <-- Исправлено: добавлен expiresAt
        );

        $missionNotExpired = new Mission(
            id: 'm2',
            userId: 'u2',
            missionTypeId: 't2',
            expiresAt: $futureDate // <-- Исправлено: добавлен expiresAt
        );

        $this->assertTrue($missionExpired->isExpired());
        $this->assertFalse($missionNotExpired->isExpired());
    }

    public function testIsCompleted(): void
    {
        $missionCompleted = new Mission(
            id: 'm1',
            userId: 'u1',
            missionTypeId: 't1',
            status: Mission::STATUS_COMPLETED,
            expiresAt: new \DateTimeImmutable('+1 day') // <-- Исправлено: добавлен expiresAt
        );

        $missionInProgress = new Mission(
            id: 'm2',
            userId: 'u2',
            missionTypeId: 't2',
            status: Mission::STATUS_IN_PROGRESS,
            expiresAt: new \DateTimeImmutable('+1 day') // <-- Исправлено: добавлен expiresAt
        );

        $this->assertTrue($missionCompleted->isCompleted());
        $this->assertFalse($missionInProgress->isCompleted());
    }

    public function testUpdateProgress(): void
    {
        $mission = new Mission(
            id: 'm1',
            userId: 'u1',
            missionTypeId: 't1',
            status: Mission::STATUS_ASSIGNED, // Статус assigned
            progress: 0,
            expiresAt: new \DateTimeImmutable('+1 day') // <-- Исправлено: добавлен expiresAt
        );

        // Обновляем прогресс, но не достигаем цели (10)
        $mission->updateProgress(3, 10);
        $this->assertSame(3, $mission->getProgress());
        $this->assertSame(Mission::STATUS_IN_PROGRESS, $mission->getStatus()); // Должно измениться на in_progress

        // Обновляем прогресс, достигаем цель (3 + 7 = 10)
        $mission->updateProgress(7, 10);
        $this->assertSame(10, $mission->getProgress());
        $this->assertSame(Mission::STATUS_COMPLETED, $mission->getStatus()); // Должно измениться на completed
    }

    public function testUpdateProgressCannotUpdateCompleted(): void
    {
        $mission = new Mission(
            id: 'm1',
            userId: 'u1',
            missionTypeId: 't1',
            status: Mission::STATUS_COMPLETED,
            progress: 10,
            expiresAt: new \DateTimeImmutable('+1 day') // <-- Исправлено: добавлен expiresAt
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot update progress for a mission with status: completed');

        $mission->updateProgress(1, 10);
    }

    public function testUpdateProgressCannotUpdateExpired(): void
    {
        $pastDate = (new \DateTimeImmutable())->modify('-1 hour');
        $mission = new Mission(
            id: 'm1',
            userId: 'u1',
            missionTypeId: 't1',
            status: Mission::STATUS_EXPIRED,
            progress: 5,
            expiresAt: $pastDate // <-- Исправлено: добавлен expiresAt
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot update progress for a mission with status: expired');

        $mission->updateProgress(1, 10);
    }

    public function testMarkRewardsClaimed(): void
    {
        $mission = new Mission(
            id: 'm1',
            userId: 'u1',
            missionTypeId: 't1',
            status: Mission::STATUS_COMPLETED,
            progress: 10,
            expiresAt: new \DateTimeImmutable('+1 day') // <-- Исправлено: добавлен expiresAt
        );

        $this->assertFalse($mission->isRewardsClaimed());

        $mission->markRewardsClaimed();

        $this->assertTrue($mission->isRewardsClaimed());
    }

    public function testMarkRewardsClaimedCannotClaimIfNotCompleted(): void
    {
        $mission = new Mission(
            id: 'm1',
            userId: 'u1',
            missionTypeId: 't1',
            status: Mission::STATUS_IN_PROGRESS,
            progress: 5,
            expiresAt: new \DateTimeImmutable('+1 day') // <-- Исправлено: добавлен expiresAt
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot claim rewards for a mission that is not completed.');

        $mission->markRewardsClaimed();
    }

    public function testMarkRewardsClaimedCannotClaimIfAlreadyClaimed(): void
    {
        $mission = new Mission(
            id: 'm1',
            userId: 'u1',
            missionTypeId: 't1',
            status: Mission::STATUS_COMPLETED,
            progress: 10,
            expiresAt: new \DateTimeImmutable('+1 day'),
            rewardsClaimed: true // Уже получена
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Rewards for this mission have already been claimed.');

        $mission->markRewardsClaimed();
    }

    public function testCanClaimRewards(): void
    {
        $completedNotClaimed = new Mission(
            id: 'm1',
            userId: 'u1',
            missionTypeId: 't1',
            status: Mission::STATUS_COMPLETED,
            progress: 10,
            expiresAt: new \DateTimeImmutable('+1 day'),
            rewardsClaimed: false
        );

        $completedClaimed = new Mission(
            id: 'm2',
            userId: 'u2',
            missionTypeId: 't2',
            status: Mission::STATUS_COMPLETED,
            progress: 10,
            expiresAt: new \DateTimeImmutable('+1 day'),
            rewardsClaimed: true
        );

        $notCompleted = new Mission(
            id: 'm3',
            userId: 'u3',
            missionTypeId: 't3',
            status: Mission::STATUS_IN_PROGRESS,
            progress: 5,
            expiresAt: new \DateTimeImmutable('+1 day'),
            rewardsClaimed: false
        );

        $this->assertTrue($completedNotClaimed->canClaimRewards());
        $this->assertFalse($completedClaimed->canClaimRewards());
        $this->assertFalse($notCompleted->canClaimRewards());
    }
}