<?php

namespace Tests\Feature\Web\Contracts;

use App\Enums\UserRoleEnum;
use App\Models\CustomerContract;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class CustomerContractModelTest extends TestCase
{
    use RefreshDatabase;

    private function contract(array $attributes = []): CustomerContract
    {
        return CustomerContract::create($attributes + [
            'number' => 'UT-2026-' . str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'status' => CustomerContract::DRAFT,
            'template_version' => 'v1',
            'branch' => 'Mary',
            'contract_date' => '2026-10-21',
            'customer_name' => 'Test Customer',
            'goods' => [['name' => 'IMEI 1', 'quantity' => 1, 'price' => 1500]],
        ]);
    }

    private function printed(CustomerContract $contract): CustomerContract
    {
        $contract->forceFill(['status' => CustomerContract::PRINTED, 'printed_at' => now()])->save();

        return $contract;
    }

    public function test_a_draft_can_be_changed(): void
    {
        $contract = $this->contract();

        $contract->update(['customer_name' => 'Changed', 'goods' => []]);

        $this->assertSame('Changed', $contract->fresh()->customer_name);
    }

    public function test_a_printed_contract_cannot_be_changed(): void
    {
        $contract = $this->printed($this->contract());

        $this->expectException(LogicException::class);

        $contract->update(['customer_name' => 'Changed']);
    }

    public function test_a_printed_contract_cannot_go_back_to_draft(): void
    {
        $contract = $this->printed($this->contract());

        $this->expectException(LogicException::class);

        $contract->forceFill(['status' => CustomerContract::DRAFT])->save();
    }

    public function test_a_printed_contract_can_be_cancelled_and_printed_again(): void
    {
        $contract = $this->printed($this->contract());

        $contract->forceFill(['print_count' => 1, 'last_printed_at' => now()])->save();
        $contract->forceFill([
            'status' => CustomerContract::CANCELLED,
            'cancelled_at' => now(),
            'cancel_reason' => 'Wrong passport',
        ])->save();
        $contract->forceFill(['print_count' => 2])->save();

        $fresh = $contract->fresh();
        $this->assertTrue($fresh->isCancelled());
        $this->assertSame(2, $fresh->print_count);
        $this->assertSame('Test Customer', $fresh->customer_name);
    }

    public function test_a_cancelled_contract_cannot_be_changed_or_reopened(): void
    {
        $contract = $this->printed($this->contract());
        $contract->forceFill(['status' => CustomerContract::CANCELLED, 'cancelled_at' => now(), 'cancel_reason' => 'x'])->save();

        foreach ([['customer_name' => 'Changed'], ['status' => CustomerContract::PRINTED], ['cancel_reason' => 'y']] as $change) {
            try {
                $contract->fresh()->forceFill($change)->save();
                $this->fail('Change was accepted: ' . json_encode($change));
            } catch (LogicException) {
                // expected
            }
        }

        $this->assertSame('x', $contract->fresh()->cancel_reason);
    }

    public function test_a_contract_is_never_deleted(): void
    {
        $contract = $this->contract();

        try {
            $contract->delete();
            $this->fail('Contract was deleted');
        } catch (LogicException) {
            // expected
        }

        $this->assertDatabaseHas('customer_contracts', ['id' => $contract->id]);
    }

    public function test_numbers_are_unique(): void
    {
        $this->contract(['number' => 'UT-2026-0000001']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $this->contract(['number' => 'UT-2026-0000001']);
    }

    public function test_a_branch_restricted_user_sees_only_their_branches_contracts(): void
    {
        $this->contract(['branch' => 'Mary', 'customer_name' => 'Own']);
        $this->contract(['branch' => 'Lebap', 'customer_name' => 'Other']);

        $this->actingAs(UserFactory::new()->role(UserRoleEnum::CASHIER)->branches(['Mary'])->create());
        $this->assertSame(['Own'], CustomerContract::pluck('customer_name')->all());

        $this->actingAs(UserFactory::new()->role(UserRoleEnum::SUPER_ADMIN)->branches(['Mary'])->create());
        $this->assertCount(2, CustomerContract::all());
    }
}
