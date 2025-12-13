<?php

use DI\Container;
use Dotenv\Dotenv;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

// Подключаем автозагрузчик Composer
require __DIR__ . '/../vendor/autoload.php';

// Загружаем .env файл
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Создаём контейнер DI
$container = new Container();

// Регистрируем подключение к БД
$container->set(\Doctrine\DBAL\Connection::class, function () {
    $dbParams = [
        'driver'   => $_ENV['DB_DRIVER'] ?? 'pdo_mysql',
        'host'     => $_ENV['DB_HOST'] ?? 'localhost',
        'port'     => $_ENV['DB_PORT'] ?? 3306,
        'dbname'   => $_ENV['DB_NAME'] ?? 'personal_missions_db',
        'user'     => $_ENV['DB_USER'] ?? 'app_user',
        'password' => $_ENV['DB_PASS'] ?? 'app_pass',
        'charset'  => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
        'driverOptions' => [
            \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ],
        'pdo_options' => [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ],
    ];

    return \Doctrine\DBAL\DriverManager::getConnection($dbParams);
});

// Устанавливаем контейнер для Slim
AppFactory::setContainer($container);
$app = AppFactory::create();

// Добавляем middleware для парсинга тела запроса
$app->addBodyParsingMiddleware();

use App\Services\MissionGenerationService;
use App\Services\MissionProgressUpdateService;

// Настройка обработки ошибок
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Настройка CORS
$corsMiddleware = new \Tuupola\Middleware\CorsMiddleware([
    "origin" => ["*"],
    "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS"],
    "headers.allow" => ["Content-Type", "Authorization"],
    "headers.expose" => ["*"],
    "credentials" => true,
    "cache" => 86400
]);
$app->add($corsMiddleware);

$app->post('/api/missions/generate', function (Request $request, Response $response) use ($container) {
    $parsedBody = $request->getParsedBody();
    $userId = $parsedBody['userId'] ?? null;

    if (!$userId) {
        $payload = ['error' => 'User ID is required'];
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus(400);
    }

    $connection = $container->get(\Doctrine\DBAL\Connection::class);

    $checkUserSql = "SELECT COUNT(*) FROM users WHERE id = ?";
    $checkUserStmt = $connection->prepare($checkUserSql);
    $checkUserStmt->bindValue(1, $userId);
    $userExists = $checkUserStmt->executeQuery()->fetchOne() > 0;

    if (!$userExists) {
        $insertUserSql = "INSERT INTO users (id, username, level, experience, created_at) VALUES (?, ?, ?, ?, ?)";
        $insertUserStmt = $connection->prepare($insertUserSql);
        $insertUserStmt->bindValue(1, $userId);
        $insertUserStmt->bindValue(2, $userId); 
        $insertUserStmt->bindValue(3, 1);
        $insertUserStmt->bindValue(4, 0); 
        $insertUserStmt->bindValue(5, (new \DateTimeImmutable())->format('Y-m-d H:i:s'));
        $insertUserStmt->executeStatement();
        error_log("DEBUG: Created user {$userId}"); 
    }

    // Получаем список всех доступных типов миссий
    $selectTypesSql = "SELECT id FROM mission_types";
    $selectTypesStmt = $connection->prepare($selectTypesSql);
    $result = $selectTypesStmt->executeQuery();
    $missionTypeIds = [];
    while ($row = $result->fetchAssociative()) {
        $missionTypeIds[] = $row['id'];
    }

    // Выбираем случайный тип миссии
    $defaultMissionTypeId = $missionTypeIds[array_rand($missionTypeIds)];

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

    // Миссии ивентовые
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

    $missionId = uniqid();
    $expiresAt = (new \DateTimeImmutable())->modify('+1 day')->format('Y-m-d H:i:s');

    // Вставляем миссию в БД
    $sql = "INSERT INTO missions (id, user_id, mission_type_id, status, progress, created_at, expires_at, rewards_claimed, objective_value) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $connection->prepare($sql);
    $stmt->bindValue(1, $missionId);
    $stmt->bindValue(2, $userId);
    $stmt->bindValue(3, $defaultMissionTypeId);
    $stmt->bindValue(4, 'assigned');
    $stmt->bindValue(5, 0);
    $stmt->bindValue(6, (new \DateTimeImmutable())->format('Y-m-d H:i:s'));
    $stmt->bindValue(7, $expiresAt);
    $stmt->bindValue(8, 0);
    $stmt->bindValue(9, $objectiveValue); // Сохраняем случайное значение цели
    $stmt->executeStatement();
    
    $selectTypeNameSql = "SELECT name FROM mission_types WHERE id = ?";
    $selectTypeNameStmt = $connection->prepare($selectTypeNameSql);
    $selectTypeNameStmt->bindValue(1, $defaultMissionTypeId);
    $typeName = $selectTypeNameStmt->executeQuery()->fetchOne(); 

    // Формируем ответ
    $payload = [
        'id' => $missionId,
        'userId' => $userId,
        'missionTypeId' => $defaultMissionTypeId,
        'missionName' => $typeName, 
        'status' => 'assigned',
        'progress' => 0,
        'createdAt' => (new \DateTimeImmutable())->format('c'),
        'expiresAt' => (new \DateTimeImmutable($expiresAt))->format('c'),
        'rewardsClaimed' => false,
    ];

    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus(200);
});

