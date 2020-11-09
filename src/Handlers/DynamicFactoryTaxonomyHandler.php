<?php

namespace Overcode\XePlugin\DynamicFactory\Handlers;

use XeLang;
use App\Facades\XeCategory;
use Overcode\XePlugin\DynamicFactory\Models\CategoryExtra;
use Overcode\XePlugin\DynamicFactory\Models\CptTaxonomy;
use Overcode\XePlugin\DynamicFactory\Models\DfTaxonomy;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Xpressengine\Category\Models\Category;
use Xpressengine\Category\Models\CategoryItem;

class DynamicFactoryTaxonomyHandler
{
    const TAXONOMY_CONFIG_NAME = 'taxonomy';

    const TAXONOMY_ITEM_CONFIG_NAME = 'taxonomyItem';

    const TAXONOMY_ITEM_ID_ATTRIBUTE_NAME_PREFIX = 'cate_item_id_';

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
        \XeDB::beginTransaction();
        try {
            $category_id = $inputs['category_id'];

            // menu item slug 중복 체크
            $slugUrl = isset($inputs['slug']) === true ? $inputs['slug'] : null;
            if ($slugUrl !== null && \XeMenu::items()->query()->where('url', $slugUrl)->exists()) {
                throw new HttpException(422, xe_trans('xe::menuItemUrlAlreadyExists'));
            }

            // TODO category_extra 슬러그 중복 검사

            $cateExtra = new CategoryExtra();

            if (!$category_id) {
                $taxonomyItem = $this->categoryHandler->createCate($inputs);
                $cateExtra->category_id = $taxonomyItem->id;

            } else {
                $category = $this->categoryHandler->cates()->find($category_id);
                $category->name = $inputs['name'];
                $taxonomyItem = $this->categoryHandler->updateCate($category);

                $cateExtra = $this->getCategoryExtra($category_id);

                CptTaxonomy::where('category_id', $category_id)->delete();
            }

            $cateExtra->slug = $inputs['slug'];
            $cateExtra->template = $inputs['template'];
            $cateExtra->save();

            foreach ($inputs['cpts'] as $val) {
                $cptTaxonomy = new CptTaxonomy();
                $cptTaxonomy->cpt_id = $val;
                $cptTaxonomy->category_id = $taxonomyItem->id;
                $cptTaxonomy->save();
            }
        } catch (\Exception $e) {
            \XeDB::rollback();

            throw $e;
        }
        \XeDB::commit();

        return $taxonomyItem->id;
    }

    // plugin.php 에서 호출
    public function createCategoryForOut()
    {
        $df_categories = \XeRegister::get('df_category');
        \XeDB::beginTransaction();
        try {
            foreach($df_categories as $cate) {
                $slug = $cate['slug'];
                $cate_extra = $this->getCategoryExtraBySlug($slug);
                if (!isset($cate_extra)) {
                    $langKey = XeLang::genUserKey();
                    XeLang::save($langKey, 'ko', $cate['name'], false);

                    $category = $this->categoryHandler->createCate(['name' => $langKey]);
                    $category_id = $category->id;

                    $this->addCategoryItemForOut($category, $cate['items']);

                    $cateExtra = new CategoryExtra();
                    $cateExtra->category_id = $category_id;
                    $cateExtra->slug = $slug;
                    $cateExtra->template = $cate['template'];
                    $cateExtra->save();

                    foreach ($cate['cpt_ids'] as $cpt_id) {
                        $cptTaxonomy = new CptTaxonomy();
                        $cptTaxonomy->cpt_id = $cpt_id;
                        $cptTaxonomy->category_id = $category_id;
                        $cptTaxonomy->save();
                    }
                }
            }
        } catch (\Exception $e) {
            \XeDB::rollback();

            throw $e;
        }
        \XeDB::commit();
    }

    public function addCategoryItemForOut($category, $arr, $parent_id = null)
    {
        foreach ($arr as $key => $val) {
            $langKey = XeLang::genUserKey();
            XeLang::save($langKey, 'ko', $val['word'], false);

            $param = [
                'word' => $langKey
            ];
            if($parent_id) {
                $param['parent_id'] = $parent_id;
            }

            $item = XeCategory::createItem($category, $param);

            if(isset($val['child']) && isset($item)) {
                $this->addCategoryItemForOut($category, $val['child'], $item->id);
            }
        }
    }

    public function getTaxonomies($cpt_id)
    {
        $cptTaxonomies = CptTaxonomy::where('cpt_id', $cpt_id)->get();

        $taxonomies = [];

        foreach ($cptTaxonomies as $key => $val) {
            //$taxonomies[] = $this->getCategoryItemsTree($val->category_id);

            $category = $this->categoryHandler->cates()->find($val->category_id);
            if(isset($category)) {
                $taxonomies[] = $category;
                $taxonomies[$key]['extra'] = $this->getCategoryExtra($val->category_id);
            }
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
            $taxonomyAttributeName = $this->getTaxonomyItemAttributeName($taxonomy->id);
            if (isset($inputs[$taxonomyAttributeName]) === false) {
                continue;
            }
            $taxonomyItemId = $inputs[$taxonomyAttributeName];

            if ($taxonomyItemId === null || $taxonomyItemId === '') {
                continue;
            }

            $taxonomyItem = CategoryItem::find($taxonomyItemId);
            if ($taxonomyItem === null) {
                continue;
            }

            $newDfTaxonomy = new DfTaxonomy();
            $newDfTaxonomy->fill([
                'target_id' => $document->id,
                'category_id' => $taxonomyItem[0]->category_id,
                'item_ids' => $taxonomyItemId
            ]);

            $newDfTaxonomy->save();
        }
    }

    public function getCategoryExtra($category_id)
    {
        return CategoryExtra::where('category_id', $category_id)->first();
    }

    public function getCategoryExtraBySlug($slug)
    {
        return CategoryExtra::where('slug', $slug)->first();
    }

    public function getCategoryExtras()
    {
        return CategoryExtra::all();
    }

    public function getTaxonomyItemAttributeName($taxonomyId)
    {
        return self::TAXONOMY_ITEM_ID_ATTRIBUTE_NAME_PREFIX . $taxonomyId;
    }

    public function deleteCategory($category_id)
    {
        \XeDB::beginTransaction();
        try {
            $category = $this->categoryHandler->cates()->find($category_id);
            XeCategory::deleteCate($category);
            CategoryExtra::where('category_id', $category_id)->delete();
            CptTaxonomy::where('category_id', $category_id)->delete();
        } catch (\Exception $e) {
            \XeDB::rollback();

            //throw $e;
            return false;
        }
        \XeDB::commit();

        return true;
    }
}
