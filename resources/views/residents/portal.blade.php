@extends('layouts.app')
@section('title', 'Resident Portal')
@section('page-title', 'Resident Portal')

@section('page-actions')
    @if (feature_enabled('communication'))
        <a href="{{ route('resident.portal.messages') }}" class="btn btn-primary">
            <i class="bi bi-chat-dots me-1"></i> Messages
        </a>
    @endif
@endsection

@section('content')
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100"><div class="card-body d-flex align-items-center gap-3">
            <span class="stat-icon bg-soft-primary"><i class="bi bi-receipt"></i></span>
            <div><div class="stat-value">{{ $openBillsCount }}</div><div class="stat-label">Open bills</div></div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100"><div class="card-body d-flex align-items-center gap-3">
            <span class="stat-icon bg-soft-info"><i class="bi bi-file-earmark-pdf"></i></span>
            <div><div class="stat-value">{{ $documents->count() }}</div><div class="stat-label">Recent documents</div></div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100"><div class="card-body d-flex align-items-center gap-3">
            <span class="stat-icon bg-soft-warning"><i class="bi bi-chat-dots"></i></span>
            <div><div class="stat-value">{{ $unreadMessages }}</div><div class="stat-label">Unread messages</div></div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100"><div class="card-body d-flex align-items-center gap-3">
            <span class="stat-icon bg-soft-success"><i class="bi bi-megaphone"></i></span>
            <div><div class="stat-value">{{ $notices->count() + $broadcasts->count() }}</div><div class="stat-label">Recent announcements</div></div>
        </div></div>
    </div>
</div>

@include('partials.society-directory-preview')

<div class="row g-3">
    @if (feature_enabled('billing'))
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0">My maintenance bills</h2>
                <a class="small" href="{{ route('resident.bills.index') }}">View bill documents</a>
            </div>
            <div class="card-body p-0">
                @forelse ($bills as $bill)
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center px-3 py-3 border-bottom">
                        <div>
                            <div class="fw-semibold">{{ $bill->bill_number }} · {{ $bill->period }}</div>
                            <div class="small text-muted">Due {{ $bill->due_date?->format('d M Y') ?? '—' }}</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-semibold">{{ money($bill->total) }}</div>
                            <span class="badge text-bg-{{ $bill->status === 'paid' ? 'success' : (in_array($bill->status, ['overdue', 'unpaid'], true) ? 'danger' : 'warning') }}">
                                {{ ucfirst($bill->status) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-muted text-center py-4 mb-0">No maintenance bills have been assigned to your account.</p>
                @endforelse
            </div>
            @if ($documents->isNotEmpty())
                <div class="card-footer">
                    <div class="small text-uppercase text-muted fw-semibold mb-2">Published bill documents</div>
                    @foreach ($documents as $document)
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div>
                                <div class="fw-semibold">{{ $document->period }} · {{ $document->original_filename }}</div>
                                <div class="small text-muted">{{ $document->published_at?->format('d M Y') }}</div>
                            </div>
                            <a href="{{ route('resident.bills.preview', $document) }}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">View PDF</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    @endif

    @if (feature_enabled('communication'))
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0">Messages from your society</h2>
                @if (feature_enabled('communication'))
                    <a class="small" href="{{ route('resident.portal.messages') }}">Open inbox</a>
                @endif
            </div>
            <div class="card-body p-0">
                @forelse ($conversations as $conversation)
                    <a href="{{ route('communication.messages.show', $conversation) }}" class="d-block px-3 py-3 border-bottom text-decoration-none text-body">
                        <div class="fw-semibold">{{ $conversation->subject ?: 'Conversation #'.$conversation->id }}</div>
                        <div class="small text-muted">
                            {{ $conversation->latestMessage?->body ? \Illuminate\Support\Str::limit($conversation->latestMessage->body, 100) : 'No messages yet' }}
                        </div>
                        <div class="small text-muted">{{ $conversation->last_message_at?->diffForHumans() }}</div>
                    </a>
                @empty
                    <p class="text-muted text-center py-4 mb-0">Messages from management will appear here.</p>
                @endforelse
            </div>
        </div>
    </div>
    @endif

    @if (feature_enabled('notices'))
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-transparent"><h2 class="h6 mb-0">Society notices</h2></div>
            <div class="card-body">
                @forelse ($notices as $notice)
                    <a href="{{ route('notices.show', $notice) }}" class="d-block py-2 text-decoration-none {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="fw-semibold">{{ $notice->title }}</div>
                        <div class="small text-muted">{{ \Illuminate\Support\Str::limit(strip_tags($notice->body), 120) }}</div>
                        <div class="small text-muted">{{ $notice->published_at?->diffForHumans() }}</div>
                    </a>
                @empty
                    <p class="text-muted mb-0">Published notices for residents will appear here.</p>
                @endforelse
            </div>
        </div>
    </div>
    @endif

    @if (feature_enabled('communication'))
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-transparent"><h2 class="h6 mb-0">Messages sent to you</h2></div>
            <div class="card-body">
                @forelse ($broadcasts as $broadcast)
                    <article class="py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="fw-semibold">{{ $broadcast->title }}</div>
                        <div class="small text-muted">{{ \Illuminate\Support\Str::limit($broadcast->message, 180) }}</div>
                        <div class="small text-muted">{{ $broadcast->sent_at?->diffForHumans() }}</div>
                    </article>
                @empty
                    <p class="text-muted mb-0">Announcements sent to your account will appear here.</p>
                @endforelse
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
