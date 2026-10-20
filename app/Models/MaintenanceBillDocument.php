<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceBillDocument extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'society_id',
        'flat_id',
        'resident_user_id',
        'uploaded_by',
        'supersedes_id',
        'period',
        'reference_number',
        'description',
        'storage_path',
        'original_filename',
        'file_size',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function flat(): BelongsTo
    {
        return $this->belongsTo(Flat::class)->withoutGlobalScopes();
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resident_user_id')->withoutGlobalScopes();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by')->withoutGlobalScopes();
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_id');
    }
}
