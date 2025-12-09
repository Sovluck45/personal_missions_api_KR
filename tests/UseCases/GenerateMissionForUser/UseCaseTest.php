<?php

namespace App\Tests\UseCases\GenerateMissionForUser;

use App\Entities\Mission;
use App\Entities\MissionType;
use App\Entities\Reward;
use App\Entities\User;
use App\UseCases\GenerateMissionForUser\UseCase as GenerateMissionUseCase;
use App\UseCases\GenerateMissionForUser\InputData as GenerateInputData;
use App\UseCases\GenerateMissionForUser\OutputData as GenerateOutputData;
use App\InterfaceAdapters\Repositories\MissionRepositoryInterface;
use App\InterfaceAdapters\Repositories\MissionTypeRepositoryInterface;
use App\InterfaceAdapters\Repositories\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;

class UseCaseTest extends TestCase
{
    private UserRepositoryInterface $mockUserRepo;
    private MissionTypeRepositoryInterface $mockMissionTypeRepo;
    private MissionRepositoryInterface $mockMissionRepo;
    private GenerateMissionUseCase $useCase;

    protected function setUp(): void
    {
        // Создаём моки для зависимостей
        $this->mockUserRepo = $this->createMock(UserRepositoryInterface::class);
        $this->mockMissionTypeRepo = $this->createMock(MissionTypeRepositoryInterface::class);
        $this->mockMissionRepo = $this->createMock(MissionRepositoryInterface::class);

        // Создаём экземпляр Use Case с моками
        $this->useCase = new GenerateMissionUseCase(
            $this->mockMissionTypeRepo,
            $this->mockMissionRepo,
            $this->mockUserRepo
        );
    }

    public function testExecuteSuccess(): void
    {
        $userId = 'user123';
        $inputData = new GenerateInputData($userId);

        // Подготовка моков
        $user = new User('user123', 'testuser', 'test@example.com');
        $this->mockUserRepo->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        $reward = new Reward('experience', 50);
        $missionType = new MissionType('type_collect_wood', 'Сбор древесины', 'Собирай древесину.', 'Collect N wood', $reward);
        $this->mockMissionTypeRepo->expects($this->once())
            ->method('findByRequirements')
            ->with(['level' => 1]) // Уровень из $user
            ->willReturn([$missionType]);

        $this->mockMissionRepo->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Mission::class)); // Проверяем, что save вызывается с объектом Mission

        // Выполнение
        $outputData = $this->useCase->execute($inputData);

        // Проверка
        $this->assertInstanceOf(GenerateOutputData::class, $outputData);
        $this->assertNull($outputData->error);
        $this->assertInstanceOf(Mission::class, $outputData->mission);
        $this->assertSame($userId, $outputData->mission->getUserId());
        $this->assertSame('type_collect_wood', $outputData->mission->getMissionTypeId());
        $this->assertEquals(0, $outputData->mission->getProgress()); // Начальный прогресс
        $this->assertEquals(Mission::STATUS_ASSIGNED, $outputData->mission->getStatus());
    }
}
