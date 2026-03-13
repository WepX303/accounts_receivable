<?php

namespace Tests\Feature\Api\Reports;

use App\Enums\UserRoleEnum;
use App\Models\AvshocrecatReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReportApiTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithToken(
        string $role = 'Admin',
        string $email = 'report@example.com',
        string $phone = '99360000500'
    ): User {
        return User::create([
            'firstname' => 'Report',
            'lastname' => 'User',
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

    private function createReport(array $overrides = []): AvshocrecatReport
    {
        return AvshocrecatReport::create(array_merge([
            'magazyn' => 'LCA',
            'karz_alyjy' => 'Durdymyradowa Jahan Carymyradowna',
            'telefon_belgisi' => '862214550 864146419',
            'pasport_belgisi' => 'I-AH 480069',
            'sertnama_nomeri' => '0014295',
            'tiger_kody' => '120.TMT.10001029',
            'kt_cykdajy' => 1000.00,
            'dt_girdeji' => 200.00,
            'm1' => 50.00,
            'm2' => 60.00,
            'm3' => 70.00,
            'm4' => 80.00,
            'm5' => 90.00,
            'm6' => 100.00,
            'galyndy' => 350.00,
            'aylyk_tolegi' => 100.00,
            'karz_alan_senesi' => '2025-01-01',
            'gutaryan_senesi' => '2025-12-31',
            'kategoriyasy' => 'Standard',
            'maglumat' => 'Monthly payment info',
            'bellik' => 'Note',
            'tolejek_senesi' => now()->toDateString(),
            'statusy' => 'AKTIW',
        ], $overrides));
    }

    public function test_allowed_role_can_get_report_list(): void
    {
        $user = $this->createUserWithToken(
            UserRoleEnum::ANALYST->value,
            'analyst-report@example.com',
            '99360000501'
        );

        $this->createReport([
            'karz_alyjy' => 'Report Customer One',
            'sertnama_nomeri' => 'R-001',
        ]);

        $this->createReport([
            'karz_alyjy' => 'Report Customer Two',
            'sertnama_nomeri' => 'R-002',
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $user->token)
            ->getJson('/api/v1/reports');

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Report list fetched successfully.',
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'magazyn',
                        'karz_alyjy',
                        'telefon_belgisi',
                        'pasport_belgisi',
                        'sertnama_nomeri',
                        'tiger_kody',
                        'kt_cykdajy',
                        'dt_girdeji',
                        'galyndy',
                        'aylyk_tolegi',
                        'tolejek_senesi',
                        'statusy',
                        'created_at',
                        'updated_at',
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

    public function test_report_list_can_be_filtered_by_query(): void
    {
        $user = $this->createUserWithToken(
            UserRoleEnum::ADMIN->value,
            'admin-report@example.com',
            '99360000502'
        );

        $this->createReport([
            'karz_alyjy' => 'Unique Report Person',
            'sertnama_nomeri' => 'UNIQUE-001',
        ]);

        $this->createReport([
            'karz_alyjy' => 'Another Person',
            'sertnama_nomeri' => 'OTHER-002',
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $user->token)
            ->getJson('/api/v1/reports?q=Unique');

        $response
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.karz_alyjy', 'Unique Report Person');
    }

    public function test_allowed_role_can_get_report_detail(): void
    {
        $user = $this->createUserWithToken(
            UserRoleEnum::CASHIER->value,
            'cashier-report@example.com',
            '99360000503'
        );

        $report = $this->createReport([
            'karz_alyjy' => 'Detail Report Person',
            'sertnama_nomeri' => 'DETAIL-001',
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $user->token)
            ->getJson('/api/v1/reports/' . $report->id);

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Report detail fetched successfully.',
            ])
            ->assertJsonPath('data.id', $report->id)
            ->assertJsonPath('data.karz_alyjy', 'Detail Report Person')
            ->assertJsonPath('data.sertnama_nomeri', 'DETAIL-001');
    }

    public function test_forbidden_role_cannot_access_reports(): void
    {
        $user = $this->createUserWithToken(
            UserRoleEnum::USER->value,
            'simpleuser-report@example.com',
            '99360000504'
        );

        $report = $this->createReport();

        $this->withHeader('Authorization', 'Bearer ' . $user->token)
            ->getJson('/api/v1/reports')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error_code' => 'ROLE_FORBIDDEN',
            ]);

        $this->withHeader('Authorization', 'Bearer ' . $user->token)
            ->getJson('/api/v1/reports/' . $report->id)
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error_code' => 'ROLE_FORBIDDEN',
            ]);
    }

    public function test_report_routes_require_authentication(): void
    {
        $report = $this->createReport();

        $this->getJson('/api/v1/reports')
            ->assertStatus(401);

        $this->getJson('/api/v1/reports/' . $report->id)
            ->assertStatus(401);
    }
}