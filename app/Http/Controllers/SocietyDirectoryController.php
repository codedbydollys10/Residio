<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Society\SocietyDirectory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SocietyDirectoryController extends Controller
{
    public function __construct(protected SocietyDirectory $directory) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
        ]);
        $city = trim((string) ($filters['city'] ?? ''));
        $state = trim((string) ($filters['state'] ?? ''));

        $query = $this->directory->listedQuery()
            ->select(['id', 'name', 'city', 'state']);

        if ($city !== '') {
            $query->whereRaw('LOWER(city) = ?', [strtolower($city)]);
        }
        if ($state !== '') {
            $query->whereRaw('LOWER(state) = ?', [strtolower($state)]);
        }

        $societies = $query->orderBy('name')->paginate(12)->withQueryString();
        $cities = $this->directory->listedQuery()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');
        $states = $this->directory->listedQuery()
            ->whereNotNull('state')
            ->where('state', '!=', '')
            ->distinct()
            ->orderBy('state')
            ->pluck('state');

        return view('society-directory.index', compact(
            'societies',
            'cities',
            'states',
            'city',
            'state',
        ));
    }
}
