<?php

namespace Overcode\XePlugin\DynamicFactory\Components\Widgets\DocumentList;

use Carbon\Carbon;
use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use View;
use Xpressengine\Category\Models\CategoryItem;
use Xpressengine\Widget\AbstractWidget;

class DocumentListWidget extends AbstractWidget
{
    protected static $path = 'dynamic_factory/components/Widgets/DocumentList';

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

        $cpt_id = $widgetConfig['cpt_id'];

        $categoryIds = [];

        if(isset($widgetConfig['categories']['item'])) {
            $categoryIds = $widgetConfig['categories']['item'];
        }else if(isset($widgetConfig['categories'])) {
            $categoryIds[] = $widgetConfig['categories'];
        }

        $categoryIds = array_map(function ($item) {
            $item = CategoryItem::find($item);
            if ($item === null) {
                return [];
            }

            return $item->getDescendantTree(true)->getNodes()->pluck('id');
        }, $categoryIds);

        $categoryIds = array_flatten($categoryIds);

        $take = $widgetConfig['take'] ?? null;
        $recent_date = (int)$widgetConfig['recent_date'] ?? 0;
        $orderType = $widgetConfig['order_type'] ?? '';

        $title = $widgetConfig['@attributes']['title'];

        $model = CptDocument::division($cpt_id, $site_key);
        $query = $model->where('instance_id', $cpt_id);

        $query = $query->where('site_key', $site_key);

        if(count($categoryIds) > 0) {
            $query->leftJoin(
                'df_taxonomy',
                sprintf('%s.%s', $query->getQuery()->from, 'id'),
                '=',
                sprintf('%s.%s', 'df_taxonomy', 'target_id')
            );

            foreach($categoryIds as $item_id) {
                $query = $query->where('df_taxonomy.item_ids', 'like', '%"'. $item_id .'"%');
            }
        }

        //$recent_date
        if ($recent_date !== 0) {
            $current = Carbon::now();
            $query = $query->where('created_at', '>=', $current->addDay(-1 * $recent_date)->toDateString() . ' 00:00:00')
                ->where('created_at', '<=', $current->addDay($recent_date)->toDateString() . ' 23:59:59');
        }

        //$orderType
        if ($orderType == '') {
            $query = $query->orderBy('head', 'desc');
        } elseif ($orderType == 'assent_count') {
            $query = $query->orderBy('assent_count', 'desc')->orderBy('head', 'desc');
        } elseif ($orderType == 'recentlyCreated') {
            $query = $query->orderBy(CptDocument::CREATED_AT, 'desc')->orderBy('head', 'desc');
        } elseif ($orderType == 'recentlyUpdated') {
            $query = $query->orderBy(CptDocument::UPDATED_AT, 'desc')->orderBy('head', 'desc');
        }

        if ($take) {
            $query = $query->take($take);
        }

        $list = $query->get();

        return $this->renderSkin([
            'widgetConfig' => $widgetConfig,
            'list' => $list,
            'title' => $title,
            'cptUrlHandler' => $cptUrlHandler,
            'site_key' => $site_key
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
