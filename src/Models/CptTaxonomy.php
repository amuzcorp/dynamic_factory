<?php
namespace Overcode\XePlugin\DynamicFactory\Models;

use Xpressengine\Category\Models\Category;
use Xpressengine\Database\Eloquent\DynamicModel;

class CptTaxonomy extends DynamicModel
{
    protected $table = 'df_cpt_taxonomy';

    protected $fillable = ['site_key','cpt_id', 'category_id'];

    public $timestamps = false;

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }
}
