<?php

namespace App\Tests\Unit\Services;

use App\Services\MissionProgressUpdateService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use PHPUnit\Framework\TestCase;

class MissionProgressUpdateServiceTest extends TestCase
{
    private Connection $mockConnection;
    private MissionProgressUpdateService $service;

    protected function setUp(): void
    {
        // Создаём мок Connection
        $this->mockConnection = $this->createMock(Connection::class);
        // Создаём сервис, передав ему мок Connection
        $this->service = new MissionProgressUpdateService($this->mockConnection);
    }

    public function testUpdateProgressMissionNotFoundReturnsError(): void
    {
        $missionId = 'nonexistent_mission';
        $userId = 'user123';
        $progressDelta = 1;

        // --- Мокируем Connection->prepare ---
        $selectStmtMock = $this->createMock(Statement::class);
        $selectResultMock = $this->createMock(Result::class);

        // Подготавливаем ожидания
        // Ожидаем, что prepare будет вызван с SQL для SELECT
        $this->mockConnection->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT * FROM missions WHERE id = ?'))
            ->willReturn($selectStmtMock);

        // На моке Statement ожидаем вызов bindValue, executeQuery
        $selectStmtMock->expects($this->once())
            ->method('bindValue')
            ->with(1, $missionId);
        $selectStmtMock->expects($this->once())
            ->method('executeQuery')
            ->willReturn($selectResultMock);

        // На моке Result ожидаем, что fetchAssociative вернёт null (миссия не найдена)
        $selectResultMock->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn(null);

        // Выполняем тестируемый метод
        [$updatedMission, $error] = $this->service->updateMissionProgress($missionId, $userId, $progressDelta);

        // Проверяем результат
        $this->assertNull($updatedMission); // Mission должна быть null
        $this->assertStringContainsString('Mission not found', $error); // Error должен содержать сообщение
    }
}