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
                'driver'   => $_ENV['DB_DRIVER'] ?? 'pdo_mysql', // Или 'pdo_pgsql' и т.д.
                'host'     => $_ENV['DB_HOST'] ?? 'localhost',
                'port'     => $_ENV['DB_PORT'] ?? 3306,
                'dbname'   => $_ENV['DB_NAME'] ?? 'personal_missions',
                'user'     => $_ENV['DB_USER'] ?? 'root', // Убедитесь, что это соответствует настройкам в docker-compose.yml
                'password' => $_ENV['DB_PASS'] ?? '',     // Убедитесь, что это соответствует настройкам в docker-compose.yml
                'charset'  => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
                // --- НОВОЕ: Настройки для избежания SSL ---
                // Эти параметры передаются Doctrine DBAL, который затем передаёт их PDO
                'driverOptions' => [
                    \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false, // Отключаем проверку SSL-сертификата сервера
                    // Другие возможные опции, если нужно:
                    // \PDO::MYSQL_ATTR_SSL_CA => '/path/to/ca-cert.pem',
                    // \PDO::MYSQL_ATTR_SSL_CERT => '/path/to/client-cert.pem',
                    // \PDO::MYSQL_ATTR_SSL_KEY => '/path/to/client-key.pem',
                ],
                // --- НОВОЕ: Дополнительные настройки PDO ---
                'pdo_options' => [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, // PDO будет выбрасывать исключения при ошибках
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC, // Режим выборки по умолчанию
                    // \PDO::MYSQL_ATTR_MULTI_STATEMENTS => false, // Опционально: отключить множественные операторы
                ],
                // --- КОНЕЦ НОВОГО ---
            ];

            self::$connection = DriverManager::getConnection($dbParams);
        }

        return self::$connection;
    }
}