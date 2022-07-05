<?php

require_once('vendor/autoload.php');

if (!\App\Config\AppConfig::GROUP) {
    die("Укажите номер группы в файле конфигураций!");
}

$app = new App\Services\VkGroupMembersService(
    \App\Config\AppConfig::VK_TOKEN,
    \App\Config\AppConfig::GROUP
);

echo "Запуск парсинга...." . PHP_EOL;

echo sprintf('Группа: %s, Кол-во подписчиков: %s; ', 131101936, $app->groupMembersCount) . PHP_EOL;

echo "==========" . PHP_EOL;

$app->parseMembers();

echo "==========" . PHP_EOL;

echo sprintf('Итого сохраненных участникв: %s; ', $app->loadMembersCount) . PHP_EOL;


