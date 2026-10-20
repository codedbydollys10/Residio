<?php

declare(strict_types=1);

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Tower;
use App\Models\User;
use App\Services\Communication\BuildingGroupMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BuildingGroupController extends Controller
{
    public function __construct(private BuildingGroupMembership $membership) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($this->isManager($user) || $this->isResident($user), 403);

        if ($this->isManager($user)) {
            $buildings = Tower::query()
                ->where('society_id', $user->society_id)
                ->where('status', 'active')
                ->orderBy('name')
                ->get();
        } else {
            $buildings = Tower::query()
                ->where('society_id', $user->society_id)
                ->where('status', 'active')
                ->whereIn('id', $this->membership->towerIdsFor($user))
                ->orderBy('name')
                ->get();
        }

        $buildings->each(fn (Tower $building) => $this->synchronizeGroup($building));

        return view('communication.building-groups.index', compact('buildings'));
    }

    public function open(Request $request, int $towerId): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->isManager($user) || $this->isResident($user), 403);

        $tower = Tower::query()
            ->where('society_id', $user->society_id)
            ->where('status', 'active')
            ->findOrFail($towerId);

        abort_unless($this->isManager($user) || $this->membership->belongsTo($user, $tower), 403);

        $conversation = $this->synchronizeGroup($tower);

        return redirect()->route('communication.messages.show', $conversation);
    }

    private function synchronizeGroup(Tower $tower): Conversation
    {
        $managers = User::query()
            ->where('society_id', $tower->society_id)
            ->where('status', 'active')
            ->whereHas('roles', fn ($query) => $query->whereIn('slug', ['society-admin', 'sub-admin']))
            ->orderBy('name')
            ->get();

        abort_if($managers->isEmpty(), 403, 'A society secretary or administrator must be assigned before the group can be opened.');

        $secretary = $managers->first(
            fn (User $manager) => str_contains(strtolower((string) $manager->designation), 'secretary')
        ) ?? $managers->first(fn (User $manager) => $manager->hasRole('society-admin'))
            ?? $managers->first();

        $membership = $this->membership;
        return DB::transaction(function () use ($tower, $secretary, $managers, $membership): Conversation {
            $conversation = Conversation::firstOrCreate(
                ['society_id' => $tower->society_id, 'tower_id' => $tower->id],
                [
                    'subject' => $tower->name.' Building Group',
                    'type' => 'group',
                    'created_by' => $secretary->id,
                    'group_admin_id' => $secretary->id,
                ],
            );

            if ((int) $conversation->group_admin_id !== (int) $secretary->id) {
                $conversation->update(['group_admin_id' => $secretary->id]);
            }

            $residentIds = $membership->userIds($tower);

            $participantIds = $residentIds
                ->merge($managers->pluck('id'))
                ->push($secretary->id)
                ->unique()
                ->values();

            ConversationParticipant::query()
                ->where('conversation_id', $conversation->id)
                ->whereNotIn('user_id', $participantIds)
                ->delete();

            $existingIds = ConversationParticipant::query()
                ->where('conversation_id', $conversation->id)
                ->pluck('user_id');
            $now = now();
            $newParticipants = $participantIds
                ->diff($existingIds)
                ->map(fn (int $participantId) => [
                    'society_id' => $tower->society_id,
                    'conversation_id' => $conversation->id,
                    'user_id' => $participantId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all();

            if ($newParticipants !== []) {
                ConversationParticipant::insertOrIgnore($newParticipants);
            }

            return $conversation;
        });
    }

    private function isManager(User $user): bool
    {
        return $user->hasAnyRole(['society-admin', 'sub-admin']);
    }

    private function isResident(User $user): bool
    {
        return $user->hasAnyRole(['resident', 'tenant', 'family-member']) && $user->society_id !== null;
    }

}
