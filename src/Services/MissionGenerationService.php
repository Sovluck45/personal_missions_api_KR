<?php

namespace App\Services;

use App\Entities\Mission;
use App\Entities\User;
use App\Entities\MissionType;
use Doctrine\DBAL\Connection;

class MissionGenerationService
{
    public function __construct(
        private Connection $connection
    ) {
    }

    /**
     * Генерирует новую миссию для пользователя.
     *
     * @param string $userId ID пользователя.
     * @return array ['mission' => Mission|null, 'error' => string|null]
     */
    public function generateMissionForUser(string $userId): array
    {
        // 1. Проверить, существует ли пользователь
        $checkUserSql = "SELECT COUNT(*) FROM users WHERE id = ?";
        $checkUserStmt = $this->connection->prepare($checkUserSql);
        $checkUserStmt->bindValue(1, $userId);
        $userExists = $checkUserStmt->executeQuery()->fetchOne() > 0;

        if (!$userExists) {
            // Создаём пользователя, если его нет (логика из index.php)
            $insertUserSql = "INSERT INTO users (id, username, level, experience, created_at) VALUES (?, ?, ?, ?, ?)";
            $insertUserStmt = $this->connection->prepare($insertUserSql);
            $insertUserStmt->bindValue(1, $userId);
            $insertUserStmt->bindValue(2, $userId); // Используем ID как username
            $insertUserStmt->bindValue(3, 1); // Начальный уровень
            $insertUserStmt->bindValue(4, 0); // Начальный опыт
            $insertUserStmt->bindValue(5, (new \DateTimeImmutable())->format('Y-m-d H:i:s'));
            $insertUserStmt->executeStatement();
        }

        // 2. Найти подходящий тип миссии
        // Простой выбор случайного типа из доступных
        $selectTypesSql = "SELECT id FROM mission_types ORDER BY RAND() LIMIT 1"; // Используем ORDER BY RAND() для простоты
        $selectTypesStmt = $this->connection->prepare($selectTypesSql);
        $row = $selectTypesStmt->executeQuery()->fetchAssociative();

        if (!$row) {
            return [null, "No suitable mission types found"];
        }

        $defaultMissionTypeId = $row['id'];

        $missionObjectives = [
    // Накопительные (Accumulative) и Убийства (Killing)
    'type_collect_wood' => [5, 15],
    'type_defeat_goblins' => [3, 8],
    'type_hunt_wolves' => [2, 6],
    'type_mine_stone' => [10, 20],
    'type_fish' => [5, 10],
    'type_collect_herbs' => [8, 12],
    'type_kill_bears' => [1, 3],
    'type_mine_coal' => [5, 15],
    'type_gather_apples' => [10, 30],
    'type_slay_orcs' => [2, 5],
    'type_hunt_boars' => [3, 7],
    'type_mine_iron' => [4, 10],
    'type_farm_wheat' => [20, 50],
    'type_collect_flowers' => [15, 25],
    'type_kill_dragons' => [1, 1], 
    'type_mine_gold' => [2, 6],
    'type_gather_mushrooms' => [12, 18],
    'type_slay_undead' => [5, 15],
    'type_hunt_deer' => [4, 8],
    'type_mine_diamonds' => [1, 2], 
    'type_collect_berries' => [20, 40],
    'type_kill_spiders' => [6, 12],
    'type_mine_copper' => [8, 16],
    'type_farm_carrots' => [15, 35],
    'type_gather_logs' => [5, 15],
    'type_kill_slimes' => [10, 20],
    'type_mine_tin' => [6, 12],
    'type_farm_potatoes' => [18, 32],
    'type_collect_feathers' => [25, 35],
    'type_kill_bats' => [8, 16],
    'type_mine_silver' => [3, 7],
    'type_farm_cabbage' => [14, 28],
    'type_gather_branches' => [15, 25],
    'type_kill_skeletons' => [5, 12],
    'type_mine_lead' => [7, 14],
    'type_farm_onions' => [16, 24],
    'type_collect_leaves' => [30, 50],
    'type_kill_zombies' => [6, 14],
    'type_mine_nickel' => [4, 9],
    'type_farm_turnips' => [12, 22],

    // Разовые (One-Time) - цель всегда 1
    'type_find_treasure' => [1, 1],
    'type_deliver_message' => [1, 1],
    'type_craft_potion' => [1, 1],
    'type_rescue_npc' => [1, 1],
    'type_solve_puzzle' => [1, 1],
    'type_talk_to_npc' => [1, 1],
    'type_visit_location' => [1, 1],
    'type_learn_spell' => [1, 1],
    'type_train_skill' => [1, 1],
    'type_donate_item' => [1, 1],
    'type_read_book' => [1, 1],
    'type_plant_tree' => [1, 1],
    'type_clean_up' => [1, 1],
    'type_protect_npc' => [1, 1],
    'type_negotiate_deal' => [1, 1],
    'type_investigate_crime' => [1, 1],
    'type_perform_show' => [1, 1],
    'type_compete_in_race' => [1, 1],
    'type_win_tournament' => [1, 1],
    'type_make_peace' => [1, 1],
    'type_find_secret_room' => [1, 1],
    'type_deliver_gift' => [1, 1],
    'type_craft_tool' => [1, 1],
    'type_rescue_animal' => [1, 1],
    'type_solve_mystery' => [1, 1],
    'type_teach_child' => [1, 1],
    'type_explore_ruins' => [1, 1],
    'type_learn_recipe' => [1, 1],
    'type_practice_magic' => [1, 1],
    'type_donate_money' => [1, 1],
    'type_write_poem' => [1, 1],
    'type_build_shelter' => [1, 1],
    'type_clean_river' => [1, 1],
    'type_protect_village' => [1, 1],
    'type_negotiate_trade' => [1, 1],
    'type_investigate_murder' => [1, 1],
    'type_perform_concert' => [1, 1],
    'type_compete_in_contest' => [1, 1],
    'type_win_competition' => [1, 1],
    'type_make_alliance' => [1, 1],

    'type_find_peace' => [1, 1], 
    'type_count_corpses' => [1, 1], 
    'type_feed_giants' => [1, 1], 
    'type_clean_graveyard' => [1, 1],
    'type_rescue_drowning_man' => [1, 1],
    'type_buy_horse' => [1, 1],
    'type_find_true_love' => [1, 1], 
    'type_steal_from_poor' => [1, 1], 
    'type_help_beggar' => [1, 1],
    'type_build_wall' => [5, 10], 
    'type_dance_with_deaths' => [1, 1], 
    'type_heal_the_dead' => [1, 1], 
    'type_count_stars' => [1, 1], 
    'type_drink_poison' => [1, 1], 
    'type_survive_nightmare' => [1, 1], 
    'type_talk_to_gravestone' => [1, 1], 
    'type_find_happiness' => [1, 1], 
    'type_eat_soup' => [1, 1], 
    'type_watch_sunrise' => [1, 1], 
    'type_hug_a_bear' => [1, 1], 
    'type_kiss_a_skull' => [1, 1], 
    'type_dig_own_grave' => [1, 1], 
    'type_burn_books' => [10, 20], 
    'type_plant_corpses' => [5, 10], 
    'type_listen_to_silence' => [1, 1], 
    'type_count_deaths' => [1, 1], 
    'type_cure_loneliness' => [1, 1], 
    'type_drown_in_tears' => [1, 1], 
    'type_eat_ashes' => [1, 1], 
    'type_kiss_death' => [1, 1], 
    'type_hunt_yourself' => [1, 1], 
    'type_dream_of_peace' => [1, 1], 
    'type_cry_for_no_reason' => [1, 1], 
    'type_burn_memories' => [1, 1], 
    'type_laugh_at_funeral' => [1, 1], 
    'type_feed_cats_corpses' => [5, 10], 
    'type_find_light' => [1, 1], 
    'type_bury_your_past' => [1, 1], 
    'type_sing_to_the_dead' => [1, 1], 
    'type_drink_blood' => [1, 1],
    'type_sleep_in_graveyard' => [1, 1], 
    'type_hug_a_zombie' => [1, 1], 
    'type_count_graves' => [1, 1], 
    'type_dance_on_graves' => [1, 1], 
    'type_cook_human_flesh' => [1, 1],
    'type_watch_world_burn' => [1, 1], 
    'type_pray_to_void' => [1, 1], 
    'type_eat_heart' => [1, 1], 
    'type_find_meaning' => [1, 1],
    'type_kiss_grave' => [1, 1], 
    'type_burn_hopes' => [1, 1], 
];
        $objectiveRange = $missionObjectives[$defaultMissionTypeId] ?? [5, 15]; 
        $objectiveValue = rand($objectiveRange[0], $objectiveRange[1]);

        // 3. Создать новую миссию
        $missionId = uniqid();
        $expiresAt = (new \DateTimeImmutable())->modify('+1 day')->format('Y-m-d H:i:s');

        // 4. Сохранить миссию в БД
        $sql = "INSERT INTO missions (id, user_id, mission_type_id, status, progress, created_at, expires_at, rewards_claimed, objective_value) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(1, $missionId);
        $stmt->bindValue(2, $userId);
        $stmt->bindValue(3, $defaultMissionTypeId);
        $stmt->bindValue(4, 'assigned');
        $stmt->bindValue(5, 0);
        $stmt->bindValue(6, (new \DateTimeImmutable())->format('Y-m-d H:i:s'));
        $stmt->bindValue(7, $expiresAt);
        $stmt->bindValue(8, 0);
        $stmt->bindValue(9, $objectiveValue); 
        $stmt->executeStatement();

        // 5. Создать и вернуть объект миссии (или массив данных)
        $mission = new Mission(
            id: $missionId,
            userId: $userId,
            missionTypeId: $defaultMissionTypeId,
            status: 'assigned',
            progress: 0,
            createdAt: new \DateTimeImmutable(),
            expiresAt: new \DateTimeImmutable($expiresAt),
            rewardsClaimed: false,
            objectiveValue: $objectiveValue
        );

        return [$mission, null];
    }

}
