<div class="card mt-3">
    <div class="card-header bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h6 mb-0">Societies near you</h2>
            @if ($currentSociety?->city || $currentSociety?->state)
                <div class="small text-muted">{{ collect([$currentSociety?->city, $currentSociety?->state])->filter()->join(', ') }}</div>
            @endif
        </div>
        <a class="btn btn-sm btn-outline-primary" href="{{ route('society-directory.index') }}">Explore directory</a>
    </div>
    <div class="card-body">
        @if ($nearbySocieties->isNotEmpty())
            <div class="row g-3">
                @foreach ($nearbySocieties as $nearbySociety)
                    <div class="col-md-6 col-xl-4">
                        <div class="border rounded p-3 h-100">
                            <div class="fw-semibold">{{ $nearbySociety->name }}</div>
                            <div class="small text-muted">{{ collect([$nearbySociety->city, $nearbySociety->state])->filter()->join(', ') ?: 'Location not provided' }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif (! $currentSociety?->city && ! $currentSociety?->state)
            <p class="text-muted mb-0">Add your city and state to your society profile to discover nearby communities.</p>
        @else
            <p class="text-muted mb-0">No other active societies were found in your area.</p>
        @endif
    </div>
</div>