$app->get('/api/missions/user/{userId}', function (Request $request, Response $response, array $args) use ($container) {
    $userId = $args['userId'];

    $connection = $container->get(\Doctrine\DBAL\Connection::class);
    $sql = "SELECT id, user_id, mission_type_id, status, progress, created_at, expires_at, rewards_claimed, objective_value FROM missions WHERE user_id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bindValue(1, $userId);
    $result = $stmt->executeQuery();

    $missions = [];
    while ($row = $result->fetchAssociative()) {
        $missions[] = [
            'id' => $row['id'],
            'userId' => $row['user_id'],
            'missionTypeId' => $row['mission_type_id'],
            'status' => $row['status'],
            'progress' => $row['progress'],
            'createdAt' => $row['created_at'],
            'expiresAt' => $row['expires_at'],
            'rewardsClaimed' => (bool)$row['rewards_claimed'],
            'objectiveValue' => (int)$row['objective_value'],
        ];
    }

    $response->getBody()->write(json_encode($missions));
    return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus(200);
});

$app->post('/api/missions/{missionId}/progress', function (Request $request, Response $response, array $args) use ($container) {
    $missionId = $args['missionId'];

    // Получаем данные из тела запроса
    $parsedBody = $request->getParsedBody();
    $progressDelta = $parsedBody['progressDelta'] ?? null;

    if ($progressDelta === null) {
        $payload = ['error' => 'Progress delta is required'];
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus(400);
    }

    // Обновляем прогресс миссии в БД
    $connection = $container->get(\Doctrine\DBAL\Connection::class);
    $selectSql = "SELECT * FROM missions WHERE id = ?";
    $selectStmt = $connection->prepare($selectSql);
    $selectStmt->bindValue(1, $missionId);
    $row = $selectStmt->executeQuery()->fetchAssociative();

    if (!$row) {
        $payload = ['error' => 'Mission not found'];
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus(404);
    }

    $maxProgress = $row['objective_value']; 

    $newProgress = $row['progress'] + $progressDelta;
    if ($newProgress > $maxProgress) {
        $newProgress = $maxProgress; 
    }

    $updateSql = "UPDATE missions SET progress = ? WHERE id = ?";
    $updateStmt = $connection->prepare($updateSql);
    $updateStmt->bindValue(1, $newProgress);
    $updateStmt->bindValue(2, $missionId);
    $updateStmt->executeStatement();

    // Получаем обновлённую миссию
    $sql = "SELECT * FROM missions WHERE id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bindValue(1, $missionId);
    $row = $stmt->executeQuery()->fetchAssociative();

    if (!$row) {
        $payload = ['error' => 'Mission not found'];
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus(404);
    }

    $selectTypeNameSql = "SELECT name FROM mission_types WHERE id = ?";
    $selectTypeNameStmt = $connection->prepare($selectTypeNameSql);
    $selectTypeNameStmt->bindValue(1, $row['mission_type_id']);
    $typeName = $selectTypeNameStmt->executeQuery()->fetchOne();

    $payload = [
        'id' => $row['id'],
        'userId' => $row['user_id'],
        'missionTypeId' => $row['mission_type_id'],
        'missionName' => $typeName, 
        'status' => $row['status'],
        'progress' => $row['progress'],
        'createdAt' => $row['created_at'],
        'expiresAt' => $row['expires_at'],
        'rewardsClaimed' => (bool)$row['rewards_claimed'],
    ];

    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus(200);
});

$app->post('/api/missions/{missionId}/complete', function (Request $request, Response $response, array $args) use ($container) {
    $missionId = $args['missionId'];

    // Помечаем миссию как выполненную
    $connection = $container->get(\Doctrine\DBAL\Connection::class);
    $sql = "UPDATE missions SET status = 'completed', rewards_claimed = 1 WHERE id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bindValue(1, $missionId);
    $stmt->executeStatement();

    // Получаем обновлённую миссию
    $sql = "SELECT * FROM missions WHERE id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bindValue(1, $missionId);
    $row = $stmt->executeQuery()->fetchAssociative();

    if (!$row) {
        $payload = ['error' => 'Mission not found'];
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus(404);
    }

    $selectTypeNameSql = "SELECT name FROM mission_types WHERE id = ?";
    $selectTypeNameStmt = $connection->prepare($selectTypeNameSql);
    $selectTypeNameStmt->bindValue(1, $row['mission_type_id']);
    $typeName = $selectTypeNameStmt->executeQuery()->fetchOne(); 

    $payload = [
        'id' => $row['id'],
        'userId' => $row['user_id'],
        'missionTypeId' => $row['mission_type_id'],
        'missionName' => $typeName,
        'status' => $row['status'],
        'progress' => $row['progress'],
        'createdAt' => $row['created_at'],
        'expiresAt' => $row['expires_at'],
        'rewardsClaimed' => (bool)$row['rewards_claimed'],
    ];

    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus(200);
});

// Обработка OPTIONS запросов для CORS
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

// Запуск приложения

$app->run();
