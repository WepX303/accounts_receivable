<?php

return [
    'validation_fix' => 'Пожалуйста, исправьте следующее:',
    'search_placeholder' => 'Поиск: имя / телефон / паспорт / договор / clientref...',
    'view_monthly_payments' => 'Посмотреть ежемесячные платежи',
    'empty_title' => 'Выполните поиск, чтобы найти клиентов',
    'empty_desc' => 'Или выберите клиентов в :customers_info и нажмите :payment.',
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

        'payment_date' => 'Дата оплаты',
        'payment_date_help' => 'Можно указать любую прошлую дату. Будущие даты запрещены.',
        'mixed_rule_pay_amount' => 'Правило (смешанная): Наличные + Карта должны равняться сумме оплаты.',

        'receiver_phone_number' => 'Номер телефона получателя',
        'receiver_phone_number_placeholder' => 'Введите только цифры',
        'receiver_phone_number_help' => 'Введите номер телефона компании/команды, на который поступил платеж.',
        'receiver_phone_number_required' => 'Номер телефона получателя не может быть пустым.',
        'receiver_phone_number_digits' => 'Номер телефона получателя должен содержать только цифры.',

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

        'corrected' => 'Исправлено',
        'corrected_from_payment_id' => 'Исправлено из оплаты (ID)',
        'payment_at' => 'Дата оплаты',
        'entered_at' => 'Введено (время)',
        'backdated' => 'Задним числом',
    ],

    'bool' => [
        'yes' => 'Да',
        'no' => 'Нет',
    ],

    'method_values' => [
        'cash' => 'Наличные',
        'card' => 'Карта',
        'mixed' => 'Смешанный',
        'phone' => 'Телефон',
    ],

    'actions' => [
        'correct' => 'Исправить',
        'void' => 'Аннулировать',
    ],
    'badges' => [
        'voided' => 'Аннулирован',
    ],
    'voided_by' => 'Аннулировал',
    'corrected_by' => 'Исправил',

    'common' => [
        'cancel' => 'Отмена',
    ],

    'modals' => [
        'void' => [
            'title' => 'Аннулировать оплату №:id',
            'desc' => 'Сумма будет снята с долга клиента, а оплата будет помечена как аннулированная.',
            'reason_label' => 'Причина (обязательно)',
            'confirm' => 'Аннулировать оплату',
        ],
        'correct' => [
            'title' => 'Исправить оплату',
            'payment_date_help' => 'Можно прошлые даты. Будущие запрещены.',
            'received_amount' => 'Полученная сумма',
            'payment_method' => 'Способ оплаты',
            'note' => 'Заметка',
            'reason_label' => 'Причина (почему исправляем)',
            'reason_placeholder' => 'Напр.: неверная сумма/дата/метод',
            'warning' => 'Старая оплата будет аннулирована и будет создана новая оплата.',
            'confirm' => 'Сохранить исправление',
        ],
    ],


];
