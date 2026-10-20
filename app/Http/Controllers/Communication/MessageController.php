<?php

declare(strict_types=1);

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Communication\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\MaintenanceBillDocument;
use App\Models\Message;
use App\Models\User;
use App\Services\Communication\CommunicationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\View\View;
use Throwable;

class MessageController extends Controller
{
    public function __construct(protected CommunicationService $service) {}

    public function inbox(): View|RedirectResponse
    {
        if (auth()->user()->hasAnyRole(['resident', 'tenant'])) {
            return redirect()->route('resident.portal.messages');
        }

        $userId = auth()->id();

        $conversations = Conversation::whereHas(
            'participants',
            fn ($q) => $q->where('user_id', $userId)
        )
            ->with(['creator', 'participants.participant'])
            ->latest('last_message_at')
            ->get();

        $users = User::query()
            ->where('society_id', auth()->user()->society_id)
            ->where('id', '!=', $userId)
            ->whereHas('roles')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('communication.messages.inbox', compact('conversations', 'users'));
    }

    public function show(Conversation $conversation): View
    {
        $this->authorize('view', $conversation);

        $conversation->load([
            'messages' => fn ($query) => $conversation->tower_id
                ? $query->reorder()->orderBy('created_at')->orderBy('id')
                : $query,
            'messages.author',
            'messages.maintenanceBillDocument',
            'participants.participant',
        ]);

        // Mark as read for current user.
        ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', auth()->id())
            ->update(['last_read_at' => now()]);

        return view('communication.messages.show', compact('conversation'));
    }

    public function store(StoreMessageRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (! empty($data['conversation_id'])) {
            // Reply to existing conversation.
            $conversation = Conversation::findOrFail($data['conversation_id']);
            $this->authorize('view', $conversation);
            $document = $this->resolveMessageDocument($data['maintenance_bill_document_id'] ?? null, $conversation);
            $attachment = $this->storeAttachment($request->file('attachment'), $request->user());
            try {
                $this->service->postMessage($conversation, auth()->id(), $data['body'], $document?->id, $attachment);
            } catch (Throwable $exception) {
                $this->deleteAttachment($attachment);
                throw $exception;
            }

            return redirect()->route('communication.messages.show', $conversation)
                ->with('success', 'Reply sent.');
        }

        $this->authorize('create', Conversation::class);

        // Start a new conversation.
        $participantIds = $data['participant_ids'] ?? [];
        if ($participantIds !== []) {
            $recipients = User::query()
                ->where('society_id', $request->user()->society_id)
                ->whereIn('id', $participantIds);

            if ($request->user()->hasAnyRole(['resident', 'tenant'])) {
                $recipients->whereHas('roles', fn ($roles) => $roles->whereIn('slug', ['society-admin', 'sub-admin']));
            }

            abort_unless($recipients->count() === count(array_unique($participantIds)), 404);
        }

        $document = $this->resolveMessageDocument($data['maintenance_bill_document_id'] ?? null);
        $attachment = $this->storeAttachment($request->file('attachment'), $request->user());
        try {
            $conversation = $this->service->startConversation(
                $participantIds,
                $data['subject'] ?? null,
                $data['body'],
                auth()->id(),
                $document?->id,
                $attachment,
            );
        } catch (Throwable $exception) {
            $this->deleteAttachment($attachment);
            throw $exception;
        }

        return redirect()->route('communication.messages.show', $conversation)
            ->with('success', 'Conversation started.');
    }

    public function compose(Request $request): View
    {
        $this->authorize('create', Conversation::class);
        $recipient = User::query()
            ->where('society_id', $request->user()->society_id)
            ->whereKey($request->query('recipient_id'))
            ->whereHas('roles', fn ($roles) => $roles->whereIn('slug', ['resident', 'tenant']))
            ->firstOrFail();

        return view('communication.messages.compose', [
            'recipient' => $recipient,
            'documents' => MaintenanceBillDocument::query()
                ->where('society_id', $request->user()->society_id)
                ->where('resident_user_id', $recipient->id)
                ->where('status', 'available')
                ->latest('published_at')
                ->get(),
            'selectedDocument' => $request->query('document_id'),
        ]);
    }

