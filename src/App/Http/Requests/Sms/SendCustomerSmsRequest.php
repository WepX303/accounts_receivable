<?php

namespace App\Http\Requests\Sms;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class SendCustomerSmsRequest extends FormRequest
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

            'schedule_type' => ['required', 'in:now,after_2m,after_5m,after_10m,custom'],
            'scheduled_at' => ['nullable', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $scheduleType = $this->input('schedule_type');
            $scheduledAt = $this->input('scheduled_at');

            if ($scheduleType === 'custom') {
                if (blank($scheduledAt)) {
                    $validator->errors()->add('scheduled_at', __('messages.scheduled_at_required'));
                    return;
                }

                try {
                    $dt = \Carbon\Carbon::parse($scheduledAt);

                    if ($dt->lessThan(now())) {
                        $validator->errors()->add('scheduled_at', __('messages.scheduled_at_must_be_future'));
                    }
                } catch (\Throwable $e) {
                    $validator->errors()->add('scheduled_at', __('messages.invalid_scheduled_at'));
                }
            }
        });
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

            'schedule_type.required' => __('messages.schedule_type_required'),
            'schedule_type.in' => __('messages.invalid_schedule_type'),
            'scheduled_at.date' => __('messages.invalid_scheduled_at'),
        ];
    }
}