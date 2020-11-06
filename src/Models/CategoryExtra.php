<?php
namespace Overcode\XePlugin\DynamicFactory\Models;

use Xpressengine\Category\Models\Category;
use Xpressengine\Database\Eloquent\DynamicModel;

class CategoryExtra extends DynamicModel
{
    protected $table = 'df_category_extra';

    protected $fillable = ['category_id', 'slug', 'template'];

    public $primaryKey = 'category_id';

    public $timestamps = false;

    public function category()
    {
        return $this->hasOne(Category::class, 'id', 'category_id');
    }

    public function cpt_tax()
    {
        return $this->hasMany(CptTaxonomy::class, 'category_id', 'category_id');
    }
}
