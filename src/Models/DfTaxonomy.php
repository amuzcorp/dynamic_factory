<?php

namespace Overcode\XePlugin\DynamicFactory\Models;

use Xpressengine\Category\Models\Category;
use Xpressengine\Category\Models\CategoryItem;
use Xpressengine\Database\Eloquent\DynamicModel;

class DfTaxonomy extends DynamicModel
{
    protected $table = 'df_taxonomy';

    public $timestamps = false;

    protected $fillable = ['target_id', 'category_id', 'item_ids'];

    protected $casts = ['item_ids' => 'array'];

    public function taxonomy()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /*public function taxonomyItem()
    {
        return $this->belongsTo(CategoryItem::class, 'item_id');
    }*/
}
