<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AUTH
    |--------------------------------------------------------------------------
    */
    'auth' => [
        'login_required' => 'Giriş meýdany hökmanydyr.',
        'password_required' => 'Parol meýdany hökmanydyr.',

        'email_not_found' => 'Bu email bilen hasap tapylmady.',
        'phone_not_found' => 'Bu telefon belgisi bilen hasap tapylmady.',
        'invalid_login' => 'Dogry email ýa-da telefon belgisini giriziň.',

        'account_inactive' => 'Hasabyňyz işjeň däl. Administrator bilen habarlaşyň.',
        'wrong_password' => 'Parol nädogry!',
    ],

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */
    'dashboard' => [
        'period_invalid'      => 'Döwür saýlanyşy nädogry.',
        'start_date_invalid'  => 'Başlangyç senesi nädogry.',
        'end_date_invalid'    => 'Soňky senesi nädogry.',
        'end_before_start'    => 'Soňky senesi başlangyç senesinden öň bolup bilmez.',
    ],

    /*
    |--------------------------------------------------------------------------
    | PROFILE
    |--------------------------------------------------------------------------
    */
    'profile' => [
        'firstname_required' => 'Ad meýdany hökmanydyr.',
        'firstname_string' => 'Ad meýdany setir bolmaly.',
        'firstname_max' => 'Ad meýdany :max harpdan köp bolup bilmez.',

        'lastname_required' => 'Familiýa meýdany hökmanydyr.',
        'lastname_string' => 'Familiýa meýdany setir bolmaly.',
        'lastname_max' => 'Familiýa meýdany :max harpdan köp bolup bilmez.',

        'email_required' => 'Email meýdany hökmanydyr.',
        'email_email' => 'Dogry email salgysyny giriziň.',
        'email_max' => 'Email meýdany :max harpdan köp bolup bilmez.',
        'email_unique' => 'Bu email eýýäm ulanylýar.',

        'phonenumber_required' => 'Telefon belgisi hökmanydyr.',
        'phonenumber_string' => 'Telefon belgisi setir bolmaly.',
        'phonenumber_max' => 'Telefon belgisi :max harpdan köp bolup bilmez.',
        'phonenumber_unique' => 'Bu telefon belgisi eýýäm ulanylýar.',

        'old_password_string' => 'Häzirki parol setir bolmaly.',

        'new_password_string' => 'Täze parol setir bolmaly.',
        'new_password_confirmed' => 'Täze parolyň tassyklamasy gabat gelenok.',
        'new_password_min' => 'Täze parol azyndan :min harp bolmaly.',

        'old_password_required_for_change' => 'Paroly üýtgetmek üçin häzirki parolyňyzy giriziň.',
        'old_password_incorrect' => 'Häzirki parolyňyz nädogry.',

        'no_changes' => 'Üýtgeşme tapylmady.',
        'password_updated' => 'Parol üstünlikli täzelendi.',
        'profile_updated' => 'Profil üstünlikli täzelendi.',
    ],

    /*
    |--------------------------------------------------------------------------
    | USERS
    |--------------------------------------------------------------------------
    */
    'users' => [
        'firstname_required' => 'Ad meýdany hökmanydyr.',
        'firstname_string' => 'Ad meýdany setir bolmaly.',
        'firstname_max' => 'Ad meýdany :max harpdan köp bolup bilmez.',

        'lastname_required' => 'Familiýa meýdany hökmanydyr.',
        'lastname_string' => 'Familiýa meýdany setir bolmaly.',
        'lastname_max' => 'Familiýa meýdany :max harpdan köp bolup bilmez.',

        'email_required' => 'Email meýdany hökmanydyr.',
        'email_email' => 'Dogry email salgysyny giriziň.',
        'email_unique' => 'Bu email eýýäm ulanylýar.',

        'phonenumber_required' => 'Telefon belgisi hökmanydyr.',
        'phonenumber_unique' => 'Bu telefon belgisi eýýäm ulanylýar.',

        'password_required' => 'Parol meýdany hökmanydyr.',
        'password_min' => 'Parol azyndan :min harp bolmaly.',

        'role_required' => 'Rol meýdany hökmanydyr.',
        'role_invalid' => 'Nädogry rol saýlandy.',

        'status_required' => 'Status meýdany hökmanydyr.',
        'status_boolean' => 'Status bahasy nädogry.',

        'created' => 'Ulanyjy goşuldy.',
        'updated' => 'Ulanyjy täzelendi.',
        'deleted' => 'Ulanyjy pozuldy.',
    ],

    /*
    |--------------------------------------------------------------------------
    | CUSTOMERS
    |--------------------------------------------------------------------------
    */
    'customers' => [
        'q_string' => 'Gözleg setiri setir bolmaly.',
        'q_max' => 'Gözleg teksti iň köp :max harp bolup biler.',
        'quick_invalid' => 'Nädogry filtr saýlandy.',
    ],

    /*
    |--------------------------------------------------------------------------
    | CUSTOMERS INFO
    |--------------------------------------------------------------------------
    */
    'customers_info' => [
        'q_string' => 'Gözleg setiri setir bolmaly.',
        'q_max' => 'Gözleg teksti iň köp :max harp bolup biler.',
        'id_integer' => 'Saýlanan ýazgy nädogry.',
        'id_min' => 'Saýlanan ýazgy nädogry.',
    ],

    /*
    |--------------------------------------------------------------------------
    | PAYMENTS
    |--------------------------------------------------------------------------
    */
    'payments' => [
        'auth_required' => 'Töleg ýazmak üçin sistema girmeli.',
        'customer_not_selected' => 'Müşderi saýlanmady.',
        'method_invalid' => 'Töleg usuly nädogry.',
        'pay_amount_zero' => 'Pay Amount 0 bolup bilmez.',
        'mixed_negative' => 'Mixed: Cash/Card otrisatel bolup bilmez.',
        'mixed_sum_must_equal' => 'Mixed: Cash + Card jemi Pay Amount bilen deň bolmaly.',
        'invalid_payment_date' => 'Töleg senesi nädogry.',
        'future_payment_date_not_allowed' => 'Geljek seneli töleg rugsat berilmeýär.',
        'payment_save_failed' => 'Töleg ýazdyrylmady.',
        'payment_saved' => 'Töleg üstünlikli ýazdyryldy.',

        'local_debt_missing' => 'Local karz maglumatlary ýetmez (amount_local / paid_local = NULL). Töleg alnyp bilinmez.',
        'debt_closed_paid_ge_total' => 'Bu müşderiniň karzy ýapyk (paid_local >= amount_local). Töleg alnyp bilinmez.',
        'debt_closed_remaining_zero' => 'Bu müşderiniň karzy ýapyk (galyndy 0). Töleg alnyp bilinmez.',
        'overpayment_too_high' => 'Artykmaç töleg örän ýokary. Iň köp gaýtarylýan: :max',
        'mixed_sum_must_equal_tx' => 'Mixed: Cash + Card jemi Pay Amount bilen deň bolmaly.',
    ],

    /*
    |--------------------------------------------------------------------------
    | PAYMENT CORRECT
    |--------------------------------------------------------------------------
    */
    'payment_correct' => [
        'admin_only' => 'Diňe Admin tölegleri düzedip biler.',
        'already_voided' => 'Bu töleg öňünden ýatyrylan (voided).',

        'payment_method_required' => 'Töleg usuly hökmanydyr.',
        'payment_method_invalid' => 'Töleg usuly nädogry.',
        'pay_amount_required' => 'Pay Amount hökmanydyr.',
        'pay_amount_numeric' => 'Pay Amount san bolmaly.',
        'pay_amount_min' => 'Pay Amount azyndan :min bolmaly.',
        'cash_total_numeric' => 'Cash Total san bolmaly.',
        'cash_total_min' => 'Cash Total otrisatel bolup bilmez.',
        'card_total_numeric' => 'Card Total san bolmaly.',
        'card_total_min' => 'Card Total otrisatel bolup bilmez.',
        'note_string' => 'Bellik setir bolmaly.',
        'note_max' => 'Bellik iň köp :max harp bolup biler.',
        'reason_string' => 'Sebäp setir bolmaly.',
        'reason_max' => 'Sebäp iň köp :max harp bolup biler.',
        'payment_at_date' => 'Töleg senesi nädogry.',

        'future_payment_date_not_allowed' => 'Geljek seneli töleg rugsat berilmeýär.',
        'mixed_sum_must_equal' => 'Mixed: Cash + Card jemi Pay Amount bilen deň bolmaly.',

        'local_debt_missing' => 'Local karz maglumatlary ýetmez. Düzediş edip bolmaýar.',
        'debt_closed_use_void_only' => 'Karz eýýäm ýapyk. Düzedişde täze töleg girizip bolmaýar. Diňe VOID ulanyň.',
        'overpayment_too_high' => 'Artykmaç töleg örän ýokary. Iň köp gaýtarylýan: :max',

        'corrected_default_reason' => 'Düzeldildi',

        'correction_failed' => 'Düzediş başartmady.',
        'corrected_success' => 'Töleg üstünlikli düzeldildi.',
    ],

    /*
    |--------------------------------------------------------------------------
    | PAYMENT VOID
    |--------------------------------------------------------------------------
    */
    'payment_void' => [
        'admin_only' => 'Diňe Admin tölegleri ýatyryp biler.',
        'void_reason_required' => 'Ýatyrma sebäbi hökmanydyr.',
        'already_voided' => 'Bu töleg öňünden ýatyrylan (voided).',
        'local_debt_missing' => 'Local karz maglumatlary ýetmez (amount_local/paid_local = NULL).',
        'void_failed' => 'Tölegi ýatyrmak başartmady.',
        'void_success' => 'Töleg üstünlikli ýatyryldy.',
    ],

];
