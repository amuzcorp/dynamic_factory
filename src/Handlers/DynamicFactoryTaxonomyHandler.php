<?php

namespace Overcode\XePlugin\DynamicFactory\Handlers;

use XeLang;
use XeDB;
use App\Facades\XeCategory;
use Overcode\XePlugin\DynamicFactory\Models\CategoryExtra;
use Overcode\XePlugin\DynamicFactory\Models\CptTaxonomy;
use Overcode\XePlugin\DynamicFactory\Models\DfTaxonomy;
use Symfony\Component\HttpKernel\Exception\HttpException;
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
        XeDB::beginTransaction();
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
            $cateExtra->site_key = \XeSite::getCurrentSiteKey();
            $cateExtra->save();

            if(isset($inputs['cpts'])) {
                foreach ($inputs['cpts'] as $val) {
                    $cptTaxonomy = new CptTaxonomy();
                    $cptTaxonomy->site_key = \XeSite::getCurrentSiteKey();
                    $cptTaxonomy->cpt_id = $val;
                    $cptTaxonomy->category_id = $taxonomyItem->id;
                    $cptTaxonomy->save();
                }
            }
        } catch (\Exception $e) {
            XeDB::rollback();

            throw $e;
        }
        XeDB::commit();

        return $taxonomyItem->id;
    }

    /**
     * plugin boot 단계에서 실행됨
     * 다른 플러그인이 등록한 카테고리를 불러와서 있으면 무시, 없으면 카테고리를 생성해 준다.
    */
    public function createCategoryForOut()
    {
        $df_categories = \XeRegister::get('df_category');
        XeDB::beginTransaction();
        try {
            foreach((array)$df_categories as $cate) {
                $slug = $cate['slug'];
                $cate_extra = $this->getCategoryExtraBySlug($slug);
                if (!isset($cate_extra)) {
                    $langKey = XeLang::genUserKey();
                    XeLang::save($langKey, 'ko', $cate['name'], false);

                    $category = $this->categoryHandler->createCate(['name' => $langKey]);
                    $category_id = $category->id;

                    $this->addCategoryItemForOut($category, $cate['items']);

                    $cateExtra = new CategoryExtra();
                    $cateExtra->site_key = \XeSite::getCurrentSiteKey();
                    $cateExtra->category_id = $category_id;
                    $cateExtra->slug = $slug;
                    $cateExtra->template = $cate['template'];
                    $cateExtra->save();

                    foreach ($cate['cpt_ids'] as $cpt_id) {
                        $cptTaxonomy = new CptTaxonomy();
                        $cptTaxonomy->site_key = \XeSite::getCurrentSiteKey();
                        $cptTaxonomy->cpt_id = $cpt_id;
                        $cptTaxonomy->category_id = $category_id;
                        $cptTaxonomy->save();
                    }
                }

                //set dynamic field
                if(array_get($cate,'dynamic_fields') && count($cate['dynamic_fields']) > 0){
                    foreach($cate['dynamic_fields'] as $key => $val){
                        $val['group'] = 'tax_'.$slug;
                        $cate['dynamic_fields'][$key] = $val;
                    }
                    \XeRegister::push('df_df', $slug, $cate['dynamic_fields']);
                }
            }
        } catch (\Exception $e) {
            XeDB::rollback();

            throw $e;
        }
        XeDB::commit();
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

    public function getTaxonomyIds($cpt_id)
    {
        $siteKey = \XeSite::getCurrentSiteKey();

        $cptTaxonomies = CptTaxonomy::where('cpt_id', $cpt_id)->where('site_key', $siteKey)->get();

        $taxo_ids = [];

        foreach ($cptTaxonomies as $key => $val) {
            $taxo_ids[] = $val->category_id;
        }

        return $taxo_ids;
    }

    public function getTaxonomies($cpt_id)
    {
        $siteKey = \XeSite::getCurrentSiteKey();

        $cptTaxonomies = CptTaxonomy::where('cpt_id', $cpt_id)->where('site_key', $siteKey)->get();

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

            $taxonomyItem = CategoryItem::find($taxonomyItemId)->first();
            if ($taxonomyItem === null) {
                continue;
            }

            if(!is_array($taxonomyItemId)){
                $taxonomyItemId = (array)$taxonomyItemId;
            }
            $newDfTaxonomy = new DfTaxonomy();
            $newDfTaxonomy->fill([
                'target_id' => $document->id,
                'category_id' => $taxonomyItem->category_id,
                'item_ids' => $taxonomyItemId
            ]);

            $newDfTaxonomy->save();
        }
    }

    public function updateTaxonomy($document, $inputs)
    {
        $taxonomies = $this->getTaxonomies($inputs['cpt_id']);
        $i = 0;
        foreach ($taxonomies as $taxonomy) {
            $taxonomyAttributeName = $this->getTaxonomyItemAttributeName($taxonomy->id);
            if (isset($inputs[$taxonomyAttributeName]) === false) {
                continue;
            }

            $dfTaxonomy = $document->taxonomy()->where('category_id', $taxonomy->id)->get()->first();

            $taxonomyItemId = $inputs[$taxonomyAttributeName];
            if ($taxonomyItemId === null || $taxonomyItemId === '' || (!empty($taxonomyItemId) && $taxonomyItemId[0] === '')) {
                if ($dfTaxonomy !== null) {
                    $dfTaxonomy->delete();
                    continue;
                }
            }

            $taxonomyItem = CategoryItem::find($taxonomyItemId)->first();
            if ($taxonomyItem === null) {
                continue;
            }

            if ($dfTaxonomy !== null) {
                if(!is_array($taxonomyItemId)) {
                    $taxonomyItemId = [
                        $taxonomyItemId
                    ];
                }

                if ($dfTaxonomy['item_ids'] != $taxonomyItemId) {
                    $dfTaxonomy['item_ids'] = $taxonomyItemId;
                }

                $dfTaxonomy->save();
            } else {
                $newDfTaxonomy = new DfTaxonomy();
                $newDfTaxonomy->fill([
                    'target_id' => $document->id,
                    'category_id' => $taxonomyItem->category_id,
                    'item_ids' => $taxonomyItemId
                ]);

                $newDfTaxonomy->save();
            }
            $i++;
        }
    }

    public function getCategoryExtra($category_id)
    {
        return CategoryExtra::where('category_id', $category_id)->first();
    }

    public function getCategoryItemExtra($category_item_id)
    {

    }

    public function getCategoryExtraBySlug($slug)
    {
        $query = CategoryExtra::where('slug', $slug);
        // site_key 컬럼을 가지고 있는지
        $hasSiteKey = \Schema::hasColumn('documents', 'site_key');
        if($hasSiteKey == true) {
            $query = $query->where('site_key', \XeSite::getCurrentSiteKey());
        }

        return $query->first();
    }

    public function getCategoryExtras()
    {
        // site_key 컬럼을 가지고 있는지
        $hasSiteKey = \Schema::hasColumn('documents', 'site_key');
        if($hasSiteKey == true) {
            return CategoryExtra::where('site_key', \XeSite::getCurrentSiteKey())->get();
        }

        return CategoryExtra::all();
    }

    public function getTaxonomyItemAttributeName($taxonomyId)
    {
        return self::TAXONOMY_ITEM_ID_ATTRIBUTE_NAME_PREFIX . $taxonomyId;
    }

    public function deleteCategory($category_id)
    {
        XeDB::beginTransaction();
        try {
            $category = $this->categoryHandler->cates()->find($category_id);
            XeCategory::deleteCate($category);
            CategoryExtra::where('category_id', $category_id)->delete();
            CptTaxonomy::where('category_id', $category_id)->delete();
        } catch (\Exception $e) {
            XeDB::rollback();

            //throw $e;
            return false;
        }
        XeDB::commit();

        return true;
    }

    public function getTaxFieldGroup($category_id)
    {
        $categoryExtra = $this->getCategoryExtra($category_id);
        $slug = $categoryExtra->slug;
        $group = 'tax_' . $slug;

        return $group;
    }

    public function getCategoryFieldTypes($category_id)
    {
        $group = $this->getTaxFieldGroup($category_id);

        $dynamicFieldHandler = app('xe.dynamicField');
        $dynamicFields = $dynamicFieldHandler->gets($group);

        $fieldTypes = []; // 카테고리에 선언된 확장변수 타입들
        foreach ($dynamicFields as $key => $val) {
            $fieldType = df($group, $key);
            $fieldTypes[] = $fieldType;
        }

        return $fieldTypes;
    }

    /**
     * category_id 로 item 리스트를 만들고 확장 변수 view 에 필요한 변수들을 붙여서 반환
     */
    public function getCategoryItemAttributes($category_id)
    {
        $group = $this->getTaxFieldGroup($category_id);

        $dynamicFieldHandler = app('xe.dynamicField');
        $dynamicFields = $dynamicFieldHandler->gets($group);

        $query = CategoryItem::newBaseQueryBuilder()->where('category_id',$category_id);
        foreach($dynamicFields as $field_name => $field)
            $query = df($group, $field_name)->get($query);

        return $query->get();
    }

    /**
     * category_id 로 item 리스트를 만들고 확장 변수 입력폼을 붙여서 반환
     */
    public function getCategoryDynamicField($category_id)
    {
        $group = $this->getTaxFieldGroup($category_id);

        $category_items = XeCategory::cates()->find($category_id)->items;

        $dynamicFieldHandler = app('xe.dynamicField');
        $dynamicFields = $dynamicFieldHandler->gets($group);

        foreach($category_items as $item) {
            $dfs = [];

            foreach ($dynamicFields as $dfKey => $dfVal) {
                $fieldType = df($group, $dfKey);
                $tableName = $fieldType->getTableName();

                foreach ($fieldType->getColumns() as $column) {
                    $name = $dfKey . '_' . $column->name;

                    $param = [
                        'field_id' => $dfKey,
                        'target_id' => $item->id,
                        'group' => $group
                    ];

                    // target_id 로 해당 Dynamic Field Table 에서 get 한다.
                    $die = XeDB::table($tableName)->where($param)->first();
                    if($die === null){
                        $dfs[] = $this->df_create($group, $dfKey, []);
                    }else{
                        if(isset($die->{$column->name})) {
                            $args = [
                                'id' => $item->id,
                                $name => $die->{$column->name}
                            ];
                            $dfs[] = $this->df_edit($group, $dfKey, $args);
                        }
                    }
                }
            }

            $item->dfs = $dfs;
        }

        return $category_items;
    }

    public function df_create($group, $columnName, $args)
    {
        $fieldType = $this->df($group, $columnName);
        if ($fieldType == null) {
            return '';
        }

        return $fieldType->getSkin()->create($args);
    }

    public function df_edit($group, $columnName, $args)
    {
        $fieldType = $this->df($group, $columnName);
        if ($fieldType == null) {
            return '';
        }

        return $fieldType->getSkin()->edit($args);
    }

    public function df($group, $columnName)
    {
        return \XeDynamicField::get($group, $columnName);
    }

    public function getSelectCategoryItems($cpt_id, $target_id)
    {
        $cptTaxs = CptTaxonomy::get()->where('cpt_id', $cpt_id)->where('site_key', \XeSite::getCurrentSiteKey());

        $items = [];

        foreach($cptTaxs as $cptTax) {
            $docTaxs = DfTaxonomy::get()->where('category_id', $cptTax->category_id)->where('target_id', $target_id);
            foreach ($docTaxs as $docTax) {
                $items[$cptTax->category_id] = $docTax->item_ids;
            }
        }

        return $items;
    }

    /**
     * item_id 로 검색하여 view 에 필요한 Dynamic Field Attributes 까지 붙여서 반환
     *
     * @param $item_id
     * @return mixed
     */
    public function getCategoryItem($item_id)
    {
        $category_item = CategoryItem::find($item_id);
        if($category_item == null) return null;

        $category_id = $category_item->category_id;
        $items = $this->getCategoryItemAttributes($category_id);

        foreach ($items as $item){
            if($item->id == $item_id) {
                return $item;
            }
        }
    }

    public function getItemOnlyTargetId($target_id)
    {
        $df_taxonomies = XeDB::table('df_taxonomy')->where('target_id', $target_id)->get();
        $poi = [];

        foreach($df_taxonomies as $df_taxonomy){
            foreach (json_decode($df_taxonomy->item_ids) as $jd){
                array_push($poi, $jd);
            }
        }

        $items = XeDB::table('category_item')->whereIn('id', $poi)->get();
        return $items;
    }
}
