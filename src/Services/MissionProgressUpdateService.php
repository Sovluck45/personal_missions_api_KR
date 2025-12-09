<?php

namespace App\Services;

use App\Entities\Mission; // Предположим, что сущность существует
use Doctrine\DBAL\Connection;

class MissionProgressUpdateService
{
    public function __construct(
        private Connection $connection
    ) {
    }

    /**
     * Обновляет прогресс миссии.
     *
     * @param string $missionId ID миссии.
     * @param string $userId ID пользователя (для проверки принадлежности).
     * @param int $progressDelta Изменение прогресса.
     * @return array ['updatedMission' => Mission|null, 'error' => string|null]
     */
    public function updateMissionProgress(string $missionId, string $userId, int $progressDelta): array
    {
        // 1. Загрузить миссию
        $selectSql = "SELECT * FROM missions WHERE id = ?";
        $selectStmt = $this->connection->prepare($selectSql);
        $selectStmt->bindValue(1, $missionId);
        $row = $selectStmt->executeQuery()->fetchAssociative();

        if (!$row) {
            return [null, "Mission not found"];
        }

        // 2. Проверить, принадлежит ли миссия пользователю
        if ($row['user_id'] !== $userId) {
            return [null, "Mission does not belong to user"];
        }

        // 3. Получить тип миссии, чтобы узнать цель (objective_value)
        // В оригинальном коде был JOIN, но здесь мы делаем отдельный запрос.
        // Это не идеально, но для тестирования логики обновления подходит.
        $selectTypeSql = "SELECT objective_value FROM missions WHERE id = ?"; // Берём objective_value из самой миссии
        // Нет, цель берётся из mission_types. Давай исправим.
        // Нужно получить objective_value из missions, так как оно там сохраняется при генерации.
        $objectiveValue = $row['objective_value']; // Цель миссии, сохранённая при генерации

        // 4. Обновить прогресс
        $newProgress = $row['progress'] + $progressDelta;

        // 5. Проверить, достигнута ли цель
        $newStatus = $row['status'];
        if ($newProgress >= $objectiveValue) {
            $newProgress = $objectiveValue; // Не превышаем цель
            $newStatus = 'completed';
        } elseif ($row['status'] === 'assigned') {
            $newStatus = 'in_progress'; // Если цель не достигнута, но прогресс увеличен с 'assigned'
        }

        // 6. Сохранить обновлённую миссию
        $updateSql = "UPDATE missions SET progress = ?, status = ? WHERE id = ?";
        $updateStmt = $this->connection->prepare($updateSql);
        $updateStmt->bindValue(1, $newProgress);
        $updateStmt->bindValue(2, $newStatus);
        $updateStmt->bindValue(3, $missionId);
        $updateStmt->executeStatement();

        // 7. Загрузить обновлённую миссию из БД для возврата
        $updatedRow = $selectStmt->executeQuery()->fetchAssociative(); // Повторный запрос для обновлённых данных

        // В реальности, тебе нужно будет адаптировать это под структуру, которую ты хочешь возвращать.
        $updatedMission = new Mission(
            id: $updatedRow['id'],
            userId: $updatedRow['user_id'],
            missionTypeId: $updatedRow['mission_type_id'],
            status: $updatedRow['status'],
            progress: $updatedRow['progress'],
            createdAt: new \DateTimeImmutable($updatedRow['created_at']),
            expiresAt: new \DateTimeImmutable($updatedRow['expires_at']),
            rewardsClaimed: (bool)$updatedRow['rewards_claimed'],
            objectiveValue: (int)$updatedRow['objective_value'] // <-- Включаем цель
        );

        return [$updatedMission, null];
    }
}