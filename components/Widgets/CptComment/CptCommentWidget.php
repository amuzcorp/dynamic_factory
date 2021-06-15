<?php

namespace Overcode\XePlugin\DynamicFactory\Components\Widgets\CptComment;

use Carbon\Carbon;
use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use View;
use Xpressengine\Category\Models\CategoryItem;
use Xpressengine\Plugins\Comment\Models\Comment;
use Xpressengine\Widget\AbstractWidget;
use Xpressengine\Plugins\Comment\Models\Target;

class CptCommentWidget extends AbstractWidget
{
    protected static $path = 'dynamic_factory/components/Widgets/CptComment';

    /**
     * Get the evaluated contents of the object.
     *
     * @return string
     */
    public function render()
    {
        $widgetConfig = $this->setting();
        $title = $widgetConfig['@attributes']['title'];

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

        $query->visible();

        $query = $query->orderBy('head', 'desc');

        $cpt_id = $widgetConfig['cpt_id'];
        $CPTDocumentIds = $query->pluck('id');

        $targetType = 'Overcode\XePlugin\DynamicFactory\Models\CptDocument';
        $commentMap = \xeDB::table('config')->where('name', 'comment_map')->first();
        $instanceId = array_get(json_dec($commentMap->vars, true), $cpt_id , '');

        $comments = $this->getCommentList($targetType, $cpt_id, $instanceId, $widgetConfig, $CPTDocumentIds);
        foreach($comments as $comment) {
            $target_id = Target::where('doc_id', $comment->id)->value('target_id');
            $comment->cpt_title = CptDocument::find($target_id)->title;
            $comment->user_profile_image_id = \XeUser::where('id', $comment->user_id)->first()->getProfileImage();
        }

        return $this->renderSkin([
            'title' => $title,
            'comments' => $comments,
            'widgetConfig' => $widgetConfig
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

    public function getCommentList($targetType, $targetId, $instanceId, $widgetConfig, $CPTDocumentIds) {
        $handler = app('xe.plugin.comment')->getHandler();

        $take = $widgetConfig['take'] ?? null;
        $recent_date = (int)$widgetConfig['recent_date'] ?? 0;
        $orderType = $widgetConfig['order_type'] ?? '';

        $model = $handler->createModel($instanceId);

        $query = $model->newQuery()->whereHas('target', function ($query) use ($targetId, $targetType, $CPTDocumentIds) {
            $query->whereIn('target_id', $CPTDocumentIds)->where('target_type', $targetType);
        })->where('display', '!=', Comment::DISPLAY_HIDDEN)->orderBy('created_at', 'DESC');

        if ($take) {
            $query = $query->take($take);
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

        // 대상글 작성자를 조회하는 relation 명을 지정할 수 없음.
        $comments = $query->paginate($take, ['*'], 'page', 1);

        return $comments;

    }
}
