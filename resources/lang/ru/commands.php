<?php

return [
    'page_title' => 'Центр команд',
    'page_description' => 'Административные команды для обслуживания операций по дебиторской задолженности.',

    'run_command' => 'Выполнить команду',

    'risk' => [
        'low' => 'Низкий риск',
        'medium' => 'Средний риск',
        'high' => 'Высокий риск',
    ],

    'commands' => [
        'clear_all_caches' => [
            'title' => 'Очистить все кэши',
            'description' => 'Очищает кэш представлений, конфигурации, маршрутов и приложения.',
            'confirm' => 'Вы уверены, что хотите очистить все кэши?',
            'success' => 'Все кэши успешно очищены.',
        ],
        'credits_resync_amount_local' => [
            'title' => 'Credits / Синхронизация Amount Local',
            'description' => 'Синхронизирует credits.amount_local с credits.amount для несоответствующих записей.',
            'confirm' => 'Вы уверены, что хотите пересинхронизировать значения amount_local?',
            'success' => 'Синхронизация завершена. Затронуто: :affected, Обновлено: :updated',
        ],
    ],
];