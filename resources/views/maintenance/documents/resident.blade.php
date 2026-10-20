@extends('layouts.app')
@section('title', 'My Maintenance Bills')
@section('page-title', 'My Maintenance Bills')
@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        @if ($documents->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-file-earmark-pdf display-5 text-muted"></i>
                <h2 class="h5 mt-3">No maintenance PDFs yet</h2>
                <p class="text-muted mb-0">Your society's published bill documents for your account will appear here.</p>
            </div>
        @else
            <div class="table-responsive"><table class="table table-hover align-middle">
                <thead><tr><th>Billing period</th><th>Flat</th><th>Bill document</th><th>Reference</th><th>Published</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                @foreach ($documents as $document)
                    <tr>
                        <td class="fw-semibold">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $document->period)->format('F Y') }}</td>
                        <td>{{ $document->flat?->label ?? $document->flat?->number ?? '—' }}</td>
                        <td><i class="bi bi-file-earmark-pdf text-danger me-1"></i>{{ $document->original_filename }}</td>
                        <td>{{ $document->reference_number ?: '—' }}</td>
                        <td>{{ $document->published_at?->format('d M Y') }}</td>
                        <td><span class="badge text-bg-{{ $document->status === 'available' ? 'success' : 'secondary' }}">{{ ucfirst($document->status) }}</span></td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('resident.bills.preview', $document) }}" target="_blank" rel="noopener">View PDF</a>
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('resident.bills.download', $document) }}">Download</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            {{ $documents->links() }}
        @endif
    </div>
</div>
@php
    $contactPhone = $secretary?->phone ?: $society?->phone;
    $contactEmail = $secretary?->email ?: $society?->email;
@endphp
@if ($contactPhone || $contactEmail || feature_enabled('communication'))
    <div class="mt-3">
        <span class="text-muted me-2">Contact your society secretary:</span>
        @if (feature_enabled('communication'))
            <a class="btn btn-sm btn-outline-primary" href="{{ route('resident.portal.messages') }}"><i class="bi bi-chat-dots me-1"></i>Message in portal</a>
        @endif
        @if ($contactPhone)
            @php $secretaryPhone = preg_replace('/\D+/', '', $contactPhone); @endphp
            @if (strlen($secretaryPhone) >= 6 && strlen($secretaryPhone) <= 15)<a class="btn btn-sm btn-outline-secondary" href="tel:{{ str_starts_with(trim($contactPhone), '+') ? '+' : '' }}{{ $secretaryPhone }}"><i class="bi bi-telephone me-1"></i>Call</a>@endif
        @endif
        @if ($contactEmail)<a class="btn btn-sm btn-outline-secondary" href="mailto:{{ $contactEmail }}"><i class="bi bi-envelope me-1"></i>Message</a>@endif
    </div>
@endif
@endsection
