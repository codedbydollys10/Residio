<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterResidentRequest;
use App\Models\Flat;
use App\Models\Resident;
use App\Models\Society;
use App\Models\Tower;
use App\Models\User;
use App\Models\Scopes\TenantScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class ResidentRegistrationController extends Controller
{
    public function __construct(protected LoginController $login) {}

    public function show(): View
    {
        return view('auth.register-resident');
    }

    public function structure(Request $request): JsonResponse
    {
        $request->merge([
            'invite_code' => strtoupper(trim((string) $request->query('invite_code'))),
        ]);

        $data = $request->validate([
            'invite_code' => [
                'required',
                'string',
                Rule::exists('societies', 'resident_invite_code')->where('status', 'active'),
            ],
        ]);

        $society = Society::query()
            ->where('resident_invite_code', strtoupper(trim($data['invite_code'])))
            ->where('status', 'active')
            ->firstOrFail();

        $towers = Tower::withoutGlobalScope(TenantScope::class)
            ->where('society_id', $society->id)
            ->where('status', 'active')
            ->with(['flats' => fn ($query) => $query
                ->withoutGlobalScope(TenantScope::class)
                ->where('society_id', $society->id)
                ->orderBy('number')])
            ->orderBy('name')
            ->get()
            ->map(fn (Tower $tower): array => [
                'id' => $tower->id,
                'name' => $tower->name,
                'code' => $tower->code,
                'flats' => $tower->flats->pluck('number')->values(),
            ])
            ->values();

        return response()->json(['towers' => $towers]);
    }

    public function store(RegisterResidentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $society = Society::query()
            ->where('resident_invite_code', $data['invite_code'])
            ->where('status', 'active')
            ->firstOrFail();

        $tower = Tower::withoutGlobalScope(TenantScope::class)
            ->where('society_id', $society->id)
            ->where('status', 'active')
            ->find($data['tower_id']);

        if (! $tower) {
            throw ValidationException::withMessages([
                'tower_id' => 'Choose a building or wing from this society’s list.',
            ]);
        }

        $flat = Flat::withoutGlobalScope(TenantScope::class)
            ->where('society_id', $society->id)
            ->where('tower_id', $tower->id)
            ->whereRaw('LOWER(number) = ?', [strtolower(trim($data['flat_number']))])
            ->first();

        if (! $flat) {
            throw ValidationException::withMessages([
                'flat_number' => 'We could not find that flat in the selected building or wing.',
            ]);
        }

        $user = DB::transaction(function () use ($data, $society, $flat): User {
            $flat = Flat::withoutGlobalScope(TenantScope::class)
                ->where('society_id', $society->id)
                ->lockForUpdate()
                ->findOrFail($flat->id);

            if ($data['type'] === 'owner') {
                $alreadyRegistered = $flat->owner_id !== null
                    || Resident::query()
                        ->where('society_id', $society->id)
                        ->where('flat_id', $flat->id)
                        ->where('type', 'owner')
                        ->where('status', 'active')
                        ->exists();

                if ($alreadyRegistered) {
                    throw ValidationException::withMessages([
                        'flat_number' => 'An owner is already registered for this flat. Please contact your society administrator.',
                    ]);
                }
            }

            $user = User::create([
                'society_id' => $society->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'status' => 'active',
            ]);
            $user->assignRole($data['type'] === 'owner' ? 'resident' : 'tenant');

            Resident::create([
                'society_id' => $society->id,
                'user_id' => $user->id,
                'flat_id' => $flat->id,
                'type' => $data['type'],
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'is_primary' => true,
                'move_in_date' => now()->toDateString(),
                'status' => 'active',
            ]);

            $flat->update([
                'owner_id' => $data['type'] === 'owner' ? $user->id : $flat->owner_id,
                'status' => 'occupied',
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->put('login.redirect_to_resident_portal', true);

        return $this->login->finalize($request, $user)
            ->with('success', 'Your resident account is ready.');
    }
}
