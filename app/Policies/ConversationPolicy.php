<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Conversation;
use App\Services\Communication\BuildingGroupMembership;
use App\Models\User;

/**
 * Auto-discovered policy for Conversation -> ConversationPolicy.
 * Conversations are private to their listed participants, including for admins.
 */
class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Conversation $conversation): bool
    {
        if ($conversation->tower_id !== null) {
            if ((int) $user->society_id !== (int) $conversation->society_id) {
                return false;
            }

            if ($user->hasAnyRole(['society-admin', 'sub-admin'])) {
                return true;
            }

            $tower = $conversation->building;

            return $tower !== null && app(BuildingGroupMembership::class)->belongsTo($user, $tower);
        }

        return $conversation->participants()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('communication.send');
    }
}
