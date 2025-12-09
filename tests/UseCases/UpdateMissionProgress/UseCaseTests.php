<?php

namespace App\Tests\UseCases\UpdateMissionProgress;

use App\Entities\Mission;
use App\Entities\MissionType;
use App\Entities\Reward;
use App\UseCases\UpdateMissionProgress\UseCase as UpdateMissionProgressUseCase;
use App\UseCases\UpdateMissionProgress\InputData as UpdateProgressInputData;
use App\UseCases\UpdateMissionProgress\OutputData as UpdateProgressOutputData;
use App\InterfaceAdapters\Repositories\MissionRepositoryInterface;
use App\InterfaceAdapters\Repositories\MissionTypeRepositoryInterface;
use PHPUnit\Framework\TestCase;

class UseCaseTest extends TestCase
{
    private MissionRepositoryInterface $mockMissionRepo;
    private MissionTypeRepositoryInterface $mockMissionTypeRepo;
    private UpdateMissionProgressUseCase $useCase;

    protected function setUp(): void
    {
        $this->mockMissionRepo = $this->createMock(MissionRepositoryInterface::class);
        $this->mockMissionTypeRepo = $this->createMock(MissionTypeRepositoryInterface::class);

        $this->useCase = new UpdateMissionProgressUseCase(
            $this->mockMissionRepo,
            $this->mockMissionTypeRepo
        );
    }

    public function testExecuteSuccessProgressUpdate(): void
    {
        $userId = 'user123';
        $missionId = 'mission456';
        $progressDelta = 2;
        $inputData = new UpdateProgressInputData($userId, $missionId, $progressDelta);

        // Подготовка моков
        $reward = new Reward('gold', 50);
        $missionType = new MissionType('type_collect_wood', 'Сбор древесины', 'Собирай древесину.', 'Collect N wood', $reward);

        // Создаём миссию с начальным прогрессом 3, цель 10 (предполагаем, что цель хранится в MissionType)
        $mission = new Mission(
            id: $missionId,
            userId: $userId,
            missionTypeId: $missionType->getId(),
            status: Mission::STATUS_ASSIGNED,
            progress: 3,
            expiresAt: new \DateTimeImmutable('+1 day')
        );

        $this->mockMissionRepo->expects($this->once())
            ->method('findById')
            ->with($missionId)
            ->willReturn($mission);

        $this->mockMissionTypeRepo->expects($this->once())
            ->method('findById')
            ->with('type_collect_wood') // ID типа миссии
            ->willReturn($missionType);

        // Ожидаем, что метод update будет вызван с обновлённой миссией
        // Проверим, что прогресс увеличился на 2, и статус изменился на in_progress
        $this->mockMissionRepo->expects($this->once())
            ->method('update')
            ->with($this->callback(function ($updatedMission) use ($mission) {
                // Проверяем, что прогресс обновлён (3 + 2 = 5), статус изменился на in_progress
                return $updatedMission->getProgress() === 5 && $updatedMission->getStatus() === Mission::STATUS_IN_PROGRESS;
            }));

        // Выполнение
        $outputData = $this->useCase->execute($inputData);

        // Проверка
        $this->assertInstanceOf(UpdateProgressOutputData::class, $outputData);
        $this->assertNull($outputData->error);
        $this->assertInstanceOf(Mission::class, $outputData->updatedMission);
        $this->assertSame(5, $outputData->updatedMission->getProgress());
        $this->assertSame(Mission::STATUS_IN_PROGRESS, $outputData->updatedMission->getStatus());
    }
}
