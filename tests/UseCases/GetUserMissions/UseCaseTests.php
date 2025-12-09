<?php

namespace App\Tests\UseCases\GetUserMissions;

use App\Entities\Mission;
use App\Entities\MissionType;
use App\Entities\Reward;
use App\Entities\User;
use App\UseCases\GetUserMissions\UseCase as GetUserMissionsUseCase;
use App\UseCases\GetUserMissions\InputData as GetUserMissionsInputData;
use App\UseCases\GetUserMissions\OutputData as GetUserMissionsOutputData;
use App\InterfaceAdapters\Repositories\MissionRepositoryInterface;
use PHPUnit\Framework\TestCase;

class UseCaseTest extends TestCase
{
    private MissionRepositoryInterface $mockMissionRepo;
    private GetUserMissionsUseCase $useCase;

    protected function setUp(): void
    {
        $this->mockMissionRepo = $this->createMock(MissionRepositoryInterface::class);

        $this->useCase = new GetUserMissionsUseCase(
            $this->mockMissionRepo
        );
    }

    public function testExecuteSuccessWithMissions(): void
    {
        $userId = 'user123';
        $inputData = new GetUserMissionsInputData($userId);

        $reward = new Reward('gold', 50);
        $missionType = new MissionType('type1', 'Test Mission', 'A test mission', 'Collect 5 items', $reward);
        $mission = new Mission( // <-- Исправлено: добавлен expiresAt
            id: 'mission123',
            userId: $userId,
            missionTypeId: $missionType->getId(),
            status: Mission::STATUS_IN_PROGRESS,
            progress: 3,
            expiresAt: new \DateTimeImmutable('+1 day') // <-- Исправлено: добавлен expiresAt
        );

        $this->mockMissionRepo->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn([$mission]);

        $outputData = $this->useCase->execute($inputData);

        $this->assertInstanceOf(GetUserMissionsOutputData::class, $outputData);
        $this->assertNull($outputData->error);
        $this->assertIsArray($outputData->missions);
        $this->assertCount(1, $outputData->missions);
        $this->assertSame($mission, $outputData->missions[0]);
    }

    public function testExecuteSuccessNoMissions(): void
    {
        $userId = 'user123';
        $inputData = new GetUserMissionsInputData($userId);

        $this->mockMissionRepo->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn([]);

        $outputData = $this->useCase->execute($inputData);

        $this->assertInstanceOf(GetUserMissionsOutputData::class, $outputData);
        $this->assertNull($outputData->error);
        $this->assertIsArray($outputData->missions);
        $this->assertCount(0, $outputData->missions);
    }
}
