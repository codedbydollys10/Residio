<?php

declare(strict_types=1);

namespace App\Services\Society;

use App\Models\Society;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;

class SocietyDirectory
{
    public function listedQuery(): Builder
    {
        return Society::query()
            ->where('status', 'active')
            ->whereNotExists(function (QueryBuilder $duplicates): void {
                $duplicates->selectRaw('1')
                    ->from('societies as duplicate_societies')
                    ->whereColumn('duplicate_societies.id', '>', 'societies.id')
                    ->whereColumn('duplicate_societies.status', 'societies.status')
                    ->whereRaw('LOWER(TRIM(duplicate_societies.name)) = LOWER(TRIM(societies.name))')
                    ->whereNull('duplicate_societies.deleted_at');
            });
    }

    public function nearby(?Society $currentSociety, int $limit = 3): Collection
    {
        if (! $currentSociety || (! $currentSociety->city && ! $currentSociety->state)) {
            return collect();
        }

        $query = $this->listedQuery()
            ->select(['id', 'name', 'city', 'state'])
            ->where('id', '!=', $currentSociety->id);

        if ($currentSociety->city) {
            $query->whereRaw('LOWER(city) = ?', [strtolower($currentSociety->city)]);
        }

        if ($currentSociety->state) {
            $query->whereRaw('LOWER(state) = ?', [strtolower($currentSociety->state)]);
        }

        return $query->orderBy('name')->limit($limit)->get();
    }
}
