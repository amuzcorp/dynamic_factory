<?php

namespace Overcode\XePlugin\DynamicFactory\Components\Widgets\DocumentWriter;

use Carbon\Carbon;
use Overcode\XePlugin\DynamicFactory\Components\Modules\Cpt\CptModule;
use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use View;
use Xpressengine\Category\Models\CategoryItem;
use Xpressengine\Http\Request;
use Xpressengine\Widget\AbstractWidget;

class DocumentWriterWidget extends AbstractWidget
{
    protected static $path = 'dynamic_factory/components/Widgets/DocumentWriter';

    /**
     * Get the evaluated contents of the object.
     *
     * @return string
     */
    public function render()
    {
        $widgetConfig = $this->setting();
        $cptUrlHandler = app('overcode.df.url');
        $site_key = $widgetConfig['site_key'];
        $title = $widgetConfig['@attributes']['title'];

        $taxonomyHandler = app('overcode.df.taxonomyHandler');
        $dfService = app('overcode.df.service');
        $cptDocService = app('overcode.doc.service');

        $cpt_id = $widgetConfig['cpt_id'];

        $cpt = $dfService->getItem($cpt_id);
        $permission_check = app('overcode.df.permission')->get($cpt_id);
        if(!$permission_check) {
            \DB::table('permissions')->insert([
                'site_key'=> $site_key, 'name' => CptModule::getId().'.'.$cpt_id, 'grants' => '{"create":{"rating":"guest","group":[],"user":[],"except":[],"vgroup":[]}}',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
        }

        $taxonomies = $taxonomyHandler->getTaxonomies($cpt_id);
        $cptConfig = $dfService->getCptConfig($cpt_id);

        $fieldTypes = $cptDocService->getFieldTypes($cptConfig);

        $dynamicFieldsById = [];
        foreach ($fieldTypes as $fieldType) {
            $dynamicFieldsById[$fieldType->get('id')] = $fieldType;
        }

        return $this->renderSkin([
            'widgetConfig' => $widgetConfig,
            'title' => $title,
            'cpt' => $cpt,
            'cptUrlHandler' => $cptUrlHandler,
            'site_key' => $site_key,
            'taxonomies' => $taxonomies,
            'head' => '',
            'fieldTypes' => $fieldTypes,
            'cptConfig' => $cptConfig,
            'dynamicFieldsById' => $dynamicFieldsById
        ]);
    }

    /**
     * 위젯 설정 페이지에 출력할 폼을 출력한다.
     *
     * @param array $args 설정값
     *
     * @return string
     */
    public function renderSetting(array $args = [])
    {
        $siteList = \XeDB::table('site')->get();

        return $view = View::make(sprintf('%s/views/setting', static::$path), [
            'args' => $args,
            'cptList' => $this->getCptList(),
            'siteList' => $siteList
        ]);
    }

    /**
     * get Cpt List
     *
     * @return mixed
     */
    protected function getCptList()
    {
        $cpts = app('overcode.df.service')->getItemsAll();

        return $cpts;
    }

    /**
     * get CategoryList
     *
     * @param CategoryItem $categoryItem
     * @return array
     */
    private function getCategoryList(CategoryItem $categoryItem)
    {
        return [];
    }
}
