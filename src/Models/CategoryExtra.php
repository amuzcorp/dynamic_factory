<?php
namespace Overcode\XePlugin\DynamicFactory\Models;

use Xpressengine\Database\Eloquent\DynamicModel;

class CategoryExtra extends DynamicModel
{
    protected $table = 'df_category_extra';

    protected $fillable = ['category_id', 'slug', 'template'];

    public $primaryKey = 'category_id';

    public $timestamps = false;
}
