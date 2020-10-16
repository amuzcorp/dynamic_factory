<?php
namespace Overcode\XePlugin\DynamicFactory\Models;

use Xpressengine\Database\Eloquent\DynamicModel;

class Label extends DynamicModel
{
    protected $table = 'df_label';

    protected $fillable = ['target_id','new_add','new_add_obj','obj_edit','new_obj','obj_search','no_search','no_trash','parent_txt','all_obj','here_title_input'];
}
