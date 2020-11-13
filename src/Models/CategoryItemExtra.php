<?php
namespace Overcode\XePlugin\DynamicFactory\Models;


use Xpressengine\Category\Models\CategoryItem;
use Xpressengine\Database\Eloquent\DynamicModel;

class CategoryItemExtra extends DynamicModel
{
    protected $table = 'df_category_item_extra';

    protected $fillable = ['item_id', 'slug'];

    public $primaryKey = 'item_id';

    public $timestamps = false;

    public function categoryItem()
    {
        return $this->hasOne(CategoryItem::class, 'id', 'item_id');
    }
}