    public function startDirect(Request $request): RedirectResponse
    {
        $this->authorize('create', Conversation::class);
        $data = $request->validate([
            'recipient_id' => ['required', 'integer'],
            'subject' => ['nullable', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:2000'],
            'maintenance_bill_document_id' => ['nullable', 'integer'],
            'attachment' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'extensions:pdf,jpg,jpeg,png,webp',
                'max:'.(int) config('communityos.communication.attachment_max_kb', 10240),
            ],
        ]);
        $recipient = User::query()
            ->where('society_id', $request->user()->society_id)
            ->whereKey($data['recipient_id'])
            ->whereHas('roles', fn ($roles) => $roles->whereIn('slug', ['resident', 'tenant']))
            ->firstOrFail();
        $document = $this->resolveMessageDocument($data['maintenance_bill_document_id'] ?? null);
        abort_if($document && (int) $document->resident_user_id !== (int) $recipient->id, 403);

        $attachment = $this->storeAttachment($request->file('attachment'), $request->user());
        try {
            $conversation = $this->service->startConversation(
                [$recipient->id],
                $data['subject'] ?? null,
                $data['body'],
                $request->user()->id,
                $document?->id,
                $attachment,
            );
        } catch (Throwable $exception) {
            $this->deleteAttachment($attachment);
            throw $exception;
        }

        return redirect()->route('communication.messages.show', $conversation)
            ->with('success', 'Message sent.');
    }

    private function resolveMessageDocument(?int $documentId, ?Conversation $conversation = null): ?MaintenanceBillDocument
    {
        if (! $documentId) {
            return null;
        }

        $document = MaintenanceBillDocument::query()
            ->where('society_id', auth()->user()->society_id)
            ->where('status', 'available')
            ->findOrFail($documentId);

        if ($conversation) {
            $hasRecipient = $conversation->participants()
                ->where('user_id', $document->resident_user_id)
                ->exists();
            abort_unless($hasRecipient, 403);
            $hasOtherRecipients = $conversation->participants()
                ->whereNotIn('user_id', [$document->resident_user_id, auth()->id()])
                ->exists();
            abort_if($hasOtherRecipients, 403);
        }

        return $document;
    }

    public function previewAttachment(Request $request, Message $message): BinaryFileResponse
    {
        $this->authorize('view', $message->conversation);
        $this->authorizeAttachmentAccess($request, $message);
        abort_unless(Storage::disk('local')->exists($message->attachment_path), 404);

        $filename = $this->safeAttachmentFilename($message->attachment_name);

        return response()->file(Storage::disk('local')->path($message->attachment_path), [
            'Content-Type' => $message->attachment_mime,
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function downloadAttachment(Request $request, Message $message)
    {
        $this->authorize('view', $message->conversation);
        $this->authorizeAttachmentAccess($request, $message);
        abort_unless(Storage::disk('local')->exists($message->attachment_path), 404);

        return Storage::disk('local')->download($message->attachment_path, $this->safeAttachmentFilename($message->attachment_name), [
            'Content-Type' => $message->attachment_mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function storeAttachment(?UploadedFile $file, User $user): ?array
    {
        if (! $file) {
            return null;
        }

        $path = $file->store("message-attachments/{$user->society_id}", 'local');
        if (! $path) {
            throw ValidationException::withMessages([
                'attachment' => 'The attachment could not be stored. Please try again.',
            ]);
        }

        return [
            'path' => $path,
            'name' => basename($file->getClientOriginalName()),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ];
    }

    private function deleteAttachment(?array $attachment): void
    {
        if ($attachment !== null) {
            Storage::disk('local')->delete($attachment['path']);
        }
    }

    private function authorizeAttachmentAccess(Request $request, Message $message): void
    {
        abort_unless(
            $message->attachment_path
                && (int) $request->user()->society_id === (int) $message->society_id
                && in_array($message->attachment_mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true),
            404,
        );
    }

    private function safeAttachmentFilename(?string $filename): string
    {
        $extension = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));
        $basename = Str::slug(pathinfo((string) $filename, PATHINFO_FILENAME)) ?: 'message-attachment';

        return $basename.($extension !== '' ? '.'.$extension : '');
    }
}
