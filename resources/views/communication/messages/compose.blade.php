@extends('layouts.app')
@section('title', 'Message '.$recipient->name)
@section('page-title', 'Message '.$recipient->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('communication.index') }}">Communication</a></li>
    <li class="breadcrumb-item"><a href="{{ route('communication.messages.index') }}">Messages</a></li>
    <li class="breadcrumb-item active">New direct message</li>
@endsection
@section('content')
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card shadow-sm">
            <div class="card-header bg-transparent">
                <h2 class="h6 mb-0">To: {{ $recipient->name }}</h2>
                <div class="small text-muted">{{ $recipient->email }}</div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('communication.messages.direct') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="recipient_id" value="{{ $recipient->id }}">
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input id="subject" name="subject" class="form-control" maxlength="180" value="{{ old('subject', 'Message from your society') }}">
                    </div>
                    <div class="mb-3">
                        <label for="messageBody" class="form-label">Message</label>
                        <textarea id="messageBody" name="body" rows="5" maxlength="2000" class="form-control" required>{{ old('body') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label for="billDocument" class="form-label">Attach published maintenance bill <span class="text-muted">(optional)</span></label>
                        <select id="billDocument" name="maintenance_bill_document_id" class="form-select">
                            <option value="">No bill attachment</option>
                            @foreach ($documents as $document)
                                <option value="{{ $document->id }}" @selected(old('maintenance_bill_document_id', $selectedDocument) == $document->id)>
                                    {{ $document->period }} · {{ $document->original_filename }}
                                </option>
                            @endforeach
                        </select>
                        @if ($documents->isEmpty())
                            <div class="form-text">No available published bills for this resident yet. You can still send a message.</div>
                        @endif
                    </div>
                    <div class="mb-3">
                        <label for="messageAttachment" class="form-label">Upload a bill or file <span class="text-muted">(optional)</span></label>
                        <input id="messageAttachment" name="attachment" type="file" class="form-control"
                               accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp">
                        <div class="form-text">PDF, JPG, PNG, or WEBP; up to {{ (int) config('communityos.communication.attachment_max_kb', 10240) / 1024 }} MB.</div>
                        @error('attachment') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('maintenance.documents.directory') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i> Send message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
