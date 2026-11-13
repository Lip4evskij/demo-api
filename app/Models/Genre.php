<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $link
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder<static>|Genre newModelQuery()
 * @method static Builder<static>|Genre newQuery()
 * @method static Builder<static>|Genre query()
 * @method static Builder<static>|Genre whereCreatedAt($value)
 * @method static Builder<static>|Genre whereId($value)
 * @method static Builder<static>|Genre whereLink($value)
 * @method static Builder<static>|Genre whereName($value)
 * @method static Builder<static>|Genre whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Genre extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'link',
    ];
}
