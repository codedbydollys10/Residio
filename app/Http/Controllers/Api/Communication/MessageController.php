<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Communication;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Communication\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use App\Services\Communication\CommunicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class MessageController extends Controller
{
    use ApiResponse;

    public function __construct(protected CommunicationService $service) {}

    public function inbox(): JsonResponse
    {
        $userId = auth()->id();

        $conversations = Conversation::whereHas(
            'participants',
            fn ($q) => $q->where('user_id', $userId)
        )
            ->with(['creator', 'participants.participant'])
            ->latest('last_message_at')
            ->get();

        return $this->ok($conversations);
    }

    public function show(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $conversation->load(['messages.author', 'participants.participant']);

        ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', auth()->id())
            ->update(['last_read_at' => now()]);

        return $this->ok($conversation);
    }

    public function store(StoreMessageRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (! empty($data['conversation_id'])) {
            $conversation = Conversation::findOrFail($data['conversation_id']);
            $this->authorize('view', $conversation);
            $message = $this->service->postMessage($conversation, auth()->id(), $data['body']);

            return $this->created($message, 'Reply sent.');
        }

        $participantIds = array_values(array_unique(array_map('intval', $data['participant_ids'] ?? [])));
        if ($participantIds === []) {
            throw ValidationException::withMessages([
                'participant_ids' => 'Choose a recipient to start a conversation.',
            ]);
        }

        $user = $request->user();
        $recipients = User::query()
            ->where('society_id', $user->society_id)
            ->whereIn('id', $participantIds);

        if ($user->hasAnyRole(['resident', 'tenant'])) {
            $recipients->whereHas('roles', fn ($roles) => $roles->whereIn('slug', ['society-admin', 'sub-admin']));
        } else {
            $this->authorize('create', Conversation::class);
        }

        abort_unless($recipients->count() === count($participantIds), 404);

        $conversation = $this->service->startConversation(
            $participantIds,
            $data['subject'] ?? null,
            $data['body'],
            $user->id,
        );

        return $this->created($conversation, 'Conversation started.');
    }
}
