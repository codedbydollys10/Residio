@extends('layouts.app')
@section('title', 'Building Group Chats')
@section('page-title', 'Building Group Chats')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('communication.index') }}">Communication</a></li>
    <li class="breadcrumb-item active">Building Group Chats</li>
@endsection

@section('content')
<div class="row g-3">
    @forelse ($buildings as $building)
        <div class="col-md-6 col-xl-4">
            <a href="{{ route('communication.building-groups.open', $building) }}"
               class="card h-100 shadow-sm text-decoration-none text-body">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="stat-icon bg-soft-primary flex-shrink-0">
                        <i class="bi bi-chat-square-dots"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 class="h6 mb-1 text-truncate">{{ $building->name }} Group</h2>
                        <p class="small text-muted mb-0">Chat with residents and society management</p>
                    </div>
                    <i class="bi bi-chevron-right ms-auto text-muted"></i>
                </div>
            </a>
        </div>
    @empty
        <div class="col-12">
            <div class="card"><div class="card-body text-center text-muted py-5">
                <i class="bi bi-chat-square-dots fs-2 d-block mb-2"></i>
                @if (auth()->user()->hasAnyRole(['resident', 'tenant', 'family-member']))
                    Your account is not linked to an active flat in a building yet. Please contact your society administrator.
                @else
                    There are no active buildings available for group chats.
                @endif
            </div></div>
        </div>
    @endforelse
</div>
@endsection
