<?php

namespace Database\Factories;

use App\Models\Credit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Credit>
 */
class CreditFactory extends Factory
{
    protected $model = Credit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sourceId = fake()->unique()->numberBetween(100000, 999999);

        return [
            'source_id' => $sourceId,
            'logicalref' => $sourceId + 5000000,
            'branch' => 'Merkez',
            'name' => fake()->name(),
            'passport' => fake()->bothify('I-??######'),
            'phone' => fake()->numerify('9936#######'),
            'contract' => fake()->unique()->numerify('C-######'),
            'clientref' => fake()->numerify('#####'),
            'date_' => now()->subMonths(2),
            'amount' => '1000.00',
            'paid' => '0.00',
            'amount_local' => '1000.00',
            'paid_local' => '0.00',
            'active' => true,
            'is_blocked' => 0,
            'rv_bigint' => $sourceId,
        ];
    }

    /**
     * Local debt and how much of it is already paid.
     */
    public function debt(string $amount, string $paid = '0.00'): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
            'amount_local' => $amount,
            'paid_local' => $paid,
        ]);
    }
}
