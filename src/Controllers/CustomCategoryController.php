<?php
namespace Overcode\XePlugin\DynamicFactory\Controllers;

use App\Http\Controllers\CategoryController;
use Exception;
use Xpressengine\Category\Models\Category;
use Xpressengine\Category\Models\CategoryItem;
use Xpressengine\Http\Request;
use Xpressengine\Support\Caster;
use Xpressengine\Support\Exceptions\InvalidArgumentHttpException;
use XeCategory;
use XeLang;
use XePresenter;
use XeDB;

class CustomCategoryController extends CategoryController
{
    public function storeItem(Request $request, $id)
    {
        /** @var Category $category */
        $category = XeCategory::cates()->find($id);

        \XeDB::beginTransaction();
        try {
            /** @var CategoryItem $item */
            $item = XeCategory::createItem($category, $request->all());
        } catch (Exception $e) {
            \XeDB::rollBack();

            throw $e;
        }
        \XeDB::commit();

        $multiLang = XeLang::getPreprocessorValues($request->all(), session()->get('locale'));
        $item->readableWord = $multiLang['word'];

        return XePresenter::makeApi($item->toArray());
    }

    public function updateItem(Request $request, $id)
    {
        /** @var CategoryItem $item */
        $item = XeCategory::items()->find($request->get('id'));
        if (!$item || $item->category->id !== Caster::cast($id)) {
            throw new InvalidArgumentHttpException;
        }
        \XeDB::beginTransaction();
        try {
            XeCategory::updateItem($item, $request->all());
            $this->insertDf($request);  // 다이나믹 필드를 저장한다.
//            $df = $this->insertDf($request);

//            return XePresenter::makeApi($df);
        } catch (Exception $e) {
            \XeDB::rollBack();

            throw $e;
        }
        \XeDB::commit();

        $multiLang = XeLang::getPreprocessorValues($request->all(), session()->get('locale'));
        $item->readableWord = $multiLang['word'];

        return XePresenter::makeApi($item->toArray());
    }

    public function children(Request $request, $id)
    {
        $txHandler = app('overcode.df.taxonomyHandler');
        $categoryExtra = $txHandler->getCategoryExtra($id);

        // 기본적으로 ITEM ID 없이 들어온다 $request->get('id') 가 item id
        $group = 'tax_' .$categoryExtra->slug;

        if ($request->get('id') === null) {
            $children = XeCategory::cates()->find($id)->getProgenitors();
        } else {
            /** @var CategoryItem $item */
            $item = XeCategory::items()->find($request->get('id'));
            if (!$item || $item->category->id !== Caster::cast($id)) {
                throw new InvalidArgumentHttpException;
            }

            $children = $item->getChildren();
        }

        $dynamicField = app('xe.dynamicField');
        $dfs = $dynamicField->gets($group);

        foreach ($children as $child) {
            $df = [];
            foreach ($dfs as $key => $val) {
                $df[] = $this->df_create($group, $key, $request->all());
            }
            $child->readableWord = xe_trans($child->word);
            $child->dfs = $df;
        }

        return XePresenter::makeApi($children->toArray());
    }

    public function df_create($group, $columnName, $args)
    {
        $fieldType = $this->df($group, $columnName);
        if ($fieldType == null) {
            return '';
        }

        return $fieldType->getSkin()->create($args);
    }

    public function df($group, $columnName)
    {
        return \XeDynamicField::get($group, $columnName);
    }

    public function insertDf(Request $request)
    {
        $dynamicField = app('xe.dynamicField');

        $group = 'tax_' . $request->slug;

        $fieldTypes = $dynamicField->gets($group);
        foreach ($fieldTypes as $id => $fieldType){
            $insertParam = [];
            $insertParam['field_id'] = $id;
//            $insertParam['rules'] = array_key_first($fieldType->getRules());
            $insertParam['target_id'] = $request->get('id');   //category item id
            $insertParam['group'] = $group;
            foreach ($fieldType->getColumns() as $column) {
                $key = $id . '_' . $column->name;

                if (isset($request->{$key}) == true) {
                    $insertParam[$column->name] = $request->{$key};
                }
            }

            $this->insertDfTable($insertParam, $fieldType->getTableName());
        }

        $args = [
            "field_id" => "board_input",
            "target_id" => "1cf43517-2bf2-43c8-a196-4b6cc2f4f8cf",
            "group" => "documents_d7838ea4",
            "text" => "qwer"
        ];
    }

    public function insertDfTable($insertParam, $tableName)
    {
        \XeDB::table($tableName)->insert($insertParam);
    }
}
