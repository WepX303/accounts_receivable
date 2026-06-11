<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AUTH
    |--------------------------------------------------------------------------
    */

    'auth' => [
        'login_required' => 'Login field is required.',
        'password_required' => 'Password field is required.',

        'email_not_found' => 'No account found with this email address.',
        'phone_not_found' => 'No account found with this phone number.',
        'invalid_login' => 'Please enter a valid email or phone number.',

        'account_inactive' => 'Your account is inactive. Please contact the administrator.',
        'wrong_password' => 'Incorrect password!',
    ],

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */
    'dashboard' => [
        'period_invalid'      => 'Invalid period selection.',
        'start_date_invalid'  => 'Start date is invalid.',
        'end_date_invalid'    => 'End date is invalid.',
        'end_before_start'    => 'End date cannot be before start date.',
    ],

    /*
    |--------------------------------------------------------------------------
    | PROFILE
    |--------------------------------------------------------------------------
    */
    'profile' => [
        'firstname_required' => 'First name is required.',
        'firstname_string' => 'First name must be a string.',
        'firstname_max' => 'First name may not be greater than :max characters.',

        'lastname_required' => 'Last name is required.',
        'lastname_string' => 'Last name must be a string.',
        'lastname_max' => 'Last name may not be greater than :max characters.',

        'email_required' => 'Email is required.',
        'email_email' => 'Please enter a valid email address.',
        'email_max' => 'Email may not be greater than :max characters.',
        'email_unique' => 'This email address is already in use.',

        'phonenumber_required' => 'Phone number is required.',
        'phonenumber_string' => 'Phone number must be a string.',
        'phonenumber_max' => 'Phone number may not be greater than :max characters.',
        'phonenumber_unique' => 'This phone number is already in use.',

        'old_password_string' => 'Current password must be a string.',

        'new_password_string' => 'New password must be a string.',
        'new_password_confirmed' => 'New password confirmation does not match.',
        'new_password_min' => 'New password must be at least :min characters.',

        'old_password_required_for_change' => 'Please enter your current password to change your password.',
        'old_password_incorrect' => 'Your current password is incorrect.',

        'no_changes' => 'No changes detected.',
        'password_updated' => 'Password updated successfully.',
        'profile_updated' => 'Profile updated successfully.',
    ],

    /*
    |--------------------------------------------------------------------------
    | USERS
    |--------------------------------------------------------------------------
    */
    'users' => [
        'firstname_required' => 'First name is required.',
        'firstname_string' => 'First name must be a string.',
        'firstname_max' => 'First name may not be greater than :max characters.',

        'lastname_required' => 'Last name is required.',
        'lastname_string' => 'Last name must be a string.',
        'lastname_max' => 'Last name may not be greater than :max characters.',

        'email_required' => 'Email is required.',
        'email_email' => 'Please enter a valid email address.',
        'email_unique' => 'This email address is already in use.',

        'phonenumber_required' => 'Phone number is required.',
        'phonenumber_unique' => 'This phone number is already in use.',

        'password_required' => 'Password is required.',
        'password_min' => 'Password must be at least :min characters.',

        'role_required' => 'Role is required.',
        'role_invalid' => 'Invalid role selected.',

        'status_required' => 'Status is required.',
        'status_boolean' => 'Invalid status value.',

        'created' => 'User created successfully.',
        'updated' => 'User updated successfully.',
        'deleted' => 'User deleted successfully.',
    ],

    /*
    |--------------------------------------------------------------------------
    | CUSTOMERS
    |--------------------------------------------------------------------------
    */
    'customers' => [
        'q_string' => 'Search query must be a string.',
        'q_max' => 'Search query may not be greater than :max characters.',
        'quick_invalid' => 'Invalid filter selection.',
    ],

    /*
    |--------------------------------------------------------------------------
    | CUSTOMERS INFO
    |--------------------------------------------------------------------------
    */
    'customers_info' => [
        'q_string' => 'Search query must be a string.',
        'q_max' => 'Search query may not be greater than :max characters.',
        'id_integer' => 'Selected record is invalid.',
        'id_min' => 'Selected record is invalid.',
    ],

    /*
    |--------------------------------------------------------------------------
    | PAYMENTS
    |--------------------------------------------------------------------------
    */
    'payments' => [
        'auth_required' => 'You must be logged in to save a payment.',
        'customer_not_selected' => 'Customer not selected.',
        'method_invalid' => 'Invalid payment method.',
        'pay_amount_zero' => 'Pay Amount cannot be 0.',
        'mixed_negative' => 'Mixed: Cash/Card cannot be negative.',
        'mixed_sum_must_equal' => 'Mixed: Cash + Card total must equal Pay Amount.',
        'invalid_payment_date' => 'Invalid payment date.',
        'future_payment_date_not_allowed' => 'Future payment date is not allowed.',
        'payment_save_failed' => 'Payment could not be saved.',
        'payment_saved' => 'Payment saved successfully.',

        'local_debt_missing' => 'Local debt data is missing (amount_local / paid_local is NULL). Payment cannot be accepted.',
        'debt_closed_paid_ge_total' => 'This customer’s debt is already closed (paid_local >= amount_local). Payment cannot be accepted.',
        'debt_closed_remaining_zero' => 'This customer’s debt is already closed (remaining is 0). Payment cannot be accepted.',
        'overpayment_too_high' => 'Overpayment is too high. Maximum change allowed: :max',
        'mixed_sum_must_equal_tx' => 'Mixed: Cash + Card total must equal Pay Amount.',
    ],

    /*
    |--------------------------------------------------------------------------
    | PAYMENT CORRECT
    |--------------------------------------------------------------------------
    */
    'payment_correct' => [
        'admin_only' => 'Only Admin can correct payments.',
        'already_voided' => 'This payment is already voided.',

        'payment_method_required' => 'Payment method is required.',
        'payment_method_invalid' => 'Invalid payment method.',
        'pay_amount_required' => 'Pay Amount is required.',
        'pay_amount_numeric' => 'Pay Amount must be a number.',
        'pay_amount_min' => 'Pay Amount must be at least :min.',
        'cash_total_numeric' => 'Cash Total must be a number.',
        'cash_total_min' => 'Cash Total cannot be negative.',
        'card_total_numeric' => 'Card Total must be a number.',
        'card_total_min' => 'Card Total cannot be negative.',
        'note_string' => 'Note must be a string.',
        'note_max' => 'Note may not be greater than :max characters.',
        'reason_string' => 'Reason must be a string.',
        'reason_max' => 'Reason may not be greater than :max characters.',
        'payment_at_date' => 'Payment date is invalid.',

        'future_payment_date_not_allowed' => 'Future payment date is not allowed.',
        'mixed_sum_must_equal' => 'Mixed: Cash + Card must equal Pay Amount.',

        'local_debt_missing' => 'Local debt data missing. Cannot correct.',
        'debt_closed_use_void_only' => 'Debt is already closed. You cannot enter a new received amount in correct. Use VOID only.',
        'overpayment_too_high' => 'Overpayment is too high. Max change allowed: :max',

        'corrected_default_reason' => 'Corrected',

        'correction_failed' => 'Correction failed.',
        'corrected_success' => 'Payment corrected successfully.',
    ],

    /*
    |--------------------------------------------------------------------------
    | PAYMENT VOID
    |--------------------------------------------------------------------------
    */
    'payment_void' => [
        'admin_only' => 'Only Admin can void payments.',
        'void_reason_required' => 'Void reason is required.',
        'already_voided' => 'This payment is already voided.',
        'local_debt_missing' => 'Local debt data missing (amount_local/paid_local NULL).',
        'void_failed' => 'Payment void failed.',
        'void_success' => 'Payment voided successfully.',
    ],

];
