<?php

namespace Tests\Feature\Api\Customers;

use App\Enums\UserRoleEnum;
use App\Models\Credit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithToken(string $role = 'Admin'): User
    {
        return User::create([
            'firstname' => 'Api',
            'lastname' => 'User',
            'email' => 'apiuser@example.com',
            'phonenumber' => '99360000100',
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
            'name' => 'Nurmyradowa Aybibi Babagulyyewna',
            'passport' => 'I-AS 537751',
            'phone' => '864316058 865018019',
            'contract' => '0652',
            'date_' => now(),
            'amount' => 1006.00,
            'paid' => 1006.35,
            'amount_local' => 1006.00,
            'paid_local' => 1006.35,
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

    public function test_authenticated_user_can_get_customer_list(): void
    {
        $user = $this->createUserWithToken(UserRoleEnum::ADMIN->value);

        $this->createCredit([
            'logicalref' => 101,
            'name' => 'Customer One',
            'contract' => 'C-001',
            'phone' => '99361111111',
        ]);

        $this->createCredit([
            'logicalref' => 102,
            'name' => 'Customer Two',
            'contract' => 'C-002',
            'phone' => '99362222222',
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $user->token)
            ->getJson('/api/v1/customers');

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'logicalref',
                        'branch',
                        'name',
                        'passport',
                        'phone',
                        'contract',
                        'status',
                        'active',
                        'amount',
                        'paid',
                        'amount_local',
                        'paid_local',
                        'local_remaining',
                        'local_closed',
                        'remote_remaining',
                        'remote_closed',
                        'rv_bigint',
                    ],
                ],
                'meta' => [
                    'pagination' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total',
                        'from',
                        'to',
                        'has_more_pages',
                    ],
                ],
            ]);
    }

    public function test_customer_list_can_be_filtered_by_query(): void
    {
        $user = $this->createUserWithToken(UserRoleEnum::ADMIN->value);

        $this->createCredit([
            'logicalref' => 201,
            'name' => 'Unique Customer Name',
            'contract' => 'AAA-001',
        ]);

        $this->createCredit([
            'logicalref' => 202,
            'name' => 'Another Person',
            'contract' => 'BBB-002',
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $user->token)
            ->getJson('/api/v1/customers?q=Unique');

        $response
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Unique Customer Name');
    }

    public function test_authenticated_user_can_search_customers(): void
    {
        $user = $this->createUserWithToken(UserRoleEnum::ADMIN->value);

        $this->createCredit([
            'logicalref' => 301,
            'name' => 'Searchable Customer',
            'contract' => 'SEARCH-001',
        ]);

        $this->createCredit([
            'logicalref' => 302,
            'name' => 'Other Customer',
            'contract' => 'OTHER-002',
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $user->token)
            ->getJson('/api/v1/customers/search?q=Searchable&limit=10');

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Searchable Customer');
    }

    public function test_authenticated_user_can_get_customer_detail(): void
    {
        $user = $this->createUserWithToken(UserRoleEnum::ADMIN->value);

        $credit = $this->createCredit([
            'logicalref' => 401,
            'name' => 'Detail Customer',
            'contract' => 'DETAIL-001',
            'phone' => '99363333333',
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $user->token)
            ->getJson('/api/v1/customers/' . $credit->logicalref);

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'logicalref',
                    'branch',
                    'name',
                    'passport',
                    'phone',
                    'contract',
                    'date_',
                    'amount',
                    'paid',
                    'amount_local',
                    'paid_local',
                    'local_remaining',
                    'local_closed',
                    'remote_remaining',
                    'remote_closed',
                    'willpaiddate',
                    'willpaidamount',
                    'note',
                    'lastnoteddate',
                    'status',
                    'active',
                    'clientref',
                    'custstatus',
                    'assurance',
                    'ctype',
                    'cardno',
                    'fishno',
                    'manager',
                    'confirmedby',
                    'gstatus',
                    'rv_bigint',
                    'paid_updated_at',
                    'amount_updated_at',
                ],
            ])
            ->assertJsonPath('data.logicalref', $credit->logicalref)
            ->assertJsonPath('data.name', 'Detail Customer');
    }

    public function test_customer_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/customers')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error_code' => 'AUTH_TOKEN_MISSING',
            ]);

        $this->getJson('/api/v1/customers/search?q=test')
            ->assertStatus(401);

        $this->getJson('/api/v1/customers/1')
            ->assertStatus(401);
    }
}