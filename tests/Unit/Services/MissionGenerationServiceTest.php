<?php

namespace App\Tests\Unit\Services;

use App\Entities\Mission;
use App\Services\MissionGenerationService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use PHPUnit\Framework\TestCase;

class MissionGenerationServiceTest extends TestCase
{
    private Connection $mockConnection;
    private MissionGenerationService $service;

    protected function setUp(): void
    {
        $this->mockConnection = $this->createMock(Connection::class);
        $this->service = new MissionGenerationService($this->mockConnection);
    }

    public function testGenerateMissionSuccess(): void
    {
        $userId = 'user123';

        $checkUserSql = 'SELECT COUNT(*) FROM users WHERE id = ?';
        $checkUserStmtMock = $this->createMock(Statement::class);
        $checkUserResultMock = $this->createMock(Result::class);

        $selectTypesStmtMock = $this->createMock(Statement::class);
        $selectTypesResultMock = $this->createMock(Result::class);
        $insertStmtMock = $this->createMock(Statement::class);

        $sqlToMockMap = [
            $checkUserSql => $checkUserStmtMock,
            'SELECT id FROM mission_types' => $selectTypesStmtMock, 
            'INSERT INTO missions ' => $insertStmtMock,             
        ];

        // Подготавливаем ожидания для Connection->prepare, используя returnCallback
        $this->mockConnection->expects($this->exactly(3)) // prepare вызывается 3 раза (проверка юзера, выбор типа, вставка миссии)
            ->method('prepare')
            ->with($this->isType('string')) // Принимаем любой SQL-запрос
            ->willReturnCallback(function ($sql) use ($sqlToMockMap) {
                foreach ($sqlToMockMap as $pattern => $mock) {
                    if (strpos($sql, $pattern) !== false) {
                        return $mock;
                    }
                }
                // Если не найдено совпадение, возвращаем null или бросаем исключение
                $this->fail("No mock found for SQL: $sql");
            });

        // Остальные ожидания для checkUserStmtMock
        $checkUserStmtMock->expects($this->once())
            ->method('bindValue')
            ->with(1, $userId);
        $checkUserStmtMock->expects($this->once())
            ->method('executeQuery')
            ->willReturn($checkUserResultMock);
        $checkUserResultMock->expects($this->once())
            ->method('fetchOne')
            ->willReturn(1);

        // Остальные ожидания для selectTypesStmtMock
        $selectTypesStmtMock->expects($this->once())
            ->method('executeQuery')
            ->willReturn($selectTypesResultMock);
        $selectTypesResultMock->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn(['id' => 'type_collect_wood']);

        // Ожидание для insertStmtMock
        $insertStmtMock->expects($this->once())
            ->method('executeStatement');

        // Выполнение
        [$mission, $error] = $this->service->generateMissionForUser($userId);

        // Проверка
        $this->assertNull($error);
        $this->assertInstanceOf(Mission::class, $mission);
        $this->assertSame($userId, $mission->userId);
        $this->assertSame('type_collect_wood', $mission->missionTypeId);
        $this->assertSame('assigned', $mission->status);
        $this->assertSame(0, $mission->progress);
        $this->assertGreaterThan(0, $mission->objectiveValue);
    }

