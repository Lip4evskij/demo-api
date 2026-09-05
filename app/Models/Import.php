<?php

namespace App\Models;

use Database\Factories\ImportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Import extends Model
{
    /** @use HasFactory<ImportFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'supplier_id',
        'external_import_id',
        'sent_at',
        'status',
        'raw_payload',
        'total_offers',
        'processed_offers',
        'error',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'raw_payload' => 'array',
            'total_offers' => 'integer',
            'processed_offers' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
