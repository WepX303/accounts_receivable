<?php

return [
    'validation_fix' => 'Пожалуйста, исправьте следующее:',
    'search_placeholder' => 'Поиск: имя / телефон / паспорт / договор / clientref...',

    'empty_title' => 'Выполните поиск, чтобы найти клиентов',
    'empty_desc'  => 'Или выберите клиентов в :customers_info и нажмите :payment.',
    'customers_info' => 'Информация о клиентах',
    'payment' => 'Оплата',

    'th' => [
        'name' => 'Имя',
        'contract' => 'Договор',
        'remaining' => 'Остаток',
        'paid_local' => 'Оплачено (Local)',
        'last_paid' => 'Последняя оплата',
    ],

    'local_missing' => 'LOCAL ОТСУТСТВУЕТ',
    'no_records' => 'Записей не найдено.',
    'total' => 'Всего',
    'page' => 'Страница',

    'select_customer' => 'Выберите клиента, чтобы увидеть детали и сохранить оплату.',

    'detail' => [
        'total_local' => 'Итого Local (amount_local)',
        'paid_local' => 'Оплачено Local',
        'remaining' => 'Остаток',
        'last_paid_by' => 'Кем оплачено',
        'last_paid_at' => 'Дата последней оплаты',
    ],

    'alert_local_missing' => 'Local поля отсутствуют (amount_local / paid_local NULL). Оплата отключена.',
    'alert_closed' => 'У клиента нет долга (paid_local >= amount_local). Оплата отключена.',

    'last_payment_detail' => 'Детали последней оплаты',
    'payment_history_last_10' => 'История оплат (Последние 10)',
    'no_payment_history' => 'Истории оплат нет.',

    'received' => 'Получено',
    'applied' => 'Зачтено в долг',
    'change' => 'Сдача',
    'cash' => 'Наличные',
    'card' => 'Карта',
    'by' => 'Кто',
    'remaining' => 'Остаток',

    'form' => [
        'received_amount' => 'Полученная сумма (клиент дает)',
        'remaining' => 'Остаток',
        'applied_to_debt' => 'Зачтено в долг',
        'change_to_customer' => 'Сдача (клиенту)',

        'payment_method' => 'Способ оплаты',
        'method_cash' => 'Наличные',
        'method_card' => 'Карта',
        'method_mixed' => 'Смешанный',
        'method_phone' => 'Телефон',

        'all_cash' => 'Все наличными',
        'all_card' => 'Все картой',
        'half' => '50/50',
        'mixed_rule' => 'Наличные + Карта должны равняться полученной сумме.',

        'note' => 'Заметка',
        'note_placeholder' => 'Необязательно...',
        'save_payment' => 'Сохранить оплату',
    ],

    'detail_keys' => [
        'method' => 'Способ',
        'received' => 'Получено',
        'applied' => 'Зачтено',
        'change' => 'Сдача',
        'cash' => 'Наличные',
        'card' => 'Карта',
        'total_local' => 'Итого Local',
        'old_paid_local' => 'Было оплачено (Local)',
        'new_paid_local' => 'Стало оплачено (Local)',
        'old_remaining' => 'Было остаток',
        'new_remaining' => 'Стало остаток',
        'note' => 'Заметка',
    ],

    'method_values' => [
        'cash'  => 'Наличные',
        'card'  => 'Карта',
        'mixed' => 'Смешанный',
        'phone' => 'Телефон',
    ],
];
