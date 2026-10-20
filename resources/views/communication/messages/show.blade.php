@extends('layouts.app')
@section('title', $conversation->subject ?: 'Conversation #'.$conversation->id)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('communication.index') }}">Communication</a></li>
    <li class="breadcrumb-item"><a href="{{ route('communication.messages.index') }}">Inbox</a></li>
    <li class="breadcrumb-item active">{{ Str::limit($conversation->subject ?? 'Conversation #'.$conversation->id, 30) }}</li>
@endsection

@section('content')
@if ($conversation->tower_id)
    <div class="whatsapp-chat">
        <header class="whatsapp-chat-header">
            <span class="whatsapp-group-avatar"><i class="bi bi-people-fill"></i></span>
            <div class="min-w-0">
                <div class="fw-semibold text-truncate">{{ $conversation->subject }}</div>
                <div class="small whatsapp-chat-subtitle">
                    {{ $conversation->participants->count() }} members · {{ $conversation->building?->name }}
                </div>
            </div>
            <button type="button" class="btn whatsapp-info-button ms-auto" data-bs-toggle="modal" data-bs-target="#groupInfoModal" aria-label="Group info">
                <i class="bi bi-three-dots-vertical"></i>
            </button>
        </header>

        <div class="whatsapp-chat-messages" id="messageThread" aria-live="polite">
            @forelse ($conversation->messages as $msg)
                @php $isMine = $msg->user_id === auth()->id(); @endphp
                <div class="whatsapp-message-row {{ $isMine ? 'is-mine' : '' }}">
                    <article class="whatsapp-message {{ $isMine ? 'whatsapp-message-mine' : 'whatsapp-message-theirs' }}">
                        @unless ($isMine)
                            <div class="whatsapp-message-author">{{ $msg->author?->name ?? 'Society member' }}</div>
                        @endunless
                        @if ($msg->body !== '')
                            <div class="whatsapp-message-body">{{ $msg->body }}</div>
                        @endif
                        @if ($msg->attachment_path)
                            <div class="whatsapp-message-attachment">
                                <a href="{{ route('communication.messages.attachment', $msg) }}" target="_blank" rel="noopener">
                                    <i class="bi bi-paperclip me-1"></i>{{ $msg->attachment_name }}
                                </a>
                                <a href="{{ route('communication.messages.attachment.download', $msg) }}" aria-label="Download {{ $msg->attachment_name }}">
                                    <i class="bi bi-download"></i>
                                </a>
                            </div>
                        @endif
                        <time class="whatsapp-message-time" datetime="{{ $msg->created_at->toIso8601String() }}">
                            {{ $msg->created_at->format('H:i') }}
                        </time>
                    </article>
                </div>
            @empty
                <div class="whatsapp-chat-empty">
                    <i class="bi bi-chat-heart"></i>
                    <span>This is the beginning of the {{ $conversation->building?->name }} group chat.</span>
                </div>
            @endforelse
        </div>

        <footer class="whatsapp-chat-composer">
            <form method="POST" action="{{ route('communication.messages.store') }}" enctype="multipart/form-data" class="whatsapp-compose-form">
                @csrf
                <input type="hidden" name="conversation_id" value="{{ $conversation->id }}">
                <label class="whatsapp-attach-button" for="groupReplyAttachment" aria-label="Attach a file">
                    <i class="bi bi-paperclip"></i>
                </label>
                <input id="groupReplyAttachment" name="attachment" type="file" class="visually-hidden"
                       accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp">
                <textarea name="body" rows="1" class="form-control whatsapp-compose-input"
                          placeholder="Type a message" aria-label="Type a message"></textarea>
                <button class="whatsapp-send-button" type="submit" aria-label="Send message">
                    <i class="bi bi-send-fill"></i>
                </button>
                @error('attachment') <div class="text-danger small whatsapp-attachment-error">{{ $message }}</div> @enderror
                @error('body') <div class="text-danger small whatsapp-attachment-error">{{ $message }}</div> @enderror
            </form>
            <div class="whatsapp-upload-name small" id="groupReplyAttachmentName"></div>
        </footer>
    </div>

    <div class="modal fade" id="groupInfoModal" tabindex="-1" aria-labelledby="groupInfoTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="groupInfoTitle">{{ $conversation->subject }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">{{ $conversation->participants->count() }} members · {{ $conversation->building?->name }}</p>
                    <ul class="list-group list-group-flush">
                        @foreach ($conversation->participants as $part)
                            <li class="list-group-item d-flex align-items-center gap-2 px-0">
                                <span class="stat-icon bg-soft-secondary" style="width:36px;height:36px">
                                    <i class="bi bi-person"></i>
                                </span>
                                <span class="flex-grow-1">{{ $part->participant?->name ?? '(deleted user)' }}</span>
                                @if ((int) $part->user_id === (int) $conversation->group_admin_id)
                                    <span class="badge text-bg-success">Group admin · Secretary</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@else
