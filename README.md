Система персональных миссий

![Personal Missions API](https://img.shields.io/badge/PHP-8.5%2B-blue?logo=php)
![Slim Framework](https://img.shields.io/badge/Slim_Framework-4.x-green)
![MySQL](https://img.shields.io/badge/MySQL-8.x-important)
![Docker](https://img.shields.io/badge/Docker-20.x+-blue?logo=docker)

Описание проекта

Серверная часть системы персональных миссий — API-сервер, который предоставляет функционал для генерации индивидуальных квестов для игроков и отслеживания их выполнения. Система позволяет создавать персонализированные задания разных типов (накопительные, убийства, разовые, с чёрным юмором) и автоматически обновлять прогресс выполнения.

Проект реализован в соответствии с API-first подходом и обеспечивает масштабируемость архитектуры. Система состоит из двух основных частей: серверного API (бэкенд) и веб-интерфейса для тестирования (фронтенд).

Основная функциональность

- Генерация миссий: автоматическое создание персональных квестов для игроков
- Отслеживание прогресса: обновление прогресса выполнения миссий
- Завершение миссий: пометка миссий как выполненных
- Просмотр миссий: получение списка всех миссий пользователя
- Поддержка различных типов миссий:
  - Накопительные (сбор ресурсов, убийства)
  - Разовые (доставка сообщений, поиск сокровищ)
  - Миссии с юмором (тёмные, абсурдные задания)
- Автоматическое создание пользователей: система создаст пользователя при первой генерации миссии
- Система наград: для будущих доработок

Технологический стек

Бэкенд
- Язык: PHP 8.4
- Фреймворк: Slim Framework 4.15
- База данных: MySQL 8.0
- ORM/DBAL: Doctrine DBAL
- Контейнеризация: Docker
- Тестирование: PHPUnit

Фронтенд
- HTML5, CSS3, JavaScript (ES6+)
- AJAX для работы с API

Инфраструктура
- Docker и Docker Compose для контейнеризации
- .env для управления конфигурацией

Требования к окружению

- Docker Engine 20.x+
- Docker Compose 2.x+
- Git (для клонирования репозитория)

Установка и запуск

1. Клонируйте репозиторий:
   git clone https://github.com/ваш-логин/personal-missions-api.git
   cd personal-missions-api
   

2. Создайте файл .env на основе примера:
   cp .env.example .env
   При необходимости отредактируйте параметры подключения к базе данных.

3. Соберите и запустите Docker-контейнеры:
   docker-compose up --build -d

4. Инициализируйте базу данных (при первом запуске):
   docker exec -i personal-missions-api_db_1 mysql -u app_user -papp_pass personal_missions_db < init.sql

5. Установите зависимости PHP через Composer:
   docker exec -it personal-missions-api_app_1 composer install

6. Приложение будет доступно по адресу:
   - API: http://localhost:8082
   - Веб-клиент: http://localhost:8082/client.html

Структура проекта
personal-missions-api/
├── docker-compose.yml               Конфигурация Docker
├── Dockerfile                       Инструкции для сборки образа
├── .env.example                     Пример конфигурации
├── init.sql                         SQL-скрипт для инициализации БД
├── composer.json                    Зависимости PHP
├── public/
│   ├── index.php                    Точка входа API
│   ├── client.html                  Веб-клиент для тестирования
│   ├── css/                         Стили для клиента
│   └── js/                          Скрипты для клиента
├── src/
│   ├── Entities/                    Сущности домена
│   │   └── Mission.php              Сущность миссии
│   └── Services/                    Сервисы бизнес-логики
│       ├── MissionGenerationService.php    Генерация миссий
│       └── MissionProgressUpdateService.php Обновление прогресса
└── tests/
    └── Unit/
        └── Services/                Юнит-тесты
            ├── MissionGenerationServiceTest.php
            └── MissionProgressUpdateServiceTest.php

API-эндпоинты

Генерация новой миссии
http
POST /api/missions/generate
Content-Type: application/json

{
  "userId": "user123"
}

Получение миссий пользователя
http
GET /api/missions/user/{userId}

Обновление прогресса миссии
http
POST /api/missions/{missionId}/progress
Content-Type: application/json

{
  "progressDelta": 5
}

Завершение миссии
http
POST /api/missions/{missionId}/complete

Тестирование

Для запуска юнит-тестов выполните команду:
docker exec -it personal-missions-api_app_1 vendor/bin/phpunit tests

Тесты покрывают основные сценарии работы:
- Генерацию накопительных и разовых миссий
- Обновление прогресса с ограничением по цели
- Автоматическое изменение статуса миссии
- Обработку ошибок (миссия не найдена, принадлежит другому пользователю)

Модель данных

В базе данных используются три основные таблицы:

1. users — информация о пользователях
   - id (PK)
   - username
   - level
   - experience
   - created_at
   - last_login_at

2. mission_types — шаблоны типов миссий
   - id (PK)
   - name
   - description
   - goal_template
   - reward_data (JSON)
   - min_level_required
   - category

3. missions — конкретные экземпляры миссий
   - id (PK)
   - user_id (FK)
   - mission_type_id (FK)
   - status (enum: assigned, in_progress, completed, failed, expired)
   - progress
   - created_at
   - expires_at
   - rewards_claimed
   - objective_value

Доработки и расширения

Возможные направления для дальнейшего развития:

1. Аутентификация и авторизация (JWT, OAuth)
2. Система наград (опыт, золото, предметы)
3. Мобильное приложение (React Native, Flutter)
4. Уровни сложности и персонализация на основе профиля игрока
5. Социальные функции (совместное выполнение, соревнования)
6. Админ-панель для управления типами миссий
7. Система событий и уведомлений
