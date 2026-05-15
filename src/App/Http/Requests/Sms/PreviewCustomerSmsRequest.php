<?php

namespace App\Http\Requests\Sms;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class PreviewCustomerSmsRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user?->hasRole(
            UserRoleEnum::SUPER_ADMIN,
            UserRoleEnum::ADMIN,
            UserRoleEnum::OPERATOR
        ) ?? false;
    }

    public function rules(): array
    {
        return [
            'selected_customers' => ['required', 'array', 'min:1'],
            'selected_customers.*' => ['integer', 'distinct'],
            'message' => ['required', 'string', 'max:1000'],
            'schedule_type' => ['nullable', 'in:now,after_2m,after_5m,after_10m,custom'],
            'scheduled_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'selected_customers.required' => __('messages.select_at_least_one_customer'),
            'selected_customers.array' => __('messages.invalid_selection'),
            'selected_customers.min' => __('messages.select_at_least_one_customer'),
            'selected_customers.*.integer' => __('messages.invalid_selection'),
            'selected_customers.*.distinct' => __('messages.invalid_selection'),
            'message.required' => __('messages.enter_sms_message'),
            'message.max' => __('messages.sms_message_too_long'),
            'schedule_type.in' => __('messages.invalid_schedule_type'),
            'scheduled_at.date' => __('messages.invalid_scheduled_at'),
        ];
    }
}