<div class="row g-3">
    {{-- Thread --}}
    <div class="col-lg-8">
        <div class="card shadow-sm mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <span class="fw-semibold">{{ $conversation->subject ?: 'Conversation #'.$conversation->id }}</span>
                    @if ($conversation->tower_id)
                        <div class="small text-muted">
                            {{ $conversation->building?->name }} · Building group · Group admin: {{ $conversation->groupAdmin?->name ?? 'Society Secretary' }}
                        </div>
                    @endif
                </div>
                <span class="badge {{ $conversation->type === 'group' ? 'text-bg-info' : 'text-bg-secondary' }}">
                    {{ $conversation->tower_id ? 'Building Group' : ucfirst($conversation->type) }}
                </span>
            </div>
            <div class="card-body" style="max-height:500px;overflow-y:auto;" id="messageThread">
                @forelse ($conversation->messages as $msg)
                    @php $isMine = $msg->user_id === auth()->id(); @endphp
                    <div class="d-flex {{ $isMine ? 'justify-content-end' : 'justify-content-start' }} mb-3">
                        <div style="max-width:75%;">
                            <div class="small text-muted mb-1 {{ $isMine ? 'text-end' : '' }}">
                                {{ $msg->author?->name ?? 'System' }} &middot;
                                {{ $msg->created_at->format('d M Y H:i') }}
                            </div>
                            <div class="p-2 rounded {{ $isMine ? 'bg-primary text-white' : 'bg-body-secondary' }}">
                                {{ $msg->body }}
                            </div>
                            @if ($msg->maintenanceBillDocument)
                                <a class="btn btn-sm btn-outline-secondary mt-2" href="{{ route('maintenance.documents.preview', $msg->maintenanceBillDocument) }}" target="_blank" rel="noopener">
                                    <i class="bi bi-file-earmark-pdf text-danger me-1"></i>
                                    {{ $msg->maintenanceBillDocument->original_filename }}
                                </a>
                            @endif
                            @if ($msg->attachment_path)
                                <div class="mt-2 d-flex flex-wrap align-items-center gap-2">
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('communication.messages.attachment', $msg) }}" target="_blank" rel="noopener">
                                        <i class="bi bi-paperclip me-1"></i>{{ $msg->attachment_name }}
                                    </a>
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('communication.messages.attachment.download', $msg) }}">
                                        <i class="bi bi-download me-1"></i>Download
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-muted text-center py-3">No messages in this conversation.</p>
                @endforelse
            </div>
        </div>

        {{-- Reply form --}}
        <div class="card shadow-sm"><div class="card-body">
            <form method="POST" action="{{ route('communication.messages.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="conversation_id" value="{{ $conversation->id }}">
                <div class="mb-2">
                    <textarea name="body" rows="3" class="form-control"
                              placeholder="Write your reply…" required></textarea>
                </div>
                @php
                    $availableDocuments = \App\Models\MaintenanceBillDocument::query()
                        ->where('society_id', auth()->user()->society_id)
                        ->where('status', 'available')
                        ->whereIn('resident_user_id', $conversation->participants->pluck('user_id')->filter())
                        ->latest('published_at')
                        ->get();
                @endphp
                @if (! $conversation->tower_id && $availableDocuments->isNotEmpty())
                    <div class="mb-2">
                        <label for="messageDocument" class="form-label small">Attach a published maintenance bill</label>
                        <select id="messageDocument" name="maintenance_bill_document_id" class="form-select form-select-sm">
                            <option value="">No attachment</option>
                            @foreach ($availableDocuments as $document)
                                <option value="{{ $document->id }}">{{ $document->resident?->name }} · {{ $document->period }} · {{ $document->original_filename }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="mb-2">
                    <label for="replyAttachment" class="form-label small">Upload a bill or file <span class="text-muted">(optional)</span></label>
                    <input id="replyAttachment" name="attachment" type="file" class="form-control form-control-sm"
                           accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp">
                    <div class="form-text">PDF, JPG, PNG, or WEBP; up to {{ (int) config('communityos.communication.attachment_max_kb', 10240) / 1024 }} MB.</div>
                    @error('attachment') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <button class="btn btn-primary btn-sm"><i class="bi bi-send me-1"></i>Send Reply</button>
            </form>
        </div></div>
    </div>

    {{-- Participants sidebar --}}
    <div class="col-lg-4">
        <div class="card shadow-sm"><div class="card-body">
            <h3 class="h6 mb-3">Participants</h3>
            <ul class="list-unstyled mb-0">
                @foreach ($conversation->participants as $part)
                    <li class="d-flex align-items-center gap-2 mb-2">
                        <span class="stat-icon bg-soft-secondary" style="width:32px;height:32px;font-size:.7rem;">
                            <i class="bi bi-person"></i>
                        </span>
                        <span class="small">
                            {{ $part->participant?->name ?? '(deleted user)' }}
                            @if ($conversation->tower_id && (int) $part->user_id === (int) $conversation->group_admin_id)
                                <span class="badge text-bg-primary ms-1">Secretary · Group admin</span>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        </div></div>
    </div>
</div>
@endif
@endsection

@push('styles')
<style>
    .whatsapp-chat {
        --chat-header: #202c33;
        --chat-wallpaper: #0b141a;
        --chat-composer: #202c33;
        --chat-message-in: #202c33;
        --chat-message-out: #005c4b;
        display: flex;
        flex-direction: column;
        height: min(72vh, 780px);
        min-height: 480px;
        overflow: hidden;
        border: 1px solid rgba(134, 150, 160, .18);
        border-radius: .85rem;
        background: var(--chat-wallpaper);
        box-shadow: 0 4px 18px rgba(0, 0, 0, .22);
    }
    .whatsapp-chat-header {
        display: flex;
        flex: 0 0 auto;
        align-items: center;
        gap: .8rem;
        min-height: 68px;
        padding: .7rem 1rem;
        color: #e9edef;
        background: var(--chat-header);
        border-bottom: 1px solid rgba(134, 150, 160, .16);
    }
    .whatsapp-group-avatar {
        display: grid;
        flex: 0 0 44px;
        width: 44px;
        height: 44px;
        place-items: center;
        border-radius: 50%;
        color: #d1d7db;
        background: #374248;
        font-size: 1.2rem;
    }
    .whatsapp-chat-subtitle { color: #aebac1; }
    .whatsapp-info-button {
        width: 40px;
        height: 40px;
        border: 0;
        border-radius: 50%;
        color: #aebac1;
        font-size: 1.15rem;
    }
    .whatsapp-info-button:hover { color: #fff; background: rgba(255, 255, 255, .08); }
    .whatsapp-chat-messages {
        display: flex;
        flex: 1 1 auto;
        flex-direction: column;
        gap: .35rem;
        overflow-y: auto;
        padding: 1.25rem clamp(.75rem, 4vw, 2rem);
        background-color: var(--chat-wallpaper);
        background-image: radial-gradient(rgba(134, 150, 160, .075) .7px, transparent .7px);
        background-size: 17px 17px;
    }
    .whatsapp-message-row { display: flex; justify-content: flex-start; }
    .whatsapp-message-row.is-mine { justify-content: flex-end; }
    .whatsapp-message {
        position: relative;
        max-width: min(78%, 620px);
        min-width: 72px;
        padding: .45rem .6rem .3rem;
        border-radius: .55rem;
        color: #e9edef;
        box-shadow: 0 1px 1px rgba(0, 0, 0, .16);
        overflow-wrap: anywhere;
    }
    .whatsapp-message-theirs { background: var(--chat-message-in); border-top-left-radius: .15rem; }
    .whatsapp-message-mine { background: var(--chat-message-out); border-top-right-radius: .15rem; }
    .whatsapp-message-author {
        margin-bottom: .15rem;
        color: #53bdeb;
        font-size: .76rem;
        font-weight: 600;
    }
    .whatsapp-message-body { white-space: pre-wrap; line-height: 1.4; }
    .whatsapp-message-time {
        display: block;
        margin: .1rem 0 0 .55rem;
        color: rgba(233, 237, 239, .68);
        font-size: .66rem;
        text-align: right;
    }
    .whatsapp-message-attachment {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        margin-top: .3rem;
    }
    .whatsapp-message-attachment a { color: #b8e5d5; text-decoration: none; }
    .whatsapp-chat-empty {
        display: flex;
        flex: 1;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .6rem;
        color: #aebac1;
        text-align: center;
    }
    .whatsapp-chat-empty i { font-size: 2rem; }
    .whatsapp-chat-composer {
        flex: 0 0 auto;
        padding: .65rem clamp(.6rem, 2vw, 1rem);
        background: var(--chat-composer);
        border-top: 1px solid rgba(134, 150, 160, .12);
    }
    .whatsapp-compose-form { display: flex; align-items: flex-end; gap: .55rem; }
    .whatsapp-attach-button, .whatsapp-send-button {
        display: grid;
        flex: 0 0 42px;
        width: 42px;
        height: 42px;
        place-items: center;
        border: 0;
        border-radius: 50%;
        color: #aebac1;
        cursor: pointer;
        font-size: 1.2rem;
    }
    .whatsapp-attach-button:hover { color: #e9edef; background: rgba(255, 255, 255, .08); }
    .whatsapp-compose-input {
        min-height: 42px;
        max-height: 120px;
        resize: vertical;
        border: 0;
        border-radius: 1.3rem;
        padding: .65rem 1rem;
        color: #e9edef;
        background: #2a3942;
    }
    .whatsapp-compose-input:focus { color: #e9edef; background: #2a3942; box-shadow: none; }
    .whatsapp-compose-input::placeholder { color: #8696a0; }
    .whatsapp-send-button { color: #fff; background: #00a884; }
    .whatsapp-send-button:hover { background: #06cf9b; }
    .whatsapp-attachment-error { padding-left: 3rem; }
    .whatsapp-upload-name { padding: .2rem 0 0 3rem; color: #aebac1; }
    @media (max-width: 575.98px) {
        .whatsapp-chat { height: calc(100dvh - 220px); min-height: 380px; }
        .whatsapp-message { max-width: 88%; }
    }
</style>
@endpush

@push('scripts')
<script>
// Scroll to bottom of thread on load.
$(function () {
    var t = document.getElementById('messageThread');
    if (t) { t.scrollTop = t.scrollHeight; }

    var attachment = document.getElementById('groupReplyAttachment');
    var attachmentName = document.getElementById('groupReplyAttachmentName');
    if (attachment && attachmentName) {
        attachment.addEventListener('change', function () {
            attachmentName.textContent = this.files && this.files.length ? this.files[0].name : '';
        });
    }
});
</script>
@endpush
