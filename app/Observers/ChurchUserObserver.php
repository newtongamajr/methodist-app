<?php

namespace App\Observers;

use App\Models\Pivots\ChurchUser;

class ChurchUserObserver
{
    /**
     * Single-primary invariant: at most one church_user row per user_id can
     * carry is_primary=true. When a row is saved with is_primary=true, demote
     * every other row for the same user.
     *
     * Fires on both insert + update (saved hook).
     */
    public function saved(ChurchUser $pivot): void
    {
        if (! $pivot->is_primary) {
            return;
        }

        ChurchUser::query()
            ->where('user_id', $pivot->user_id)
            ->where('church_id', '!=', $pivot->church_id)
            ->where('is_primary', true)
            ->update(['is_primary' => false]);
    }
}
