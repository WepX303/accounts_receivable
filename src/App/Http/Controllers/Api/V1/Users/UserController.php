<?php

namespace App\Http\Controllers\Api\V1\Users;

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Users\UserController as PanelUserController;
use App\Http\Resources\ManagedUserResource;
use App\Models\User;
use App\Support\ApiResponse;
use App\Support\Panel;

/**
 * User management, run by the panel's own controller so that who may create,
 * change or delete whom is decided in one place.
 */
class UserController extends Controller
{
    public function index()
    {
        $data = Panel::call(PanelUserController::class, '__invoke')->getData();

        return ApiResponse::paginated(
            $data['users'],
            ManagedUserResource::collection($data['users']->getCollection())->resolve(),
            [
                // The roles and branches this user may hand out.
                'roles' => array_map(fn (string $role) => [
                    'value' => $role,
                    'label' => UserRoleEnum::from($role)->label(),
                ], $data['roles']),
                'branches' => collect($data['branches'])->values()->all(),
            ]
        );
    }

    public function store()
    {
        $before = User::max('id');

        return $this->answer(
            Panel::call(PanelUserController::class, 'store'),
            fn () => User::where('id', '>', (int) $before)->latest('id')->first(),
            201
        );
    }

    public function update(User $user)
    {
        return $this->answer(Panel::call(PanelUserController::class, 'update', ['user' => $user]), fn () => $user->fresh());
    }

    public function destroy(User $user)
    {
        return $this->answer(Panel::call(PanelUserController::class, 'destroy', ['user' => $user]), fn () => null);
    }

    private function answer($response, \Closure $user, int $status = 200)
    {
        if ($refusal = Panel::refusal($response)) {
            return $refusal;
        }

        $user = $user();

        return ApiResponse::success(
            $user ? ['user' => (new ManagedUserResource($user))->resolve()] : null,
            Panel::flashed($response, 'success'),
            $status
        );
    }
}
