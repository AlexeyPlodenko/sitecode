<?php declare(strict_types=1);

namespace Alexeyplodenko\Sitecode\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $title
 * @property string $content
 *
 * @mixin Eloquent
 */
class SharedContent extends Model
{
    protected $table = 'shared_content';

    protected $fillable = [
        'title',
        'content',
    ];
}
