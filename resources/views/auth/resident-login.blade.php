@extends('layouts.auth')
@section('title', 'Resident sign in')

@section('content')
    <div class="text-center mb-4">
        <span class="badge text-bg-primary mb-2"><i class="bi bi-house-door me-1"></i>Resident access</span>
        <h2 class="h5 mb-1">Sign in to your resident portal</h2>
        <p class="text-muted small mb-0">View your bills, society updates, and private messages.</p>
    </div>

    <form method="POST" action="{{ route('resident.login') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="residentEmail">Email address</label>
            <input id="residentEmail" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" placeholder="name@example.com" autocomplete="username" required autofocus>
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="residentPassword">Password</label>
            <div class="input-group">
                <input id="residentPassword" type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="current-password" required>
                <button class="btn btn-outline-secondary" type="button" data-password-toggle aria-label="Show password" aria-pressed="false">
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
            </div>
            @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" name="remember" value="1" class="form-check-input" id="residentRemember">
            <label class="form-check-label" for="residentRemember">Remember me</label>
        </div>

        <button type="submit" class="btn btn-primary w-100">Sign in to resident portal</button>
    </form>

    <p class="text-center text-muted small mt-4 mb-0">
        New resident? <a href="{{ route('resident.register') }}">Create a resident account</a>
    </p>
    <p class="text-center small mt-2 mb-0">
        <a href="{{ route('login') }}">Society staff sign in</a>
    </p>
@endsection
