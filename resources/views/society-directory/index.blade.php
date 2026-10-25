@extends('layouts.app')
@section('title', 'Societies Using Residio')
@section('page-title', 'Societies Using Residio')

@section('content')
<div class="card mb-3">
    <div class="card-body">
        <p class="text-muted mb-0">Discover communities using Residio. Active societies are listed automatically; only their name, city, and state are shown.</p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('society-directory.index') }}" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="directoryCity" class="form-label">City</label>
                <select id="directoryCity" name="city" class="form-select">
                    <option value="">All cities</option>
                    @foreach ($cities as $option)
                        <option value="{{ $option }}" @selected(strcasecmp($city, $option) === 0)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <label for="directoryState" class="form-label">State</label>
                <select id="directoryState" name="state" class="form-select">
                    <option value="">All states</option>
                    @foreach ($states as $option)
                        <option value="{{ $option }}" @selected(strcasecmp($state, $option) === 0)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1" type="submit">Filter</button>
                <a class="btn btn-outline-secondary" href="{{ route('society-directory.index') }}" aria-label="Clear filters">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3">
    @forelse ($societies as $society)
        <div class="col-md-6 col-xl-4">
            <article class="card h-100">
                <div class="card-body d-flex align-items-start gap-3">
                    <span class="stat-icon bg-soft-primary"><i class="bi bi-buildings"></i></span>
                    <div class="min-w-0">
                        <h2 class="h6 mb-1">{{ $society->name }}</h2>
                        <p class="text-muted small mb-0">
                            {{ collect([$society->city, $society->state])->filter()->join(', ') ?: 'Location not provided' }}
                        </p>
                    </div>
                </div>
            </article>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-buildings fs-2 text-muted" aria-hidden="true"></i>
                    <h2 class="h5 mt-3">No societies found</h2>
                    <p class="text-muted mb-0">Try another city or state. Active societies appear here automatically.</p>
                </div>
            </div>
        </div>
    @endforelse
</div>

<div class="mt-3">{{ $societies->links() }}</div>
@endsection
