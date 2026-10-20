@extends('layouts.app')
@section('title', 'Bill PDF History')
@section('page-title', 'Bill PDF History')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('maintenance.index') }}">Maintenance Management</a></li>
    <li class="breadcrumb-item"><a href="{{ route('maintenance.documents.directory') }}">Flat directory</a></li>
    <li class="breadcrumb-item active">{{ $flat->label }}</li>
@endsection
@section('content')
<div class="card shadow-sm">
    <div class="card-header bg-transparent"><h2 class="h6 mb-0">{{ $flat->label }} · {{ $flat->tower?->name }}</h2></div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead><tr><th>Period</th><th>Resident</th><th>Bill file</th><th>Reference</th><th>Published</th><th>Uploaded by</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse ($documents as $document)
            <tr>
                <td>{{ $document->period }}</td><td>{{ $document->resident?->name ?? '—' }}</td>
                <td>{{ $document->original_filename }}</td><td>{{ $document->reference_number ?: '—' }}</td>
                <td>{{ $document->published_at?->format('d M Y H:i') }}</td><td>{{ $document->uploader?->name ?? '—' }}</td>
                <td><span class="badge text-bg-{{ $document->status === 'available' ? 'success' : 'secondary' }}">{{ ucfirst($document->status) }}</span></td>
                <td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="{{ route('maintenance.documents.preview', $document) }}" target="_blank" rel="noopener">View</a> <a class="btn btn-sm btn-outline-secondary" href="{{ route('maintenance.documents.download', $document) }}">Download</a></td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted py-4">No bill PDFs have been published for this flat.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="card-body">{{ $documents->links() }}</div>
</div>
@endsection
