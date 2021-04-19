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

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function extra(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CategoryExtra::class, 'category_id','category_id');
    }

    public function getItemsByDocumentId(){
        $group = 'tax_' . $this->slug;
        $dynamicFieldHandler = app('xe.dynamicField');
        $dynamicFields = $dynamicFieldHandler->gets($group);

        $query = CategoryItem::getQuery()->whereIn('id',$this->item_ids);
        foreach($dynamicFields as $field_name => $field) df($group, $field_name)->get($query);

        $collection = CategoryItem::setQuery($query);
        return $collection->get();
    }

    /*public function taxonomyItem()
    {
        return $this->belongsTo(CategoryItem::class, 'item_id');
    }*/
}