    public function testGenerateMissionUserNotFoundCreatesUser(): void
    {
        $userId = 'new_user_123';

        $checkUserSql = 'SELECT COUNT(*) FROM users WHERE id = ?';
        $checkUserStmtMock = $this->createMock(Statement::class);
        $checkUserResultMock = $this->createMock(Result::class);

        $insertUserStmtMock = $this->createMock(Statement::class);
        $selectTypesStmtMock = $this->createMock(Statement::class);
        $selectTypesResultMock = $this->createMock(Result::class);
        $insertMissionStmtMock = $this->createMock(Statement::class);

        $sqlToMockMap = [
            $checkUserSql => $checkUserStmtMock,
            'INSERT INTO users ' => $insertUserStmtMock,
            'SELECT id FROM mission_types' => $selectTypesStmtMock,
            'INSERT INTO missions ' => $insertMissionStmtMock, 
        ];

        $this->mockConnection->expects($this->exactly(4)) 
            ->method('prepare')
            ->with($this->isType('string')) 
            ->willReturnCallback(function ($sql) use ($sqlToMockMap) {
                foreach ($sqlToMockMap as $pattern => $mock) {
                    if (strpos($sql, $pattern) !== false) {
                        return $mock;
                    }
                }
                $this->fail("No mock found for SQL: $sql");
            });

        // Остальные ожидания для checkUserStmtMock
        $checkUserStmtMock->expects($this->once())
            ->method('bindValue')
            ->with(1, $userId);
        $checkUserStmtMock->expects($this->once())
            ->method('executeQuery')
            ->willReturn($checkUserResultMock);
        $checkUserResultMock->expects($this->once())
            ->method('fetchOne')
            ->willReturn(0); 

        // Ожидание для insertUserStmtMock
        $insertUserStmtMock->expects($this->once())
            ->method('executeStatement');

        // Остальные ожидания для selectTypesStmtMock
        $selectTypesStmtMock->expects($this->once())
            ->method('executeQuery')
            ->willReturn($selectTypesResultMock);
        $selectTypesResultMock->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn(['id' => 'type_defeat_goblins']);

        // Ожидание для insertMissionStmtMock
        $insertMissionStmtMock->expects($this->once())
            ->method('executeStatement');

        // Выполнение
        [$mission, $error] = $this->service->generateMissionForUser($userId);

        // Проверка
        $this->assertNull($error);
        $this->assertInstanceOf(Mission::class, $mission);
        $this->assertSame($userId, $mission->userId);
        $this->assertSame('type_defeat_goblins', $mission->missionTypeId);
        $this->assertSame('assigned', $mission->status);
        $this->assertSame(0, $mission->progress);
        $this->assertGreaterThan(0, $mission->objectiveValue);
    }

    public function testGenerateMissionNoSuitableTypesFound(): void
    {
        $userId = 'user123';

        // --- Мокаем проверку существования пользователя (найден) ---
        $checkUserSql = 'SELECT COUNT(*) FROM users WHERE id = ?';
        $checkUserStmtMock = $this->createMock(Statement::class);
        $checkUserResultMock = $this->createMock(Result::class);

        // Подготавливаем мок для выбора типа (не найден)
        $selectTypesStmtMock = $this->createMock(Statement::class);
        $selectTypesResultMock = $this->createMock(Result::class);

        $sqlToMockMap = [
            $checkUserSql => $checkUserStmtMock,
            'SELECT id FROM mission_types' => $selectTypesStmtMock,
        ];

        $this->mockConnection->expects($this->exactly(2)) // prepare вызывается 2 раза (проверка юзера, выбор типа)
            ->method('prepare')
            ->with($this->isType('string')) // Принимаем любой SQL-запрос
            ->willReturnCallback(function ($sql) use ($sqlToMockMap) {
                foreach ($sqlToMockMap as $pattern => $mock) {
                    if (strpos($sql, $pattern) !== false) {
                        return $mock;
                    }
                }
                $this->fail("No mock found for SQL: $sql");
            });

        // Остальные ожидания для checkUserStmtMock
        $checkUserStmtMock->expects($this->once())
            ->method('bindValue')
            ->with(1, $userId);
        $checkUserStmtMock->expects($this->once())
            ->method('executeQuery')
            ->willReturn($checkUserResultMock);
        $checkUserResultMock->expects($this->once())
            ->method('fetchOne')
            ->willReturn(1); // Пользователь существует

        // Остальные ожидания для selectTypesStmtMock
        $selectTypesStmtMock->expects($this->once())
            ->method('executeQuery')
            ->willReturn($selectTypesResultMock);
        $selectTypesResultMock->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn(null); // Нет подходящих типов

        // Выполнение
        [$mission, $error] = $this->service->generateMissionForUser($userId);

        // Проверка
        $this->assertNull($mission);
        $this->assertStringContainsString('No suitable mission types found', $error);
    }

}
