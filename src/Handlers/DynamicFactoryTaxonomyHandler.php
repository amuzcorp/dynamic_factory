<?php

namespace Overcode\XePlugin\DynamicFactory\Handlers;

use Overcode\XePlugin\DynamicFactory\Models\CategoryExtra;
use Overcode\XePlugin\DynamicFactory\Models\CptTaxonomy;
use Overcode\XePlugin\DynamicFactory\Models\DfTaxonomy;
use Xpressengine\Category\Models\CategoryItem;

class DynamicFactoryTaxonomyHandler
{
    const TAXONOMY_CONFIG_NAME = 'taxonomy';

    const TAXONOMY_ITEM_CONFIG_NAME = 'taxonomyItem';

    const TAXONOMY_ITEM_ID_ATTRIBUTE_NAME_PREFIX = 'taxonomy_item_id_';

    protected $categoryHandler;

    protected $dfConfigHandler;

    protected $dfService;

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

            $cateExtra = $this->getCategoryExtra($category_id);

            CptTaxonomy::where('category_id', $category_id)->delete();
        }

        $cateExtra->slug = $inputs['slug'];
        $cateExtra->template = $inputs['template'];
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

        foreach ($cptTaxonomies as $key => $val) {
            //$taxonomies[] = $this->getCategoryItemsTree($val->category_id);
            $taxonomies[] = $this->categoryHandler->cates()->find($val->category_id);
            //$taxonomies[$key]['extra'] = $this->getCategoryExtra($val->category_id);
        }

        return $taxonomies;
    }

    public function getCategory($category_id)
    {
        return $this->categoryHandler->cates()->find($category_id);
    }

    public function getCategoryItemsTree($category_id)
    {
        $items = [];

        $categoryItems = CategoryItem::where('category_id', $category_id)
            ->where('parent_id', null)
            ->orderBy('ordering')->get();
        foreach ($categoryItems as $categoryItem) {
            $categoryItemData = [
                'value' => $categoryItem->id,
                'text' => xe_trans($categoryItem->word),
                'children' => $this->getCategoryItemChildrenData($categoryItem)
            ];

            $items[] = $categoryItemData;
        }

        return $items;
    }

    public function getCategoryItemChildrenData(CategoryItem $categoryItem)
    {
        $children = $categoryItem->getChildren();

        if($children->isEmpty() === true) {
            return [];
        }

        $childrenData = [];
        foreach ($children as $child) {
            $childrenData[] = [
                'value' => $child->id,
                'text' =>xe_trans($child->word),
                'children' => $this->getCategoryItemChildrenData($child)
            ];
        }

        return $childrenData;
    }

    public function getTaxonomyItems($category_id)
    {
        $items = [];
        $taxonomyItems = CategoryItem::where('category_id', $category_id)->orderBy('ordering')->get();
        foreach ($taxonomyItems as $taxonomyItem) {
            $items[] = [
                'value' => $taxonomyItem->id,
                'text' => $taxonomyItem->word
            ];
        }

        return $items;
    }

    public function storeTaxonomy($document, $inputs)
    {
        $taxonomies = $this->getTaxonomies($inputs['cpt_id']);

        foreach ($taxonomies as $taxonomy) {
            $categoryId = $taxonomy->id;
            if (isset($inputs[$categoryId]) === false) {
                continue;
            }
            $taxonomyItemId = $inputs[$categoryId];

            if ($taxonomyItemId === null || $taxonomyItemId === '') {
                continue;
            }

            $newDfTaxonomy = new DfTaxonomy();
            $newDfTaxonomy->fill([
                'target_id' => $document->id,
                'category_id' => $categoryId,
                'item_id' => $taxonomyItemId
            ]);

            $newDfTaxonomy->save();
        }
    }

    public function getCategoryExtra($category_id)
    {
        return CategoryExtra::where('category_id', $category_id)->first();
    }

}
