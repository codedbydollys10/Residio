<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'society_id', 'conversation_id', 'user_id', 'body', 'maintenance_bill_document_id',
        'attachment_path', 'attachment_name', 'attachment_mime', 'attachment_size',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function maintenanceBillDocument(): BelongsTo
    {
        return $this->belongsTo(MaintenanceBillDocument::class)->withoutGlobalScopes();
    }
}
