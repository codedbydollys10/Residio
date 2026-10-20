<?php

declare(strict_types=1);

namespace App\Notifications\Maintenance;

use App\Models\MaintenanceBillDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BillDocumentPublished extends Notification
{
    use Queueable;

    public function __construct(private readonly MaintenanceBillDocument $document) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'maintenance_bill_document',
            'message' => 'A maintenance bill PDF for '.$this->document->period.' is available.',
            'period' => $this->document->period,
            'document_id' => $this->document->id,
            'url' => route('resident.bills.index'),
        ];
    }
}
