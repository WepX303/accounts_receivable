<?php

namespace Tests\Feature\Web;

use App\Enums\UserRoleEnum;
use Database\Factories\CreditFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchRestrictionTest extends TestCase
{
    use RefreshDatabase;

    public function test_statement_of_a_credit_from_another_branch_cannot_be_opened_by_its_address(): void
    {
        $own = CreditFactory::new()->create(['branch' => 'Mary', 'name' => 'Own Customer']);
        $other = CreditFactory::new()->create(['branch' => 'Lebap', 'name' => 'Other Customer', 'passport' => 'I-AS999999']);

        $analyst = UserFactory::new()->role(UserRoleEnum::ANALYST)->create([
            'branches' => ['Mary'],
            'token' => str_repeat('a', 64),
            'token_expires_at' => now()->addDay(),
        ]);

        $this->withCookie('auth_token', $analyst->token);

        $this->get("/payments/customer/{$own->source_id}/statement")->assertOk()->assertSee('Own Customer');

        $this->get("/payments/customer/{$other->source_id}/statement")
            ->assertNotFound()
            ->assertDontSee('Other Customer')
            ->assertDontSee('I-AS999999');
    }

    public function test_unrestricted_user_and_super_admin_still_open_every_branch(): void
    {
        $credit = CreditFactory::new()->create(['branch' => 'Lebap', 'name' => 'Other Customer']);

        foreach ([[UserRoleEnum::ANALYST, null], [UserRoleEnum::SUPER_ADMIN, ['Mary']]] as $i => [$role, $branches]) {
            $user = UserFactory::new()->role($role)->create([
                'branches' => $branches,
                'token' => str_repeat((string) $i, 64),
                'token_expires_at' => now()->addDay(),
            ]);

            $this->withCookie('auth_token', $user->token)
                ->get("/payments/customer/{$credit->source_id}/statement")
                ->assertOk()
                ->assertSee('Other Customer');
        }
    }

    public function test_guest_is_still_sent_to_login_and_wrong_role_is_still_forbidden(): void
    {
        $credit = CreditFactory::new()->create();

        $this->get("/payments/customer/{$credit->source_id}/statement")->assertRedirect(route('login'));
        $this->get('/payments/customer/999999999/statement')->assertRedirect(route('login'));

        $cashier = UserFactory::new()->role(UserRoleEnum::CASHIER)->create([
            'token' => str_repeat('c', 64),
            'token_expires_at' => now()->addDay(),
        ]);

        $this->withCookie('auth_token', $cashier->token)
            ->get("/payments/customer/{$credit->source_id}/statement")
            ->assertForbidden();
    }
}
