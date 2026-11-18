@extends('layouts.app')
@section('title', 'My Maintenance Bills')
@section('page-title', 'My Maintenance Bills')
@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        @if ($bills->isEmpty() && $documents->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-file-earmark-pdf display-5 text-muted"></i>
                <h2 class="h5 mt-3">No maintenance bills yet</h2>
                <p class="text-muted mb-0">Bills generated for your flat and documents published by your society will appear here.</p>
            </div>
        @endif

        @if ($bills->isNotEmpty())
            <h2 class="h5 mb-3">Maintenance bills</h2>
            <div class="table-responsive"><table class="table table-hover align-middle">
                <thead><tr><th>Bill number</th><th>Billing period</th><th>Flat</th><th>Due date</th><th>Total</th><th>Balance</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                @foreach ($bills as $bill)
                    @php
                        $statusColors = [
                            'unpaid' => 'warning',
                            'partial' => 'info',
                            'paid' => 'success',
                            'overdue' => 'danger',
                            'cancelled' => 'secondary',
                        ];
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $bill->bill_number }}</td>
                        <td>{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $bill->period)->format('F Y') }}</td>
                        <td>{{ $bill->flat?->label ?? $bill->flat?->number ?? '—' }}</td>
                        <td>{{ $bill->due_date?->format('d M Y') ?? '—' }}</td>
                        <td>{{ money($bill->total) }}</td>
                        <td class="{{ $bill->balance > 0 ? 'text-danger fw-semibold' : 'text-success' }}">{{ money($bill->balance) }}</td>
                        <td>
                            <span class="badge text-bg-{{ $statusColors[$bill->status] ?? 'secondary' }} text-capitalize">{{ $bill->status }}</span>
                            @if ($bill->paymentSubmissions->contains('status', 'pending'))
                                <span class="badge text-bg-info">Payment under review</span>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('maintenance.bills.show', $bill) }}">View bill</a>
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('resident.bills.generated.download', $bill) }}">Download PDF</a>
                            @if ($bill->balance > 0 && !in_array($bill->status, ['paid', 'cancelled']))
                                <a class="btn btn-sm btn-outline-success" href="{{ route('maintenance.bills.show', $bill) }}#report-payment">Pay / report transfer</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            {{ $bills->links() }}
        @endif

        @if ($documents->isNotEmpty())
            <h2 class="h5 mt-4 mb-3">Published bill documents</h2>
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
