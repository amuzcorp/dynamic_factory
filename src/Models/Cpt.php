<?php
namespace Overcode\XePlugin\DynamicFactory\Models;

use Xpressengine\Database\Eloquent\DynamicModel;

class Cpt extends DynamicModel
{
    protected $table = 'df_cpt';

    protected $fillable = ['menu_id','obj_name','menu_name','menu_order','description','slug','editor','edit_section'];

    public $incrementing = false;
}
