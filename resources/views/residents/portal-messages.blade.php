@extends('layouts.app')
@section('title', 'My Messages')
@section('page-title', 'Messages')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('resident.portal') }}">Resident Portal</a></li>
    <li class="breadcrumb-item active">Messages</li>
@endsection
@section('page-actions')
    <a href="{{ route('resident.portal') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Portal</a>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header bg-transparent"><h2 class="h6 mb-0">Your conversations</h2></div>
            <div class="card-body p-0">
                @forelse ($conversations as $conversation)
                    <a href="{{ route('communication.messages.show', $conversation) }}" class="d-block px-3 py-3 border-bottom text-decoration-none text-body">
                        <div class="d-flex justify-content-between gap-2">
                            <span class="fw-semibold">{{ $conversation->subject ?: 'Conversation #'.$conversation->id }}</span>
                            <span class="small text-muted">{{ $conversation->last_message_at?->diffForHumans() }}</span>
                        </div>
                        <div class="small text-muted">
                            {{ $conversation->latestMessage?->author?->name ?? 'Society management' }}:
                            {{ \Illuminate\Support\Str::limit($conversation->latestMessage?->body ?? '', 120) }}
                        </div>
                    </a>
                @empty
                    <p class="text-muted text-center py-5 mb-0">No messages yet. Use the form to contact society management.</p>
                @endforelse
            </div>
            @if ($conversations->hasPages())
                <div class="card-footer">{{ $conversations->links() }}</div>
            @endif
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card">
            <div class="card-header bg-transparent"><h2 class="h6 mb-0">Contact society management</h2></div>
            <div class="card-body">
                @if ($managers->isEmpty())
                    <p class="text-muted mb-0">There are no management accounts available to receive messages.</p>
                @else
                    <form method="POST" action="{{ route('resident.portal.messages.send') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="recipientId" class="form-label">Send to</label>
                            <select id="recipientId" name="recipient_id" class="form-select" required>
                                @foreach ($managers as $manager)
                                    <option value="{{ $manager->id }}" @selected(old('recipient_id') == $manager->id)>{{ $manager->name }}</option>
                                @endforeach
                            </select>
                            @error('recipient_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="messageSubject" class="form-label">Subject</label>
                            <input id="messageSubject" name="subject" maxlength="180" class="form-control" value="{{ old('subject') }}">
                            @error('subject')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="messageBody" class="form-label">Message</label>
                            <textarea id="messageBody" name="body" rows="5" maxlength="2000" class="form-control" required>{{ old('body') }}</textarea>
                            @error('body')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Send message</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
