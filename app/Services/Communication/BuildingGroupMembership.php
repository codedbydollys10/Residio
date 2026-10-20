<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Models\Flat;
use App\Models\Resident;
use App\Models\Tower;
use App\Models\User;
use Illuminate\Support\Collection;

class BuildingGroupMembership
{
    public function userIds(Tower $tower): Collection
    {
        $residentIds = Resident::query()
            ->where('society_id', $tower->society_id)
            ->where('status', 'active')
            ->whereNotNull('user_id')
            ->whereHas('flat', fn ($query) => $query->where('tower_id', $tower->id))
            ->pluck('user_id');

        $ownerIds = Flat::query()
            ->where('society_id', $tower->society_id)
            ->where('tower_id', $tower->id)
            ->whereNotNull('owner_id')
            ->pluck('owner_id');

        return User::query()
            ->where('society_id', $tower->society_id)
            ->where('status', 'active')
            ->whereIn('id', $residentIds->merge($ownerIds)->unique())
            ->whereHas('roles', fn ($query) => $query->whereIn('slug', ['resident', 'tenant', 'family-member']))
            ->pluck('id');
    }

    public function belongsTo(User $user, Tower $tower): bool
    {
        return $this->userIds($tower)->contains(fn ($userId) => (int) $userId === (int) $user->id);
    }

    public function towerIdsFor(User $user): Collection
    {
        $residentTowerIds = Resident::query()
            ->where('society_id', $user->society_id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereHas('flat.tower', fn ($query) => $query->where('status', 'active'))
            ->with('flat:id,tower_id')
            ->get()
            ->map(fn (Resident $resident) => $resident->flat?->tower_id)
            ->filter();

        $ownedTowerIds = Flat::query()
            ->where('society_id', $user->society_id)
            ->where('owner_id', $user->id)
            ->whereHas('tower', fn ($query) => $query->where('status', 'active'))
            ->pluck('tower_id');

        return $residentTowerIds->merge($ownedTowerIds)->unique()->values();
    }
}
