<?php

declare(strict_types=1);

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\Flat;
use App\Models\MaintenanceBillDocument;
use App\Models\Society;
use App\Models\Tower;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MaintenanceBillDocumentController extends Controller
{
    public function directory(Request $request): View
    {
        abort_unless($request->user()->can('maintenance.create'), 403);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'tower_id' => ['nullable', 'integer'],
            'period' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'upload_status' => ['nullable', 'in:uploaded,pending'],
        ]);
        $period = $filters['period'] ?? now()->format('Y-m');
        $societyId = $this->societyId($request);
        $flats = Flat::query()
            ->with(['owner', 'tower', 'residents.user'])
            ->where('society_id', $societyId)
            ->when($filters['tower_id'] ?? null, fn ($query, $towerId) => $query->where('tower_id', $towerId))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $term = '%'.$search.'%';
                $query->where(function ($query) use ($term) {
                    $query->where('number', 'like', $term)
                        ->orWhereHas('owner', fn ($owner) => $owner->where('name', 'like', $term)->orWhere('phone', 'like', $term))
                        ->orWhereHas('residents', fn ($resident) => $resident->whereIn('type', ['owner', 'tenant'])->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('phone', 'like', $term)));
                });
            })
            ->withCount(['maintenanceBillDocuments as available_documents_count' => fn ($query) => $query->where('period', $period)->where('status', 'available')])
            ->withMax(['maintenanceBillDocuments as latest_upload_at' => fn ($query) => $query->where('period', $period)], 'published_at')
            ->when(($filters['upload_status'] ?? null) === 'uploaded', fn ($query) => $query->whereHas('maintenanceBillDocuments', fn ($docs) => $docs->where('period', $period)->where('status', 'available')))
            ->when(($filters['upload_status'] ?? null) === 'pending', fn ($query) => $query->whereDoesntHave('maintenanceBillDocuments', fn ($docs) => $docs->where('period', $period)->where('status', 'available')))
            ->orderBy('number')
            ->paginate(25)
            ->withQueryString();

        return view('maintenance.documents.directory', [
            'flats' => $flats,
            'towers' => Tower::query()->where('society_id', $societyId)->orderBy('name')->get(),
            'period' => $period,
        ]);
    }

    public function history(Request $request, int $flatId): View
    {
        abort_unless($request->user()->can('maintenance.create'), 403);
        $societyId = $this->societyId($request);
        $flat = Flat::query()->where('society_id', $societyId)->with('tower')->findOrFail($flatId);
        $documents = MaintenanceBillDocument::query()
            ->where('society_id', $societyId)
            ->where('flat_id', $flat->id)
            ->with(['resident', 'uploader'])
            ->latest('published_at')
            ->paginate(20);

        return view('maintenance.documents.history', compact('flat', 'documents'));
    }

    public function preview(Request $request, MaintenanceBillDocument $document): BinaryFileResponse
    {
        $this->authorizeDocumentAccess($request, $document);
        abort_unless(Storage::disk('local')->exists($document->storage_path), 404);

        return response()->file(Storage::disk('local')->path($document->storage_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.Str::slug(pathinfo($document->original_filename, PATHINFO_FILENAME)).'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function download(Request $request, MaintenanceBillDocument $document)
    {
        $this->authorizeDocumentAccess($request, $document);
        abort_unless(Storage::disk('local')->exists($document->storage_path), 404);

        return Storage::disk('local')->download($document->storage_path, $document->original_filename, [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function residentBills(Request $request): View
    {
        $user = $request->user();
        $documents = MaintenanceBillDocument::query()
            ->where('society_id', $user->society_id)
            ->where('resident_user_id', $user->id)
            ->where('status', 'available')
            ->with(['flat', 'supersedes'])
            ->latest('published_at')
            ->paginate(15);

        return view('maintenance.documents.resident', [
            'documents' => $documents,
            'secretary' => User::query()
                ->where('society_id', $user->society_id)
                ->whereHas('roles', fn ($roles) => $roles->whereIn('slug', ['society-admin', 'sub-admin']))
                ->orderBy('id')
                ->first(),
            'society' => Society::query()->find($user->society_id),
        ]);
    }

    private function authorizeDocumentAccess(Request $request, MaintenanceBillDocument $document): void
    {
        $isSecretary = $request->user()->can('maintenance.create')
            && (int) $request->user()->society_id === (int) $document->society_id;
        $isRecipient = (int) $request->user()->id === (int) $document->resident_user_id
            && (int) $request->user()->society_id === (int) $document->society_id;
        abort_unless($document->status !== 'cancelled' && ($isSecretary || $isRecipient), 404);
    }

    private function societyId(Request $request): int
    {
        $society = current_society();
        abort_unless($society && (int) $society->id === (int) $request->user()->society_id, 403);

        return (int) $society->id;
    }
}
