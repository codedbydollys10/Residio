@extends('layouts.auth')
@section('title', 'Resident registration')
@section('card-width', '700px')

@section('content')
    <div class="text-center mb-4">
        <span class="badge text-bg-primary mb-2"><i class="bi bi-person-plus me-1"></i>Resident access</span>
        <h2 class="h5 mb-1">Create your resident account</h2>
        <p class="text-muted small mb-0">Use the invite code and unit details provided by your society.</p>
    </div>

    <form method="POST" action="{{ route('resident.register') }}">
        @csrf
        <h3 class="h6 text-muted text-uppercase mb-3">Society and home</h3>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="inviteCode">Society invite code</label>
                <input id="inviteCode" name="invite_code" value="{{ old('invite_code') }}" class="form-control text-uppercase @error('invite_code') is-invalid @enderror" autocomplete="off" required>
                @error('invite_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="residentType">I am registering as</label>
                <select id="residentType" name="type" class="form-select @error('type') is-invalid @enderror" required>
                    <option value="">Choose owner or tenant</option>
                    <option value="owner" @selected(old('type') === 'owner')>Owner</option>
                    <option value="tenant" @selected(old('type') === 'tenant')>Tenant</option>
                </select>
                @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="buildingWing">Building name or wing</label>
                <select id="buildingWing" name="tower_id" class="form-select @error('tower_id') is-invalid @enderror" data-old-value="{{ old('tower_id') }}" disabled required>
                    <option value="">Enter your invite code first</option>
                </select>
                @error('tower_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div id="buildingWingHelp" class="form-text">Available buildings will load after you enter your invite code.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="flatNumber">Flat number</label>
                <select id="flatNumber" name="flat_number" class="form-select @error('flat_number') is-invalid @enderror" data-old-value="{{ old('flat_number') }}" disabled required>
                    <option value="">Select a building or wing first</option>
                </select>
                @error('flat_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <h3 class="h6 text-muted text-uppercase mt-4 mb-3">Your details</h3>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="residentName">Full name</label>
                <input id="residentName" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" autocomplete="name" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="residentPhone">Phone number</label>
                <input id="residentPhone" name="phone" type="tel" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" autocomplete="tel" required>
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="residentEmail">Email address</label>
                <input id="residentEmail" name="email" type="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" autocomplete="email" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="residentPassword">Password</label>
                <input id="residentPassword" name="password" type="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" minlength="8" required>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="residentPasswordConfirmation">Confirm password</label>
                <input id="residentPasswordConfirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" minlength="8" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 mt-4">Create resident account</button>
    </form>

    <p class="text-center text-muted small mt-4 mb-0">
        Already have an account? <a href="{{ route('resident.login') }}">Resident sign in</a>
    </p>
    <p class="text-center small mt-2 mb-0">
        <a href="{{ route('login') }}">Society staff sign in</a>
    </p>

    <script>
        (() => {
            const inviteCode = document.getElementById('inviteCode');
            const buildingWing = document.getElementById('buildingWing');
            const flatNumber = document.getElementById('flatNumber');
            const help = document.getElementById('buildingWingHelp');
            const oldTowerId = buildingWing.dataset.oldValue;
            const oldFlatNumber = flatNumber.dataset.oldValue;
            let towers = [];

            const resetUnits = () => {
                buildingWing.replaceChildren(new Option('Select a building or wing', ''));
                buildingWing.disabled = true;
                flatNumber.replaceChildren(new Option('Select a building or wing first', ''));
                flatNumber.disabled = true;
            };

            inviteCode.addEventListener('change', async () => {
                const code = inviteCode.value.trim().toUpperCase();
                inviteCode.value = code;
                towers = [];
                resetUnits();

                if (!code) {
                    help.textContent = 'Available buildings will load after you enter your invite code.';
                    return;
                }

                help.textContent = 'Loading buildings for this society…';

                try {
                    const url = new URL(@json(route('resident.register.structure')));
                    url.searchParams.set('invite_code', code);
                    const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const result = await response.json();

                    if (!response.ok) {
                        help.textContent = response.status === 422
                            ? 'Invite code not recognized. Check the code with your society administrator.'
                            : 'Could not load buildings right now. Please try again.';
                        return;
                    }

                    if (result.towers.length === 0) {
                        help.textContent = 'No buildings or wings are set up for this society yet. Please contact your administrator.';
                        return;
                    }

                    towers = result.towers;
                    towers.forEach((tower) => {
                        const label = tower.code ? `${tower.name} (${tower.code})` : tower.name;
                        buildingWing.add(new Option(label, tower.id, false, String(tower.id) === oldTowerId));
                    });
                    buildingWing.disabled = false;
                    help.textContent = 'Choose the building or wing shown in your society records.';

                    if (buildingWing.value) {
                        buildingWing.dispatchEvent(new Event('change'));
                    }
                } catch {
                    help.textContent = 'Could not load buildings right now. Please try again.';
                }
            });

            buildingWing.addEventListener('change', async () => {
                const selectedTower = buildingWing.value;
                flatNumber.replaceChildren(new Option('Select your flat', ''));
                flatNumber.disabled = true;

                if (!selectedTower) {
                    return;
                }

                const tower = towers.find((item) => String(item.id) === selectedTower);
                if (!tower) {
                    help.textContent = 'Could not load flats for this building. Re-enter the invite code and try again.';
                    return;
                }

                tower.flats.forEach((flat) => {
                    flatNumber.add(new Option(flat, flat, false, flat === oldFlatNumber));
                });
                flatNumber.disabled = tower.flats.length === 0;
                help.textContent = tower.flats.length
                    ? 'Select your flat from the society records.'
                    : 'No flats are set up for this building yet. Please contact your administrator.';
            });
        })();
    </script>
@endsection
