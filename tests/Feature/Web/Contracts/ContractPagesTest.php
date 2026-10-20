<?php

namespace Tests\Feature\Web\Contracts;

use App\Enums\UserRoleEnum;
use App\Models\CustomerContract;
use App\Models\User;
use Database\Factories\CreditFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContractPagesTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(UserRoleEnum $role, ?array $branches = null): User
    {
        $user = UserFactory::new()->role($role)->create([
            'branches' => $branches,
            'token' => Str::random(64),
            'token_expires_at' => now()->addDay(),
        ]);

        $this->withCookie('auth_token', $user->token);

        return $user;
    }

    private function form(array $overrides = []): array
    {
        return $overrides + [
            'branch' => 'Mary',
            'contract_date' => '2026-10-21',
            'customer_name' => 'Aman Amanow',
            'passport_series' => 'I-AŞ',
            'passport_number' => '350924',
            'address' => 'Aşgabat',
            'phone' => '+99365000000',
            'months' => '6',
            'monthly_amount' => '250',
            'installment_part' => '5/6',
            'goods' => [
                ['name' => 'IMEI 868468451649', 'quantity' => '1', 'price' => '1 500', 'total' => ''],
                ['name' => '', 'quantity' => '', 'price' => '', 'total' => ''],
            ],
        ];
    }

    private function draft(User $by, array $overrides = []): CustomerContract
    {
        $this->post('/contracts', $this->form($overrides))->assertRedirect();

        return CustomerContract::withoutGlobalScopes()->latest('id')->firstOrFail();
    }

    public function test_the_menu_and_the_list_are_there_for_the_roles_that_make_contracts(): void
    {
        foreach ([UserRoleEnum::SUPER_ADMIN, UserRoleEnum::ADMIN, UserRoleEnum::CASHIER, UserRoleEnum::OPERATOR] as $role) {
            $this->signIn($role);
            $this->get('/contracts')->assertOk()->assertSee(__('pages/contracts.title'));
            $this->get('/')->assertSee(route('contracts.index'));
        }

        foreach ([UserRoleEnum::ANALYST, UserRoleEnum::MANAGER, UserRoleEnum::USER] as $role) {
            $this->signIn($role);
            $this->get('/contracts')->assertForbidden();
            $this->post('/contracts/blank', ['branch' => 'Mary'])->assertForbidden();
        }
    }

    public function test_choosing_a_customer_fills_the_form(): void
    {
        $this->signIn(UserRoleEnum::CASHIER, ['Mary']);
        $credit = CreditFactory::new()->create([
            'branch' => 'Mary', 'name' => 'Aman Amanow', 'passport' => 'I-AŞ 350924', 'assurance' => 'Aşgabat, jaý 5',
        ]);

        $this->get('/contracts/create?q=Aman')->assertOk()->assertSee('Aman Amanow');

        $this->get("/contracts/create?q=Aman&credit={$credit->source_id}")
            ->assertOk()
            ->assertSee('value="I-AŞ"', false)
            ->assertSee('value="350924"', false)
            ->assertSee('Aşgabat, jaý 5')
            ->assertSee($credit->contract);
    }

    public function test_a_customer_of_another_branch_cannot_be_chosen(): void
    {
        $this->signIn(UserRoleEnum::CASHIER, ['Mary']);
        $credit = CreditFactory::new()->create(['branch' => 'Lebap', 'name' => 'Other Customer']);

        $this->get('/contracts/create?q=Other')->assertOk()->assertDontSee('Other Customer');
        $this->get("/contracts/create?credit={$credit->source_id}")->assertNotFound();
    }

    public function test_a_contract_is_saved_as_a_draft_and_shown(): void
    {
        $user = $this->signIn(UserRoleEnum::CASHIER);

        $response = $this->post('/contracts', $this->form());

        $contract = CustomerContract::sole();
        $response->assertRedirect(route('contracts.show', $contract));

        $this->assertTrue($contract->isDraft());
        $this->assertSame(250.0, (float) $contract->monthly_amount);
        // Empty goods rows are dropped; amounts typed with spaces are kept plainly.
        $this->assertSame([['name' => 'IMEI 868468451649', 'quantity' => '1', 'price' => '1500.00', 'total' => null]], $contract->goods);
        $this->assertSame($user->id, $contract->created_by);

        $this->get(route('contracts.show', $contract))
            ->assertOk()
            ->assertSee($contract->number)
            ->assertSee(__('pages/contracts.actions.contract_created'));
    }

    public function test_a_contract_can_be_filled_by_hand_without_making_a_customer(): void
    {
        $this->signIn(UserRoleEnum::CASHIER);

        $this->get('/contracts/create?manual=1')->assertOk()->assertSee(__('pages/contracts.manual_hint'));

        $this->post('/contracts', $this->form(['customer_name' => 'New Person']))->assertRedirect();

        $this->assertNull(CustomerContract::sole()->credit_source_id);
        $this->assertDatabaseCount('credits', 0);
    }

    public function test_the_form_is_checked(): void
    {
        $this->signIn(UserRoleEnum::CASHIER, ['Mary']);

        $this->from('/contracts/create?manual=1')
            ->post('/contracts', $this->form(['customer_name' => '', 'months' => '500', 'monthly_amount' => 'abc']))
            ->assertRedirect('/contracts/create?manual=1')
            ->assertSessionHasErrors(['customer_name', 'months', 'monthly_amount']);

        $this->from('/contracts/create?manual=1')
            ->post('/contracts', $this->form(['branch' => 'Lebap']))
            ->assertSessionHas('warning', __('validations/validations.contracts.branch_not_allowed'));

        $this->assertDatabaseCount('customer_contracts', 0);
    }

    public function test_a_draft_is_changed_and_the_change_shows_in_its_history(): void
    {
        $user = $this->signIn(UserRoleEnum::CASHIER);
        $contract = $this->draft($user);

        $this->put(route('contracts.update', $contract), $this->form(['address' => 'Mary şäheri']))
            ->assertRedirect(route('contracts.show', $contract))
            ->assertSessionHas('success');

        $this->assertSame('Mary şäheri', $contract->fresh()->address);

        $this->get(route('contracts.show', $contract))
            ->assertSee(__('pages/contracts.actions.contract_updated'))
            ->assertSee('Mary şäheri');

        $this->put(route('contracts.update', $contract), $this->form(['address' => 'Mary şäheri']))
            ->assertSessionHas('info', __('pages/contracts.nothing_changed'));
    }

    public function test_an_operator_approves_and_the_contract_locks(): void
    {
        $cashier = $this->signIn(UserRoleEnum::CASHIER);
        $contract = $this->draft($cashier);

        $this->post(route('contracts.approve', $contract))->assertForbidden();

        $this->signIn(UserRoleEnum::OPERATOR);
        $this->get(route('contracts.show', $contract))->assertSee(__('pages/contracts.approve'));
        $this->post(route('contracts.approve', $contract))->assertSessionHas('success');

        $this->assertTrue($contract->fresh()->isApproved());

        $this->get(route('contracts.show', $contract))
            ->assertOk()
            ->assertSee(__('pages/contracts.locked'))
            ->assertDontSee('name="_method" value="PUT"', false)
            ->assertDontSee(__('pages/contracts.approve_confirm'));

        $this->put(route('contracts.update', $contract), $this->form(['address' => 'x']))
            ->assertSessionHas('warning', __('validations/validations.contracts.not_draft'));
        $this->assertSame('Aşgabat', $contract->fresh()->address);
    }

    public function test_an_admin_cancels_with_a_reason(): void
    {
        $contract = $this->draft($this->signIn(UserRoleEnum::CASHIER));

        $this->signIn(UserRoleEnum::OPERATOR);
        $this->post(route('contracts.cancel', $contract), ['cancel_reason' => 'x'])->assertForbidden();

        $this->signIn(UserRoleEnum::ADMIN);
        $this->post(route('contracts.cancel', $contract), ['cancel_reason' => ''])
            ->assertSessionHas('warning', __('validations/validations.contracts.cancel_reason_required'));

        $this->post(route('contracts.cancel', $contract), ['cancel_reason' => 'Typed twice'])->assertSessionHas('success');

        $this->get(route('contracts.show', $contract))->assertSee('Typed twice');
        $this->assertTrue($contract->fresh()->isCancelled());
    }

    public function test_printing_opens_the_pdf_and_counts_it(): void
    {
        $contract = $this->draft($this->signIn(UserRoleEnum::CASHIER));

        $response = $this->get(route('contracts.print', $contract));

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString($contract->number . '.pdf', $response->headers->get('Content-Disposition'));
        $this->assertSame(1, $contract->fresh()->print_count);
    }

    public function test_a_blank_form_takes_the_next_number_and_can_be_filled_in_later(): void
    {
        $this->signIn(UserRoleEnum::CASHIER, ['Mary']);
        $this->draft(UserFactory::new()->make(), []);

        $blank = $this->post(route('contracts.blank'), ['branch' => 'Mary']);

        $blank->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $issued = CustomerContract::latest('id')->firstOrFail();
        $this->assertTrue($issued->isIssued());
        $this->assertSame('UT-' . now()->year . '-0000002', $issued->number);
        $this->assertStringContainsString($issued->number . '.pdf', $blank->headers->get('Content-Disposition'));
        $this->assertSame(1, $issued->print_count);

        $this->post(route('contracts.blank'), ['branch' => 'Lebap'])
            ->assertSessionHas('warning', __('validations/validations.contracts.branch_not_allowed'));
        $this->assertSame(2, CustomerContract::count());

        $this->get('/contracts?status=issued')->assertOk()->assertSee($issued->number);
        $this->get(route('contracts.show', $issued))
            ->assertOk()
            ->assertSee(__('pages/contracts.issued_hint'))
            ->assertSee('name="_method" value="PUT"', false);

        // The details from the paper are entered later; it becomes a draft to approve.
        $this->put(route('contracts.update', $issued), $this->form(['customer_name' => 'From Paper']))->assertSessionHas('success');
        $fresh = $issued->fresh();
        $this->assertTrue($fresh->isDraft());
        $this->assertSame('From Paper', $fresh->customer_name);
        $this->assertSame($issued->number, $fresh->number);
    }

    public function test_a_contract_of_another_branch_cannot_be_opened(): void
    {
        $contract = $this->draft($this->signIn(UserRoleEnum::ADMIN), ['branch' => 'Lebap', 'customer_name' => 'Other Customer']);

        $this->signIn(UserRoleEnum::CASHIER, ['Mary']);

        $this->get('/contracts')->assertOk()->assertDontSee('Other Customer');
        $this->get(route('contracts.show', $contract))->assertNotFound();
        $this->get(route('contracts.print', $contract))->assertNotFound();
        $this->put(route('contracts.update', $contract), $this->form())->assertNotFound();
    }
}
