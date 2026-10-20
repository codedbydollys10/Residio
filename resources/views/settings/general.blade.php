@extends('layouts.app')
@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')
<div class="row g-3">
    <div class="col-lg-3">@include('settings._nav')</div>

    <div class="col-lg-9">
        <div class="card">
            <div class="card-body">
                <h2 class="h5 mb-1">General Settings</h2>
                <p class="text-muted small">Society-wide preferences used across the platform.</p>

                <form method="POST" action="{{ route('settings.update') }}">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Currency Symbol</label>
                            <input name="general[currency_symbol]" value="{{ $values['general.currency_symbol'] }}" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Timezone</label>
                            <input name="general[timezone]" value="{{ $values['general.timezone'] }}" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date Format</label>
                            <select name="general[date_format]" class="form-select">
                                @foreach (['d M Y' => '01 Jun 2026', 'd/m/Y' => '01/06/2026', 'Y-m-d' => '2026-06-01', 'M d, Y' => 'Jun 01, 2026'] as $fmt => $eg)
                                    <option value="{{ $fmt }}" @selected($values['general.date_format'] === $fmt)>{{ $eg }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Week Starts On</label>
                            <select name="general[week_start]" class="form-select">
                                <option value="monday" @selected($values['general.week_start'] === 'monday')>Monday</option>
                                <option value="sunday" @selected($values['general.week_start'] === 'sunday')>Sunday</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Support Email</label>
                            <input type="email" name="general[support_email]" value="{{ $values['general.support_email'] }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Support Phone</label>
                            <input name="general[support_phone]" value="{{ $values['general.support_phone'] }}" class="form-control">
                        </div>
                    </div>
                    @can('update', App\Models\Setting::class)
                        <div class="mt-4"><button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Settings</button></div>
                    @endcan
                </form>

                @if ($residentInviteCode)
                    <hr class="my-4">
                    <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                        <div>
                            <h3 class="h6 mb-1">Resident registration invite code</h3>
                            <p class="text-muted small mb-2">Share this code with residents so they can register and be linked to this society.</p>
                            <code class="fs-5 user-select-all">{{ $residentInviteCode }}</code>
                        </div>
                        @can('update', App\Models\Setting::class)
                            <form method="POST" action="{{ route('settings.resident-invite-code.rotate') }}"
                                  onsubmit="return confirm('Regenerate the code? The current code will stop working for new registrations.');">
                                @csrf
                                <button class="btn btn-outline-secondary"><i class="bi bi-arrow-clockwise me-1"></i>Regenerate code</button>
                            </form>
                        @endcan
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
