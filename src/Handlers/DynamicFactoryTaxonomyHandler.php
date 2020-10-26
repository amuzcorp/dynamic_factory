<?php

namespace Overcode\XePlugin\DynamicFactory\Handlers;

use Overcode\XePlugin\DynamicFactory\Models\CategoryExtra;
use Overcode\XePlugin\DynamicFactory\Models\CptTaxonomy;

class DynamicFactoryTaxonomyHandler
{
    const TAXONOMY_CONFIG_NAME = 'taxonomy';

    const TAXONOMY_ITEM_CONFIG_NAME = 'taxonomyItem';

    const TAXONOMY_ITEM_ID_ATTRIBUTE_NAME_PREFIX = 'taxonomy_item_id_';

    protected $categoryHandler;

    protected $dfConfigHandler;

    public function __construct()
    {
        $this->categoryHandler = app('xe.category');
        $this->dfConfigHandler = app('overcode.df.configHandler');
    }

    public function createTaxonomy($inputs)
    {
        $category_id = $inputs['category_id'];

        // Todo slug 중복 체크
        // Todo taxonomy 중복 체크

        $cateExtra = new CategoryExtra();

        if(!$category_id) {
            $taxonomyItem = $this->categoryHandler->createCate($inputs);
            $cateExtra->category_id = $taxonomyItem->id;

        }else{
            $category = $this->categoryHandler->cates()->find($category_id);
            $category->name = $inputs['name'];
            $taxonomyItem = $this->categoryHandler->updateCate($category);

            $cateExtra = CategoryExtra::where('category_id', $category_id)->first();

            CptTaxonomy::where('category_id', $category_id)->delete();
        }

        $cateExtra->slug = $inputs['slug'];
        $cateExtra->is_hierarchy = $inputs['is_hierarchy'];
        $cateExtra->save();

        foreach($inputs['cpts'] as $val) {
            $cptTaxonomy = new CptTaxonomy();
            $cptTaxonomy->cpt_id = $val;
            $cptTaxonomy->category_id = $taxonomyItem->id;
            $cptTaxonomy->save();
        }

        return $taxonomyItem->id;
    }

    public function getTaxonomies($cpt_id)
    {
        $cptTaxonomies = CptTaxonomy::where('cpt_id', $cpt_id)->get();

        $taxonomies = [];

        foreach ($cptTaxonomies as $val) {
            $taxonomies[] = $this->categoryHandler->cates()->find($val->category_id);
        }

        return $taxonomies;
    }

    public function getCategory($category_id)
    {
        return $this->categoryHandler->cates()->find($category_id);
    }

}
