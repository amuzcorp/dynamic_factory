<?php

namespace Overcode\XePlugin\DynamicFactory\Components\Widgets\SearchBar;

use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use View;
use Xpressengine\Widget\AbstractWidget;
use Xpressengine\Menu\Models\MenuItem;
use Xpressengine\Routing\InstanceRoute;
use Overcode\XePlugin\DynamicFactory\Models\DfTaxonomy;

class SearchBarWidget extends AbstractWidget
{
    protected static $path = 'dynamic_factory/components/Widgets/SearchBar';

    /**
     * Get the evaluated contents of the object.
     *
     * @return string
     */
    public function render()
    {
        $taxonomyHandler = app('overcode.df.taxonomyHandler');

        $widgetConfig = $this->setting();
        $title = $widgetConfig['@attributes']['title'];
        $dfService = app('overcode.df.service');

        $cateCount = $widgetConfig['category_count'];
        // 카테고리
        $datas = [];
        for($i = 0; $i < $cateCount; $i++) {
            $category = $dfService->getCategoryExtras()->where('slug', $widgetConfig['category_'.($i + 1)])->first();

            $categoryItems = $taxonomyHandler->getCategoryItemAttributes($category->category_id);
            $fieldTypes = $taxonomyHandler->getCategoryFieldTypes($category->category_id);

            foreach($categoryItems as $categoryItem) {
                $categoryItem->selected_item = DfTaxonomy::where('item_ids', 'like', '%"'.$categoryItem->id.'"%')->count();
            }
            $datas[$i]['category'] = $category;
            $datas[$i]['categoryItems'] = $categoryItems;
            $datas[$i]['fieldTypes'] = $fieldTypes;
        }

        // 서브 카테고리
        $sub_datas = [];
        $sub_item = [];
        if($widgetConfig['sub_category_count']) {
            $count = $widgetConfig['sub_category_count'];

            for($i = 0; $i < $count; $i++) {
                $category = $dfService->getCategoryExtras()->where('slug', $widgetConfig['sub_category_'.($i + 1)])->first();

                $categoryItems = $taxonomyHandler->getCategoryItemAttributes($category->category_id);
                $fieldTypes = $taxonomyHandler->getCategoryFieldTypes($category->category_id);

                foreach($categoryItems as $categoryItem) {
                    $categoryItem->selected_item = DfTaxonomy::where('item_ids', 'like', '%"'.$categoryItem->id.'"%')->count();
                }
                $sub_datas[$i]['category'] = $category;
                $sub_datas[$i]['categoryItems'] = $categoryItems;
                $sub_datas[$i]['fieldTypes'] = $fieldTypes;

                if(array_get($widgetConfig, 'sub_cate_'.($i+1).'_name')) {
                    $sub_datas[$i]['cate_name'] = $widgetConfig['sub_cate_' . ($i + 1) . '_name'];
                }
            }
        }

        // 기본 선택 인스턴스 URL
        $instanceData = [];
        if(array_get($widgetConfig, 'instance_id')) {
            $instanceData = MenuItem::where('site_key', \XeSite::getCurrentSiteKey())->where('id', $widgetConfig['instance_id'])->first();
        }
        // 스킨에서 선택한 인스턴스
        if(array_get($widgetConfig, 'add_instance_count')) {
            for($i = 0; $i < array_get($widgetConfig, 'add_instance_count'); $i++) {
                if (array_get($widgetConfig, 'sub_instance_id_'.($i+1))) {
                    $sub_instance_data = MenuItem::where('site_key', \XeSite::getCurrentSiteKey())->
                    where('id', $widgetConfig['sub_instance_id_'.($i+1)])->first();
                    $sub_item['sub_instance_data'][$i] = $sub_instance_data;
                }
            }
        }

        if(array_get($widgetConfig, 'sub_title')) {
            $sub_item['sub_title'] = $widgetConfig['sub_title'];
        }

        return $this->renderSkin( compact(
            'widgetConfig',
            'title',
            'instanceData',
            'datas',
            'sub_datas',
            'sub_item'
        ));

    }

    public function renderSetting(array $args = [])
    {

        $taxonomyHandler = app('overcode.df.taxonomyHandler');
        $categoryExtras = $taxonomyHandler->getCategoryExtras();
        foreach($categoryExtras as $key => $extra) {
            $categoryExtras[$key]->category_name = xe_trans($extra->category->name);
        }
        $menu_items = $this->getMenuItems();

        $view = View::make(sprintf('%s/views/setting', static::$path), [
            'args' => $args,
            'categoryExtras' => $categoryExtras,
            'menu_items' => $menu_items
        ]);

        return $view;
    }

    protected function getMenuItems()
    {
        $hasSiteKey = \Schema::hasColumn('menu_item', 'site_key');

        $menus = [];

        if($hasSiteKey) {
            $menu_items = MenuItem::where('site_key', \XeSite::getCurrentSiteKey())->orderBy('ordering')->get();
        }else {
            $menu_items = MenuItem::orderBy('ordering')->get();
        }
        foreach ($menu_items as $menu_item) {
            $menus[$menu_item->id] = $menu_item->title;
        }
        if(\XeSite::getCurrentSiteKey() == 'default') {
            $menus['admin_dashboard'] = '관리자 대시보드';
        }

        return $menus;
    }
}
