<?php
namespace Overcode\XePlugin\DynamicFactory\Models;

use Xpressengine\Database\Eloquent\DynamicModel;

/**
 * @property int favorite_id
 * @property string target_id
 * @property string user_id
 */
class DfFavorite extends DynamicModel
{
    public $timestamps = false;

    protected $primaryKey = 'favorite_id';

    protected $fillable = ['target_id', 'user_id'];
}
