<?php
namespace Overcode\XePlugin\DynamicFactory\Models;

use Xpressengine\Database\Eloquent\DynamicModel;

class Cpt extends DynamicModel
{
    protected $table = 'df_cpt';

    protected $fillable = ['menu_id','menu_order','label','description','slug','editor'];

    protected $primaryKey = 'id';
}
