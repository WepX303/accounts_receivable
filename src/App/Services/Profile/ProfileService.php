<?php

namespace App\Services\Profile;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Hash;

/**
 * A user changing their own details or password, with the audit log and the
 * alert that go with it. Shared by the panel and the API.
 */
class ProfileService
{
    public const UNCHANGED = 'unchanged';

    public const PASSWORD_UPDATED = 'password_updated';

    public const PROFILE_UPDATED = 'profile_updated';

    public function __construct(private AuditLogger $audit)
    {
    }

    public static function rules(User $user): array
    {
        return [
            'firstname'    => 'required|string|max:255',
            'lastname'     => 'required|string|max:255',
            'email'        => 'required|email|max:255|unique:users,email,' . $user->id,
            'phonenumber'  => 'required|string|max:50|unique:users,phonenumber,' . $user->id,
            'old_password' => 'nullable|string',
            'new_password' => 'nullable|string|confirmed|min:6',
        ];
    }

    public static function messages(): array
    {
        return [
            // firstname
            'firstname.required' => __('validations/validations.profile.firstname_required'),
            'firstname.string'   => __('validations/validations.profile.firstname_string'),
            'firstname.max'      => __('validations/validations.profile.firstname_max'),

            // lastname
            'lastname.required' => __('validations/validations.profile.lastname_required'),
            'lastname.string'   => __('validations/validations.profile.lastname_string'),
            'lastname.max'      => __('validations/validations.profile.lastname_max'),

            // email
            'email.required' => __('validations/validations.profile.email_required'),
            'email.email'    => __('validations/validations.profile.email_email'),
            'email.max'      => __('validations/validations.profile.email_max'),
            'email.unique'   => __('validations/validations.profile.email_unique'),

            // phonenumber
            'phonenumber.required' => __('validations/validations.profile.phonenumber_required'),
            'phonenumber.string'   => __('validations/validations.profile.phonenumber_string'),
            'phonenumber.max'      => __('validations/validations.profile.phonenumber_max'),
            'phonenumber.unique'   => __('validations/validations.profile.phonenumber_unique'),

            // passwords
            'old_password.string' => __('validations/validations.profile.old_password_string'),

            'new_password.string'     => __('validations/validations.profile.new_password_string'),
            'new_password.confirmed'  => __('validations/validations.profile.new_password_confirmed'),
            'new_password.min'        => __('validations/validations.profile.new_password_min'),
        ];
    }

    /**
     * @param  array  $validated  input that passed rules()
     * @return string one of the constants above
     *
     * @throws ProfileException
     */
    public function update(User $user, array $validated): string
    {
        $old = $user->only([
            'firstname',
            'lastname',
            'email',
            'phonenumber',
        ]);

        $passwordChanged = false;
        $otherChanged = false;

        if (! empty($validated['new_password'])) {
            if (empty($validated['old_password'])) {
                throw new ProfileException('old_password', __('validations/validations.profile.old_password_required_for_change'));
            }

            if (! Hash::check($validated['old_password'], $user->password)) {
                throw new ProfileException('old_password', __('validations/validations.profile.old_password_incorrect'));
            }

            $user->password = Hash::make($validated['new_password']);
            $passwordChanged = true;
        }

        foreach (['firstname', 'lastname', 'email', 'phonenumber'] as $field) {
            $newValue = $validated[$field] ?? null;
            if ($newValue !== $user->$field) {
                $user->$field = $newValue;
                $otherChanged = true;
            }
        }

        if (! $passwordChanged && ! $otherChanged) {
            return self::UNCHANGED;
        }

        $user->save();

        $new = $user->only([
            'firstname',
            'lastname',
            'email',
            'phonenumber',
        ]);

        $this->audit->log(
            action: 'profile_updated',
            category: 'profile',
            subject: $user,
            oldValues: $old,
            newValues: $new,
            extra: [
                'password_changed' => $passwordChanged,
            ],
            message: 'Profile updated',
            isSuccess: true,
            severity: $passwordChanged ? 'warning' : 'info',
            isSuspicious: $passwordChanged
        );

        if ($passwordChanged) {
            $this->audit->alert(
                alertType: 'password_changed',
                riskLevel: 'medium',
                message: 'User password changed',
                meta: [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]
            );
        }

        return $passwordChanged && ! $otherChanged ? self::PASSWORD_UPDATED : self::PROFILE_UPDATED;
    }
}
