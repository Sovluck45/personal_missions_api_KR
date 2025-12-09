<?php

namespace App\Tests\UseCases\CompleteMission;

use App\Entities\Mission;
use App\UseCases\CompleteMission\UseCase as CompleteMissionUseCase;
use App\UseCases\CompleteMission\InputData as CompleteInputData;
use App\UseCases\CompleteMission\OutputData as CompleteOutputData;
use App\InterfaceAdapters\Repositories\MissionRepositoryInterface;
use App\InterfaceAdapters\Repositories\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;

class UseCaseTest extends TestCase
{
    private MissionRepositoryInterface $mockMissionRepo;
    private UserRepositoryInterface $mockUserRepo;
    private CompleteMissionUseCase $useCase;

    protected function setUp(): void
    {
        $this->mockMissionRepo = $this->createMock(MissionRepositoryInterface::class);
        $this->mockUserRepo = $this->createMock(UserRepositoryInterface::class);

        $this->useCase = new CompleteMissionUseCase(
            $this->mockMissionRepo,
            $this->mockUserRepo
        );
    }

    public function testExecuteSuccess(): void
    {
        $userId = 'user123';
        $missionId = 'mission333';
        $inputData = new CompleteInputData($userId, $missionId);

        // Создаём миссию, статус которой уже 'completed', но награда не получена
        $mission = $this->createMock(Mission::class);
        $mission->method('getUserId')->willReturn($userId);
        $mission->method('isCompleted')->willReturn(true);
        $mission->method('canClaimRewards')->willReturn(true);

        $this->mockMissionRepo->expects($this->once())
            ->method('findById')
            ->with($missionId)
            ->willReturn($mission);

        // Ожидаем вызова markRewardsClaimed на миссии
        $mission->expects($this->once())
            ->method('markRewardsClaimed');

        $this->mockMissionRepo->expects($this->once())
            ->method('update')
            ->with($mission);

        $outputData = $this->useCase->execute($inputData);

        $this->assertInstanceOf(CompleteOutputData::class, $outputData);
        $this->assertNull($outputData->error);
        $this->assertSame($mission, $outputData->updatedMission);
    }

    public function testExecuteMissionNotFound(): void
    {
        $userId = 'user123';
        $missionId = 'nonexistent_mission';
        $inputData = new CompleteInputData($userId, $missionId);

        $this->mockMissionRepo->expects($this->once())
            ->method('findById')
            ->with($missionId)
            ->willReturn(null);

        $outputData = $this->useCase->execute($inputData);

        $this->assertInstanceOf(CompleteOutputData::class, $outputData);
        $this->assertNull($outputData->updatedMission);
        $this->assertStringContainsString('Mission not found', $outputData->error);
    }

    public function testExecuteMissionNotBelongingToUser(): void
    {
        $userId = 'user123';
        $wrongUserId = 'user456';
        $missionId = 'mission444';
        $inputData = new CompleteInputData($userId, $missionId);

        $mission = $this->createMock(Mission::class);
        $mission->method('getUserId')->willReturn($wrongUserId); // Другой пользователь

        $this->mockMissionRepo->expects($this->once())
            ->method('findById')
            ->with($missionId)
            ->willReturn($mission);

        $outputData = $this->useCase->execute($inputData);

        $this->assertInstanceOf(CompleteOutputData::class, $outputData);
        $this->assertNull($outputData->updatedMission);
        $this->assertStringContainsString('Mission does not belong to user', $outputData->error);
    }

    public function testExecuteMissionNotCompletedYet(): void
    {
        $userId = 'user123';
        $missionId = 'mission555';
        $inputData = new CompleteInputData($userId, $missionId);

        $mission = $this->createMock(Mission::class);
        $mission->method('getUserId')->willReturn($userId);
        $mission->method('isCompleted')->willReturn(false); // Не завершена

        $this->mockMissionRepo->expects($this->once())
            ->method('findById')
            ->with($missionId)
            ->willReturn($mission);

        $outputData = $this->useCase->execute($inputData);

        $this->assertInstanceOf(CompleteOutputData::class, $outputData);
        $this->assertNull($outputData->updatedMission);
        $this->assertStringContainsString('Mission is not completed yet', $outputData->error);
    }

    public function testExecuteCannotClaimRewards(): void
    {
        $userId = 'user123';
        $missionId = 'mission666';
        $inputData = new CompleteInputData($userId, $missionId);

        $mission = $this->createMock(Mission::class);
        $mission->method('getUserId')->willReturn($userId);
        $mission->method('isCompleted')->willReturn(true);
        $mission->method('canClaimRewards')->willReturn(false); // Награда уже получена

        $this->mockMissionRepo->expects($this->once())
            ->method('findById')
            ->with($missionId)
            ->willReturn($mission);

        $outputData = $this->useCase->execute($inputData);

        $this->assertInstanceOf(CompleteOutputData::class, $outputData);
        $this->assertNull($outputData->updatedMission);
        $this->assertStringContainsString('Cannot claim rewards for this mission', $outputData->error);
    }
}
