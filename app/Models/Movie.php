<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $link
 * @property Carbon|null $processing_started_at
 * @property bool $is_downloaded
 * @property string|null $hash
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder<static>|Movie newModelQuery()
 * @method static Builder<static>|Movie newQuery()
 * @method static Builder<static>|Movie query()
 * @method static Builder<static>|Movie whereCreatedAt($value)
 * @method static Builder<static>|Movie whereHash($value)
 * @method static Builder<static>|Movie whereId($value)
 * @method static Builder<static>|Movie whereIsDownloaded($value)
 * @method static Builder<static>|Movie whereLink($value)
 * @method static Builder<static>|Movie whereName($value)
 * @method static Builder<static>|Movie whereProcessingStartedAt($value)
 * @method static Builder<static>|Movie whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Movie extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'link',
        'processing_started_at',
        'is_downloaded',
        'hash',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_downloaded' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'processing_started_at' => 'datetime',
            'is_downloaded' => 'boolean',
        ];
    }
}
