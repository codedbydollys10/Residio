@extends('layouts.auth')
@section('title', 'Sign in')

@section('content')
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Email address</label>
            <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" placeholder="name@example.com" autocomplete="username" required autofocus>
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="input-group">
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="current-password" required>
                <button class="btn btn-outline-secondary" type="button" data-password-toggle aria-label="Show password" aria-pressed="false">
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
            </div>
            @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-check">
                <input type="checkbox" name="remember" value="1" class="form-check-input" id="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100">Sign in</button>
    </form>

    <p class="text-center text-muted small mt-4 mb-0">
        New community? <a href="{{ route('register') }}">Create an account</a>
    </p>
    <p class="text-center small mt-2 mb-0">
        Resident or tenant? <a href="{{ route('resident.login') }}">Sign in to your resident portal</a>
    </p>
@endsection
