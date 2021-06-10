<?php

namespace Overcode\XePlugin\DynamicFactory\Components\Widgets\Category;

use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use View;
use Xpressengine\Widget\AbstractWidget;
use Xpressengine\Menu\Models\MenuItem;
use Xpressengine\Routing\InstanceRoute;

class CategoryWidget extends AbstractWidget
{
    protected static $path = 'dynamic_factory/components/Widgets/Category';

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

        // 카테고리
        $dfService = app('overcode.df.service');
        $category = $dfService->getCategoryExtras()->where('slug', $widgetConfig['category_slug'])->first();

        // 카테고리 아이템
        $categoryItems = $taxonomyHandler->getCategoryItemAttributes($category->category_id);
        $fieldTypes = $taxonomyHandler->getCategoryFieldTypes($category->category_id);

        // 선택한 인스턴스 URL
        $instanceUrl = '';
        if($widgetConfig['instance_id'] !== '') {
            $instanceUrl = InstanceRoute::where('instance_id', $widgetConfig['instance_id'])->value('url');
        }

        //인스턴스로 전달한 카테고리 ID의 Parameter Key 이름
        $parameterKey = $widgetConfig['parameter_key'];

        return $this->renderSkin( compact(
            'widgetConfig',
            'title',
            'category',
            'categoryItems',
            'instanceUrl',
            'parameterKey',
            'categoryItems',
            'fieldTypes'
        ));

    }

    public function renderSetting(array $args = [])
    {

        $taxonomyHandler = app('overcode.df.taxonomyHandler');
        $categoryExtras = $taxonomyHandler->getCategoryExtras();
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
