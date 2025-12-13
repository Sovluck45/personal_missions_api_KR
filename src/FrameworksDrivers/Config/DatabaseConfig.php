<?php

namespace App\FrameworksDrivers\Config;

use Doctrine\DBAL\DriverManager;
use Dotenv\Dotenv;

class DatabaseConfig
{
    private static ?\Doctrine\DBAL\Connection $connection = null;

    public static function getConnection(): \Doctrine\DBAL\Connection
    {
        if (self::$connection === null) {
            // Загружаем .env файл, если он существует
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../..'); // Поднимаемся в корень проекта
            $dotenv->safeLoad(); // Используем safeLoad, чтобы не было ошибки, если .env отсутствует

            $dbParams = [
                'driver'   => $_ENV['DB_DRIVER'] ?? 'pdo_mysql', 
                'host'     => $_ENV['DB_HOST'] ?? 'localhost',
                'port'     => $_ENV['DB_PORT'] ?? 3306,
                'dbname'   => $_ENV['DB_NAME'] ?? 'personal_missions',
                'user'     => $_ENV['DB_USER'] ?? 'root', 
                'password' => $_ENV['DB_PASS'] ?? '',     
                'charset'  => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
                // Эти параметры передаются Doctrine DBAL, который затем передаёт их PDO
                'driverOptions' => [
                    \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false, // Отключаем проверку SSL-сертификата сервера
                ],
                'pdo_options' => [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, // PDO будет выбрасывать исключения при ошибках
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC, // Режим выборки по умолчанию
                ],
            ];

            self::$connection = DriverManager::getConnection($dbParams);
        }

        return self::$connection;
    }

}
