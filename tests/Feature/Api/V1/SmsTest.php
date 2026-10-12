<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRoleEnum;
use App\Models\Credit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class SmsTest extends ApiTestCase
{
    private Credit $aman;

    private Credit $noPhone;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-14 15:00:00'));
        config(['services.sms_api.base_url' => 'https://sms.test', 'services.sms_api.token' => 'secret-key']);
        Http::preventStrayRequests();

        $this->aman = $this->credit('1200.00', '200.00', [
            'name' => 'Aman Amanow', 'phone' => '99361000001', 'contract' => 'C-000111', 'branch' => 'Mary', 'date_' => '2026-06-01',
        ]);
        $this->noPhone = $this->credit('1200.00', '0.00', ['name' => 'No Phone', 'phone' => '', 'branch' => 'Mary']);
        $this->credit('1200.00', '0.00', ['name' => 'Lebap Customer', 'phone' => '99361000003', 'branch' => 'Lebap']);
        $this->credit('1200.00', '1200.00', ['name' => 'Settled', 'phone' => '99361000004']);
        $this->credit('1200.00', '0.00', ['name' => 'Blocked', 'phone' => '99361000005', 'is_blocked' => 1]);
    }

    public function test_customers_are_the_ones_the_panel_offers_with_their_status(): void
    {
        $response = $this->signIn($this->user(UserRoleEnum::OPERATOR))->getJson('/api/v1/sms/customers')->assertOk();

        $this->assertEqualsCanonicalizing(
            ['Aman Amanow', 'No Phone', 'Lebap Customer'],
            array_column($response->json('data.customers.items'), 'name')
        );
        // Every branch the user may see, whether or not it has someone to message.
        $this->assertEqualsCanonicalizing(['Lebap', 'Mary', 'Merkez'], $response->json('data.branches'));

        $aman = collect($response->json('data.customers.items'))->firstWhere('name', 'Aman Amanow');
        $this->assertSame(__('messages.overdue'), $aman['status_label']);
        $this->assertSame('danger', $aman['status_class']);
        $this->assertArrayHasKey('day_info', $aman);

        $this->getJson('/api/v1/sms/customers?branch[]=Lebap')->assertJsonPath('data.customers.meta.total', 1);
        $this->getJson('/api/v1/sms/customers?phone_status=no_phone')->assertJsonPath('data.customers.items.0.name', 'No Phone');
    }

    public function test_preview_renders_the_message_for_each_selected_customer(): void
    {
        $this->signIn($this->user(UserRoleEnum::OPERATOR))->postJson('/api/v1/sms/preview', [
            'selected_customers' => [$this->aman->source_id],
            'message' => 'Hormatly {name}, galan bergiňiz {remaining} TMT. Şertnama {contract}.',
            'schedule_type' => 'after_5m',
        ])
            ->assertOk()
            ->assertJsonCount(1, 'data.rows')
            ->assertJsonPath('data.rows.0.name', 'Aman Amanow')
            ->assertJsonPath('data.rows.0.phone', '99361000001')
            ->assertJsonPath('data.rows.0.message', 'Hormatly Aman Amanow, galan bergiňiz 1000.00 TMT. Şertnama C-000111.')
            ->assertJsonPath('data.schedule_type', 'after_5m');

        // Everyone the filters match, without picking them one by one.
        $this->postJson('/api/v1/sms/preview?branch[]=Mary', ['preview_mode' => 'filtered', 'message' => 'Salam {name}'])
            ->assertOk()
            ->assertJsonCount(2, 'data.rows');
    }

    public function test_preview_input_is_validated(): void
    {
        $this->signIn($this->user(UserRoleEnum::OPERATOR));

        $this->postJson('/api/v1/sms/preview', ['preview_mode' => 'selected', 'message' => 'x'])
            ->assertStatus(422)
            ->assertJsonPath('errors.selected_customers.0', __('messages.select_at_least_one_customer'));
        $this->postJson('/api/v1/sms/preview', ['selected_customers' => [$this->aman->source_id]])
            ->assertStatus(422)
            ->assertJsonPath('errors.message.0', __('messages.enter_sms_message'));
        $this->postJson('/api/v1/sms/preview', ['selected_customers' => [999999999], 'message' => 'x'])
            ->assertStatus(422)
            ->assertJsonPath('errors.selected_customers.0', __('messages.no_valid_customer_found'));
    }

    public function test_preview_export_downloads_the_spreadsheet(): void
    {
        $this->signIn($this->user(UserRoleEnum::OPERATOR));

        $response = $this->post('/api/v1/sms/preview/export', [
            'selected_customers' => [$this->aman->source_id, $this->noPhone->source_id],
            'message' => 'Salam {name}',
        ]);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('sms-preview-', (string) $response->headers->get('content-disposition'));

        $this->postJson('/api/v1/sms/preview/export', ['selected_customers' => [$this->noPhone->source_id], 'message' => 'Salam'])
            ->assertStatus(422)
            ->assertJsonPath('message', __('messages.no_valid_phone_for_export'))
            ->assertJsonPath('errors.skipped.0.name', 'No Phone')
            ->assertJsonPath('errors.skipped.0.reason', 'empty');
    }

    public function test_send_hands_the_distribution_to_the_sms_service(): void
    {
        Http::fake(['sms.test/*' => Http::response(['id' => 77, 'queued' => 1], 200)]);
        $operator = $this->user(UserRoleEnum::OPERATOR, ['firstname' => 'Oraz', 'lastname' => 'Orazow']);

        $this->signIn($operator)->postJson('/api/v1/sms/send', [
            'selected_customers' => [$this->aman->source_id, $this->noPhone->source_id],
            'message' => 'Salam {name}',
            'schedule_type' => 'after_5m',
        ])
            ->assertOk()
            ->assertJsonPath('message', __('messages.sms_send_request_success'))
            ->assertJsonPath('data.result.id', 77)
            ->assertJsonPath('data.skipped_no_phone', 1);

        Http::assertSentCount(1);
        Http::assertSent(function ($request) use ($operator) {
            return $request->url() === 'https://sms.test/api/distributions/manual'
                && $request->hasHeader('X-SMS-API-KEY', 'secret-key')
                && $request['service_name'] === 'DISTRIBUTION_KREDIT'
                && $request['start_time'] === '2026-10-14 15:05:00'
                && $request['created_by']['id'] === $operator->id
                && $request['created_by']['name'] === 'Oraz Orazow'
                && count($request['messages']) === 1
                && $request['messages'][0]['phone'] === '99361000001'
                && $request['messages'][0]['content'] === 'Salam Aman Amanow';
        });
    }

    public function test_send_is_refused_without_reaching_the_sms_service_when_input_is_wrong(): void
    {
        Http::fake();
        $this->signIn($this->user(UserRoleEnum::OPERATOR));

        $this->postJson('/api/v1/sms/send', ['selected_customers' => [$this->aman->source_id], 'message' => 'x'])
            ->assertStatus(422)->assertJsonPath('errors.schedule_type.0', __('messages.schedule_type_required'));
        $this->postJson('/api/v1/sms/send', ['selected_customers' => [$this->aman->source_id], 'message' => 'x', 'schedule_type' => 'custom', 'scheduled_at' => '2026-10-14 14:00'])
            ->assertStatus(422)->assertJsonPath('errors.scheduled_at.0', __('messages.scheduled_at_must_be_future'));
        $this->postJson('/api/v1/sms/send', ['selected_customers' => [$this->noPhone->source_id], 'message' => 'x', 'schedule_type' => 'now'])
            ->assertStatus(422)->assertJsonPath('errors.selected_customers.0', __('messages.no_customer_with_phone'));

        Http::assertNothingSent();
    }

    public function test_a_failing_sms_service_is_reported_not_swallowed(): void
    {
        Http::fake(['sms.test/*' => Http::response(['error' => 'down'], 503)]);

        $this->signIn($this->user(UserRoleEnum::OPERATOR))->postJson('/api/v1/sms/send', [
            'selected_customers' => [$this->aman->source_id],
            'message' => 'x',
            'schedule_type' => 'now',
        ])->assertStatus(422)->assertJsonPath('errors.api.0', __('messages.sms_api_request_failed') . ' (503)');
    }

    public function test_branch_restricted_operator_cannot_message_another_branch(): void
    {
        Http::fake();
        $lebap = Credit::withoutGlobalScopes()->where('name', 'Lebap Customer')->first();

        $this->signIn($this->user(UserRoleEnum::OPERATOR, ['branches' => ['Mary']]));

        $this->getJson('/api/v1/sms/customers')->assertJsonPath('data.customers.meta.total', 2);
        $this->postJson('/api/v1/sms/preview', ['selected_customers' => [$lebap->source_id], 'message' => 'x'])->assertStatus(422);
        $this->postJson('/api/v1/sms/send', ['selected_customers' => [$lebap->source_id], 'message' => 'x', 'schedule_type' => 'now'])->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_only_roles_with_sms_may_use_it(): void
    {
        Http::fake();

        foreach ([UserRoleEnum::CASHIER, UserRoleEnum::ANALYST, UserRoleEnum::MANAGER, UserRoleEnum::USER] as $role) {
            $this->signIn($this->user($role));
            $this->getJson('/api/v1/sms/customers')->assertForbidden();
            $this->postJson('/api/v1/sms/send', ['selected_customers' => [$this->aman->source_id], 'message' => 'x', 'schedule_type' => 'now'])->assertForbidden();
        }

        foreach ([UserRoleEnum::SUPER_ADMIN, UserRoleEnum::ADMIN, UserRoleEnum::OPERATOR] as $role) {
            $this->signIn($this->user($role))->getJson('/api/v1/sms/customers')->assertOk();
        }

        Http::assertNothingSent();
    }
}
