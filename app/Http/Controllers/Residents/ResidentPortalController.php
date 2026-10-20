<?php

declare(strict_types=1);

namespace App\Http\Controllers\Residents;

use App\Http\Controllers\Controller;
use App\Models\Broadcast;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\MaintenanceBill;
use App\Models\MaintenanceBillDocument;
use App\Models\Notice;
use App\Models\User;
use App\Services\Communication\CommunicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResidentPortalController extends Controller
{
    public function __construct(protected CommunicationService $communication) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $this->ensureResident($user);

        $isOwner = $user->hasRole('resident');
        $bills = feature_enabled('billing')
            ? MaintenanceBill::query()
                ->where('society_id', $user->society_id)
                ->where('user_id', $user->id)
                ->where('status', '!=', 'cancelled')
                ->latest('period')
                ->limit(5)
                ->get()
            : collect();

        $openBillsCount = feature_enabled('billing')
            ? MaintenanceBill::query()
                ->where('society_id', $user->society_id)
                ->where('user_id', $user->id)
                ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                ->count()
            : 0;

        $documents = feature_enabled('billing')
            ? MaintenanceBillDocument::query()
                ->where('society_id', $user->society_id)
                ->where('resident_user_id', $user->id)
                ->where('status', 'available')
                ->latest('published_at')
                ->limit(5)
                ->get()
            : collect();

        $notices = feature_enabled('notices')
            ? Notice::query()
                ->published()
                ->whereIn('audience', array_values(array_filter([
                    'all',
                    $isOwner ? 'owners' : 'tenants',
                    'residents',
                ])))
                ->latest('published_at')
                ->limit(5)
                ->get()
            : collect();

        $broadcasts = feature_enabled('communication')
            ? Broadcast::query()
                ->where('status', 'sent')
                ->whereHas('recipients', fn ($query) => $query
                    ->where('user_id', $user->id)
                    ->whereIn('status', ['sent', 'read']))
                ->latest('sent_at')
                ->limit(5)
                ->get()
            : collect();

        $conversationParticipants = feature_enabled('communication')
            ? ConversationParticipant::query()
                ->where('user_id', $user->id)
                ->with(['conversation.participants.participant', 'conversation.latestMessage.author'])
                ->get()
            : collect();

        $conversations = $conversationParticipants
            ->pluck('conversation')
            ->filter()
            ->sortByDesc('last_message_at')
            ->take(5)
            ->values();

        $unreadMessages = $conversationParticipants->filter(function (ConversationParticipant $participant) use ($user): bool {
            $latest = $participant->conversation?->latestMessage;

            return $latest
                && (int) $latest->user_id !== (int) $user->id
                && (! $participant->last_read_at || $latest->created_at->gt($participant->last_read_at));
        })->count();

        return view('residents.portal', compact(
            'bills',
            'openBillsCount',
            'documents',
            'notices',
            'broadcasts',
            'conversations',
            'unreadMessages',
        ));
    }

    public function messages(Request $request): View
    {
        $user = $request->user();
        $this->ensureResident($user);

        $conversations = Conversation::query()
            ->whereHas('participants', fn ($query) => $query->where('user_id', $user->id))
            ->with(['participants.participant', 'latestMessage.author'])
            ->latest('last_message_at')
            ->paginate(15);

        $managers = User::query()
            ->where('society_id', $user->society_id)
            ->whereHas('roles', fn ($query) => $query->whereIn('slug', ['society-admin', 'sub-admin']))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('residents.portal-messages', compact('conversations', 'managers'));
    }

    public function sendMessage(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->ensureResident($user);

        $data = $request->validate([
            'recipient_id' => ['required', 'integer'],
            'subject' => ['nullable', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $manager = User::query()
            ->where('society_id', $user->society_id)
            ->whereKey($data['recipient_id'])
            ->whereHas('roles', fn ($query) => $query->whereIn('slug', ['society-admin', 'sub-admin']))
            ->firstOrFail();

        $conversation = $this->communication->startConversation(
            [$manager->id],
            $data['subject'] ?? null,
            $data['body'],
            $user->id,
        );

        return redirect()->route('communication.messages.show', $conversation)
            ->with('success', 'Your message has been sent to society management.');
    }

    private function ensureResident(User $user): void
    {
        abort_unless($user->hasAnyRole(['resident', 'tenant']), 403);
        abort_unless($user->society_id, 403);
    }
}
