<?php

namespace App\Support;

use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Str;

/**
 * Turns the data a panel page hands to its view into plain JSON, so an API
 * endpoint can return exactly what the page shows without a second copy of
 * the query behind it.
 */
class ViewData
{
    /**
     * Figures the pages read from the models but that are not stored columns.
     */
    private const COMPUTED = [
        Credit::class => ['local_remaining', 'local_closed', 'remote_remaining'],
        CreditPayment::class => ['applied_amount', 'is_voided'],
    ];

    public static function toArray(array $data): array
    {
        $out = [];

        foreach ($data as $key => $value) {
            $out[Str::snake($key)] = self::convert($value);
        }

        return $out;
    }

    private static function convert(mixed $value): mixed
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if ($value instanceof CarbonInterface) {
            return $value->toIso8601String();
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof LengthAwarePaginator) {
            return [
                'items' => self::convert($value->items()),
                'meta' => [
                    'current_page' => $value->currentPage(),
                    'last_page' => $value->lastPage(),
                    'per_page' => $value->perPage(),
                    'total' => $value->total(),
                ],
            ];
        }

        // Staff appear in reports by name only.
        if ($value instanceof User) {
            return ['id' => $value->id, 'full_name' => $value->full_name];
        }

        if ($value instanceof Model) {
            $row = $value->attributesToArray();

            foreach (self::COMPUTED[$value::class] ?? [] as $attribute) {
                $row[$attribute] = $value->{$attribute};
            }

            foreach ($value->getRelations() as $name => $related) {
                $row[Str::snake($name)] = $related;
            }

            return self::convert($row);
        }

        if ($value instanceof Enumerable) {
            return self::convert($value->all());
        }

        if (is_array($value)) {
            return array_map(self::convert(...), $value);
        }

        if ($value instanceof \stdClass) {
            return self::convert((array) $value);
        }

        // Anything else is a helper object for the template, not data.
        return null;
    }
}
