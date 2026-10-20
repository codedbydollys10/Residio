@extends('layouts.app')
@section('title', 'Flat & Owner Directory')
@section('page-title', 'Flat & Owner Directory')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('maintenance.index') }}">Maintenance Management</a></li>
    <li class="breadcrumb-item active">Flat directory</li>
@endsection
@section('content')
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4"><label for="directorySearch" class="form-label">Owner, flat or phone</label><input id="directorySearch" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search owner name, flat or phone"></div>
            <div class="col-md-2"><label for="directoryTower" class="form-label">Wing / tower</label><select id="directoryTower" name="tower_id" class="form-select"><option value="">All towers</option>@foreach ($towers as $tower)<option value="{{ $tower->id }}" @selected(request('tower_id') == $tower->id)>{{ $tower->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label for="directoryPeriod" class="form-label">Billing period</label><input id="directoryPeriod" name="period" type="month" value="{{ $period }}" class="form-control"></div>
            <div class="col-md-2"><label for="uploadStatus" class="form-label">PDF status</label><select id="uploadStatus" name="upload_status" class="form-select"><option value="">All flats</option><option value="uploaded" @selected(request('upload_status') === 'uploaded')>PDF on file</option><option value="pending" @selected(request('upload_status') === 'pending')>No PDF on file</option></select></div>
            <div class="col-md-2 d-flex gap-2"><button class="btn btn-outline-primary" type="submit">Filter</button><a href="{{ route('maintenance.documents.directory') }}" class="btn btn-link">Clear</a></div>
        </form>
    </div>
</div>
<div class="card shadow-sm">
    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead><tr><th>Owner / resident</th><th>Flat</th><th>Phone</th><th>Email</th><th>PDF status · {{ $period }}</th><th>Last published</th><th>Contact / actions</th></tr></thead>
        <tbody>
        @forelse ($flats as $flat)
            @php
                $contacts = collect();
                if ($flat->owner) {
                    $ownerResident = $flat->residents->firstWhere('user_id', $flat->owner->id);
                    $contacts->push([
                        'name' => $flat->owner->name,
                        'phone' => $flat->owner->phone,
                        'email' => $flat->owner->email,
                        'user_id' => $flat->owner->id,
                        'resident_id' => $ownerResident?->id,
                    ]);
                }
                foreach ($flat->residents as $resident) {
                    if (in_array($resident->type, ['owner', 'tenant'], true) && $resident->status === 'active') {
                        $contacts->push([
                            'name' => $resident->name,
                            'phone' => $resident->phone ?: $resident->user?->phone,
                            'email' => $resident->email ?: $resident->user?->email,
                            'user_id' => $resident->user_id,
                            'resident_id' => $resident->id,
                        ]);
                    }
                }
                $contacts = $contacts->unique(fn ($contact) => $contact['user_id'] ? 'user:'.$contact['user_id'] : 'resident:'.$contact['name']);
                if ($contacts->isEmpty()) $contacts->push(['name' => 'Not provided', 'phone' => null, 'email' => null, 'user_id' => null, 'resident_id' => null]);
            @endphp
            @foreach ($contacts as $contact)
                @php
                    $phone = (string) ($contact['phone'] ?? '');
                    $digits = preg_replace('/\D+/', '', $phone);
                    $tel = strlen($digits) >= 6 && strlen($digits) <= 15
                        ? (str_starts_with(trim($phone), '+') ? '+' : '').$digits
                        : null;
                    $whatsapp = str_starts_with(trim($phone), '+') && strlen($digits) >= 8 && strlen($digits) <= 15 ? $digits : null;
                    $canMessage = $contact['user_id']
                        && auth()->user()->can('communication.send')
                        && \Route::has('communication.messages.compose');
                @endphp
                <tr>
                    <td class="fw-semibold">
                        {{ $contact['name'] }}
                        @if (! $contact['user_id'] && ! $contact['resident_id'])
                            <span class="d-block small text-warning">Owner not linked</span>
                        @endif
                    </td>
                    <td>{{ $flat->label }}</td>
                    <td>{{ $contact['phone'] ?: 'Not provided' }}</td>
                    <td>{{ $contact['email'] ?: 'Not provided' }}</td>
                    <td>
                        @if ($flat->available_documents_count)
                            <span class="badge text-bg-success">PDF uploaded</span>
                        @else
                            <span class="badge text-bg-secondary">No PDF on file</span>
                        @endif
                        @if ($flat->available_documents_count)
                            <a class="small d-block mt-1" href="{{ route('maintenance.documents.history', $flat->id) }}">View bill history</a>
                        @endif
                    </td>
                    <td>{{ $flat->latest_upload_at ? \Illuminate\Support\Carbon::parse($flat->latest_upload_at)->format('d M Y H:i') : '—' }}</td>
                    <td class="text-nowrap">
                        @if ($contact['resident_id'] && auth()->user()->can('residents.view'))
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('residents.show', $contact['resident_id']) }}" aria-label="View details for {{ $contact['name'] }}" title="View resident"><i class="bi bi-eye"></i></a>
                        @else
                            <button class="btn btn-sm btn-outline-secondary" type="button" disabled aria-label="Resident details unavailable for {{ $contact['name'] }}" title="Resident details are not linked"><i class="bi bi-eye"></i></button>
                        @endif
                        @if ($canMessage)
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('communication.messages.compose', ['recipient_id' => $contact['user_id']]) }}" aria-label="Message {{ $contact['name'] }}" title="Message"><i class="bi bi-chat-dots"></i></a>
                        @else
                            <button class="btn btn-sm btn-outline-primary" type="button" disabled aria-label="Messaging unavailable for {{ $contact['name'] }}" title="{{ $contact['user_id'] ? 'Messaging permission required' : 'No resident account linked' }}"><i class="bi bi-chat-dots"></i></button>
                        @endif
                        @if ($contact['email'])
                            <a class="btn btn-sm btn-outline-secondary" href="mailto:{{ $contact['email'] }}" aria-label="Email {{ $contact['name'] }}" title="Email"><i class="bi bi-envelope"></i></a>
                        @else
                            <button class="btn btn-sm btn-outline-secondary" type="button" disabled aria-label="Email unavailable for {{ $contact['name'] }}" title="No email address provided"><i class="bi bi-envelope"></i></button>
                        @endif
                        @if ($tel)<a class="btn btn-sm btn-outline-secondary" href="tel:{{ $tel }}" aria-label="Call {{ $contact['name'] }}" title="Call"><i class="bi bi-telephone"></i></a>@endif
                        @if ($whatsapp)<a class="btn btn-sm btn-outline-success" href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp {{ $contact['name'] }}"><i class="bi bi-whatsapp"></i></a>@endif
                        @if (! $contact['resident_id'] && auth()->user()->can('residents.create'))
                            <a class="btn btn-sm btn-outline-warning" href="{{ route('residents.create', ['flat_id' => $flat->id]) }}" aria-label="Add owner details for {{ $flat->label }}" title="Add owner details"><i class="bi bi-person-plus"></i></a>
                        @endif
                    </td>
                </tr>
            @endforeach
        @empty
            <tr><td colspan="7" class="text-center text-muted py-4">No flats found for these filters.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="card-body">{{ $flats->links() }}</div>
</div>
@endsection
