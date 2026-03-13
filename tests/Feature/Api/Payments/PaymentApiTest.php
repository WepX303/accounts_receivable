<?php

namespace Tests\Feature\Api\Payments;

use App\Enums\UserRoleEnum;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithToken(string $role = 'Admin', string $email = 'api-payment@example.com', string $phone = '99360000200'): User
    {
        return User::create([
            'firstname' => 'Api',
            'lastname' => 'Payment',
            'email' => $email,
            'phonenumber' => $phone,
            'position' => 'Tester',
            'role' => $role,
            'status' => true,
            'password' => Hash::make('secret123'),
            'token' => bin2hex(random_bytes(32)),
            'token_expires_at' => now()->addDay(),
        ]);
    }

    private function createCredit(array $overrides = []): Credit
    {
        return Credit::create(array_merge([
            'logicalref' => random_int(1000, 999999),
            'branch' => 'FLO',
            'name' => 'Payment Customer',
            'passport' => 'I-AS 537751',
            'phone' => '864316058 865018019',
            'contract' => '0652',
            'date_' => now(),
            'amount' => 1000.00,
            'paid' => 0.00,
            'amount_local' => 1000.00,
            'paid_local' => 0.00,
            'status' => 'WAGTLAYYN',
            'active' => true,
            'clientref' => '120.TMT.10001029',
            'custstatus' => 'MÜŞDERI',
            'assurance' => '',
            'fishno' => 'None',
            'confirmedby' => 'None',
            'rv_bigint' => random_int(1, 999999),
        ], $overrides));
    }

    private function createPayment(Credit $credit, User $user, array $overrides = []): CreditPayment
    {
        return CreditPayment::create(array_merge([
            'credit_logicalref' => (int) $credit->logicalref,
            'customer_name' => $credit->name,
            'customer_phone' => $credit->phone,
            'customer_passport' => $credit->passport,
            'customer_contract' => $credit->contract,
            'branch' => $credit->branch,
            'created_by' => $user->id,
            'created_by_name' => $user->full_name,
            'created_by_email' => $user->email,
            'created_by_phone' => $user->phonenumber,
            'pay_amount' => '100.00',
            'change_amount' => '0.00',
            'method' => 'cash',
            'cash_amount' => '100.00',
            'card_amount' => '0.00',
            'phone_amount' => '0.00',
            'old_amount_local' => '1000.00',
            'new_amount_local' => '900.00',
            'old_paid_local' => '0.00',
            'new_paid_local' => '100.00',
            'note' => null,
            'created_at' => now(),
        ], $overrides));
    }

    public function test_authenticated_user_can_create_cash_payment(): void
    {
        $user = $this->createUserWithToken(UserRoleEnum::ADMIN->value);
        $credit = $this->createCredit([
            'logicalref' => 5001,
            'amount_local' => 1000.00,
            'paid_local' => 100.00,
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $user->token)
            ->postJson('/api/v1/payments', [
                'customer_id' => $credit->logicalref,
                'payment_method' => 'cash',
                'pay_amount' => 150,
                'note' => 'cash payment',
            ]);

        $response
            ->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Payment created successfully.',
            ])
            ->assertJsonPath('data.credit_logicalref', $credit->logicalref)
            ->assertJsonPath('data.method', 'cash')
            ->assertJsonPath('data.pay_amount', '150.00')
            ->assertJsonPath('data.change_amount', '0.00');

        $credit->refresh();

        $this->assertEquals('250.00', $credit->paid_local);
        $this->assertDatabaseHas('credit_payments', [
            'credit_logicalref' => $credit->logicalref,
            'method' => 'cash',
            'pay_amount' => '150.00',
        ]);
    }

    public function test_authenticated_user_can_create_phone_payment(): void
    {
        $user = $this->createUserWithToken(UserRoleEnum::CASHIER->value, 'cashier@example.com', '99360000201');
        $credit = $this->createCredit([
            'logicalref' => 5002,
            'amount_local' => 500.00,
            'paid_local' => 0.00,
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $user->token)
            ->postJson('/api/v1/payments', [
                'customer_id' => $credit->logicalref,
                'payment_method' => 'phone',
                'pay_amount' => 50,
            ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('data.method', 'phone')
            ->assertJsonPath('data.phone_amount', '50.00')
            ->assertJsonPath('data.cash_amount', '0.00')
            ->assertJsonPath('data.card_amount', '0.00');
    }

    public function test_authenticated_user_can_create_mixed_payment(): void
    {
        $user = $this->createUserWithToken(UserRoleEnum::ADMIN->value, 'mixed@example.com', '99360000202');
        $credit = $this->createCredit([
            'logicalref' => 5003,
            'amount_local' => 700.00,
            'paid_local' => 0.00,
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $user->token)
            ->postJson('/api/v1/payments', [
                'customer_id' => $credit->logicalref,
                'payment_method' => 'mixed',
                'pay_amount' => 100,
                'cash_total' => 60,
                'card_total' => 40,
            ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('data.method', 'mixed')
            ->assertJsonPath('data.cash_amount', '60.00')
            ->assertJsonPath('data.card_amount', '40.00');
    }

    public function test_payment_create_fails_when_mixed_total_is_invalid(): void
    {
        $user = $this->createUserWithToken(UserRoleEnum::ADMIN->value, 'invalidmixed@example.com', '99360000203');
        $credit = $this->createCredit([
            'logicalref' => 5004,
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $user->token)
            ->postJson('/api/v1/payments', [
                'customer_id' => $credit->logicalref,
                'payment_method' => 'mixed',
                'pay_amount' => 100,
                'cash_total' => 20,
                'card_total' => 30,
            ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'MIXED_TOTAL_INVALID',
            ]);
    }

    public function test_payment_create_fails_when_debt_is_already_closed(): void
    {
        $user = $this->createUserWithToken(UserRoleEnum::ADMIN->value, 'closed@example.com', '99360000204');
        $credit = $this->createCredit([
            'logicalref' => 5005,
            'amount_local' => 100.00,
            'paid_local' => 100.00,
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $user->token)
            ->postJson('/api/v1/payments', [
                'customer_id' => $credit->logicalref,
                'payment_method' => 'cash',
                'pay_amount' => 10,
            ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'PAYMENT_CREATE_FAILED',
            ]);
    }

    public function test_authenticated_user_can_get_customer_payment_history(): void
    {
        $user = $this->createUserWithToken(UserRoleEnum::ADMIN->value, 'history@example.com', '99360000205');
        $credit = $this->createCredit([
            'logicalref' => 5006,
        ]);

        $this->createPayment($credit, $user, [
            'pay_amount' => '50.00',
            'cash_amount' => '50.00',
        ]);

        $this->createPayment($credit, $user, [
            'pay_amount' => '75.00',
            'cash_amount' => '75.00',
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $user->token)
            ->getJson('/api/v1/customers/' . $credit->logicalref . '/payments');

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'credit_logicalref',
                        'customer_name',
                        'customer_phone',
                        'customer_passport',
                        'customer_contract',
                        'branch',
                        'created_by',
                        'created_by_name',
                        'created_by_email',
                        'created_by_phone',
                        'pay_amount',
                        'change_amount',
                        'applied_amount',
                        'method',
                        'cash_amount',
                        'card_amount',
                        'phone_amount',
                        'old_amount_local',
                        'new_amount_local',
                        'old_paid_local',
                        'new_paid_local',
                        'note',
                        'created_at',
                        'is_voided',
                        'voided_at',
                        'void_reason',
                        'corrected_at',
                        'correct_reason',
                        'corrected_from_payment_id',
                    ],
                ],
            ]);
    }

    public function test_admin_can_void_payment(): void
    {
        $admin = $this->createUserWithToken(UserRoleEnum::ADMIN->value, 'voidadmin@example.com', '99360000206');
        $credit = $this->createCredit([
            'logicalref' => 5007,
            'amount_local' => 1000.00,
            'paid_local' => 100.00,
        ]);

        $payment = $this->createPayment($credit, $admin, [
            'id' => 7001,
            'pay_amount' => '100.00',
            'change_amount' => '0.00',
            'old_paid_local' => '0.00',
            'new_paid_local' => '100.00',
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $admin->token)
            ->postJson('/api/v1/payments/' . $payment->id . '/void', [
                'void_reason' => 'wrong entry',
            ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Payment voided successfully.',
            ]);

        $payment->refresh();
        $credit->refresh();

        $this->assertNotNull($payment->voided_at);
        $this->assertEquals('0.00', $credit->paid_local);
    }

    public function test_non_admin_cannot_void_payment(): void
    {
        $cashier = $this->createUserWithToken(UserRoleEnum::CASHIER->value, 'cashiervoid@example.com', '99360000207');
        $credit = $this->createCredit([
            'logicalref' => 5008,
            'amount_local' => 1000.00,
            'paid_local' => 100.00,
        ]);

        $payment = $this->createPayment($credit, $cashier, [
            'id' => 7002,
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $cashier->token)
            ->postJson('/api/v1/payments/' . $payment->id . '/void', [
                'void_reason' => 'no permission',
            ]);

        $response
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error_code' => 'ROLE_FORBIDDEN',
            ]);
    }

    public function test_admin_can_correct_payment(): void
    {
        $admin = $this->createUserWithToken(UserRoleEnum::ADMIN->value, 'correctadmin@example.com', '99360000208');
        $credit = $this->createCredit([
            'logicalref' => 5009,
            'amount_local' => 1000.00,
            'paid_local' => 100.00,
        ]);

        $payment = $this->createPayment($credit, $admin, [
            'id' => 7003,
            'pay_amount' => '100.00',
            'change_amount' => '0.00',
            'old_paid_local' => '0.00',
            'new_paid_local' => '100.00',
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $admin->token)
            ->postJson('/api/v1/payments/' . $payment->id . '/correct', [
                'payment_method' => 'cash',
                'pay_amount' => 80,
                'reason' => 'amount correction',
            ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Payment corrected successfully.',
            ])
            ->assertJsonPath('data.pay_amount', '80.00');

        $payment->refresh();
        $credit->refresh();

        $this->assertNotNull($payment->voided_at);
        $this->assertEquals('80.00', $credit->paid_local);
        $this->assertDatabaseHas('credit_payments', [
            'corrected_from_payment_id' => $payment->id,
            'pay_amount' => '80.00',
        ]);
    }
}