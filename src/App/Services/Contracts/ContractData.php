<?php

namespace App\Services\Contracts;

use Carbon\Carbon;

/**
 * What the form says about a contract. Everything here is printed as it is.
 */
class ContractData
{
    /**
     * @param  int|null  $creditSourceId  the credit the form was filled from; null when filled by hand
     * @param  list<array{name: string, quantity: ?string, price: ?string, total: ?string}>  $goods  page 2 table rows
     * @param  list<array<string, string>>  $history  the buyer's earlier credits, as page 2 shows them
     */
    public function __construct(
        public readonly string $branch,
        public readonly Carbon $contractDate,
        public readonly string $customerName,
        public readonly ?string $passportSeries = null,
        public readonly ?string $passportNumber = null,
        public readonly ?string $address = null,
        public readonly ?string $phone = null,
        public readonly ?int $months = null,
        public readonly ?float $monthlyAmount = null,
        public readonly ?string $installmentPart = null,
        public readonly array $goods = [],
        public readonly array $history = [],
        public readonly ?int $creditSourceId = null,
    ) {
    }

    /**
     * The columns this data fills on a contract row.
     *
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'branch' => $this->branch,
            'contract_date' => $this->contractDate->toDateString(),
            'customer_name' => $this->customerName,
            'passport_series' => $this->passportSeries,
            'passport_number' => $this->passportNumber,
            'address' => $this->address,
            'phone' => $this->phone,
            'months' => $this->months,
            'monthly_amount' => $this->monthlyAmount,
            'installment_part' => $this->installmentPart,
            'goods' => $this->goods,
            'history' => $this->history,
            'credit_source_id' => $this->creditSourceId,
        ];
    }
}
