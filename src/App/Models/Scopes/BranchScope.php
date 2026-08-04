<?php

namespace App\Models\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Restricts every query on a branch-bearing model to the branches the signed-in
 * user is allowed to see.
 *
 * This is deliberately a global scope rather than a filter sprinkled across the
 * controllers: the data is read from three dozen places, and anything added
 * later would otherwise start out unprotected. Code that genuinely needs the
 * whole picture has to say so with withoutGlobalScope(BranchScope::class).
 *
 * It is a no-op when nobody is signed in, which is what lets the nightly report
 * command and the sync jobs still see every branch.
 */
class BranchScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $branches = $user->allowedBranches();

        if ($branches === null) {
            return;
        }

        // Qualified so the clause survives joins against the other credit table.
        $builder->whereIn($model->qualifyColumn('branch'), $branches);
    }
}
