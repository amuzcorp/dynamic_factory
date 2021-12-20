<?php
namespace Overcode\XePlugin\DynamicFactory\Services;

use Illuminate\Support\Collection;
use Overcode\XePlugin\DynamicFactory\Exceptions\NotFoundDocumentException;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryDocumentHandler;
use Overcode\XePlugin\DynamicFactory\IdentifyManager;
use Overcode\XePlugin\DynamicFactory\Models\CategoryExtra;
use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use Xpressengine\Config\ConfigEntity;
use Xpressengine\Http\Request;
use Xpressengine\Permission\Instance;
use Xpressengine\User\UserInterface;
use Gate;

class CptDocService
{
    protected $handler;

    public function __construct(DynamicFactoryDocumentHandler $documentHandler)
    {
        $this->handler = $documentHandler;
    }

    /**
     * @param Request $request
     * @param ConfigEntity $config
     * @param null $site_key (없으면 자신의 사이트 문서만, 있으면 해당 사이트의 문서만, *이면 모든 사이트의 문서)
     * @return mixed
     */
    public function getItems(Request $request, ConfigEntity $config, $site_key = null)
    {
        //확장필드들은 config에 기반하기때문에 전체사이트를 조회하는경우 각 사이트의 config를 따로불러올 수 없으므로 default를 기준으로 join하도록 처리함
        //즉, default에서 선언되지 않은 확장필드를 멀티사이트에서 선언한다고해서 전체사이트 데이터를 조회할때 멀티사이트의 확장필드가 보여지지는 않음
        //하지만 멀티사이트 전체의 게시글을 한번에 조회하는것은 보통 default사이트에서 처리되므로 큰문제는 없을듯함.
        $model = CptDocument::division($config->get('cpt_id'), $site_key != '*' ?: 'default');

        $query = $model->where('instance_id', $config->get('cpt_id'));

        // site_key 컬럼을 가지고 있는지
        $hasSiteKey = \Schema::hasColumn('documents', 'site_key');

        if($hasSiteKey == true) {
            $site_key = $site_key == null ? \XeSite::getCurrentSiteKey() : $site_key;
            if($site_key != "*"){
                $query->where('site_key', $site_key);
            }
        }

        if ($config->get('useConsultation') === true){
            $cptPermission = app('overcode.df.permission');
            $isManager = Gate::allows(
                $cptPermission::ACTION_MANAGE,
                new Instance($cptPermission->name($config->get('instanceId')))
            ) ? true : false;
            if ($isManager == false) {
                $query->where('user_id', auth()->user()->getId());
            }
        } elseif($config->get('useGroupConsultation') === true) {
            $cptPermission = app('overcode.df.permission');
            $isManager = Gate::allows(
                $cptPermission::ACTION_MANAGE,
                new Instance($cptPermission->name($config->get('instanceId')))
            ) ? true : false;
            if ($isManager == false) {
                $user_groups = auth()->user()->groups->pluck('id') ?: [];
                $groupInUsers = \XeDB::table('user_group_user')->whereIn('group_id', $user_groups)->groupBy('user_id')->pluck('user_id');
                $query->whereIn('user_id',$groupInUsers);
            }
        }

        $dfConfig = app('overcode.df.configHandler')->getConfig($config->get('cpt_id'));
        if($dfConfig != null) {
            $orders = $dfConfig->get('orders', []); // dyFac Config 의 정렬 정보를 가져옴
            $request->request->add(['orders' => $orders]);
        }

        $this->handler->makeWhere($query, $request, $config);
        $this->handler->makeOrder($query, $request, $config);

        $sql = $query->toSql();
        $is_controlled_visible = false;
        foreach(['status','visible','approved'] as $visible) if(strpos($sql, $visible) !== false) $is_controlled_visible = true;
        if(!$is_controlled_visible) $query->visible();

        $query->GroupBy('documents.id');

        $perPage = $request->get('perPage') ?: $config->get('perPage') ?: '20';
        $paginate = $query->paginate($perPage)->appends($request->except('page'));

        $total = $paginate->total();
        $currentPage = $paginate->currentPage();
        $count = 0;

        foreach($paginate as $item) {
            if(!app('overcode.df.documentHandler')->hasFavorite($item->id, \Auth::user()->getId())) $item->has_favorite = 0;
            else $item->has_favorite = 1;
        }

        if($request->get('taxonomies') && $request->get('taxonomies') === 'Y'){
            $taxonomyHandler = app('overcode.df.taxonomyHandler');
            $categoryHandler = app('xe.category');
            foreach($paginate as $item) {
                $selectedTaxonomies = $taxonomyHandler->getItemOnlyTargetId($item->id);
                foreach($selectedTaxonomies as $taxonomy) {
                    $cate = $categoryHandler->cates()->find($taxonomy->category_id);
                    $category_Extra = CategoryExtra::where('category_id', $taxonomy->category_id)->first();
                    $taxonomy->category_slug = $category_Extra->slug;
                    $taxonomy->parent_cate_name = xe_trans($cate->name);
                    $taxonomy->word = xe_trans($taxonomy->word);
                    $taxonomy->description = xe_trans($taxonomy->description);
                }
                $item->selectedTaxonomies = $selectedTaxonomies;
            }
        }

        //getThumbnail
        if($request->get('thumbnail') && $request->get('thumbnail') === 'Y') {
            $dfDocumentHandler = app('overcode.df.documentHandler');
            foreach($paginate as $item) {
                if($dfDocumentHandler->getThumb($item->id)) $item->thumbnail = $dfDocumentHandler->getThumb($item->id);
            }
        }


        // 순번 필드를 추가하여 transform
        $paginate->getCollection()->transform(function ($paginate) use ($total, $perPage, $currentPage, &$count) {
            $paginate->seq = ($total - ($perPage * ($currentPage - 1))) - $count;
            $count++;
            return $paginate;
        });

        return $paginate;
    }

    public function getItem($id, UserInterface $user, ConfigEntity $config, $force = false)
    {
        $item = CptDocument::division($config->get('cpt_id'))->find($id);

        if(!app('overcode.df.documentHandler')->hasFavorite($item->id, \Auth::user()->getId())) $item->has_favorite = 0;
        else $item->has_favorite = 1;

        if ($item === null) {
            throw new NotFoundDocumentException;
        }
        return $item;
    }

    public function getItemOnlyId($id)
    {
        $temp = CptDocument::find($id);
        if ($temp === null) {
            throw new NotFoundDocumentException;
        }

        $item = CptDocument::division($temp->instance_id)->find($id);

        return $item;
    }

    public function getFieldTypes(ConfigEntity $config)
    {
        $configHandler = app('overcode.df.configHandler');
        return (array)$configHandler->getDynamicFields($config);
    }

    /**
     * 확장 필드 관련 CPT 목록 불러오기
     *
     * @param array $cpt_ids
     * @param UserInterface|null $user
     * @param string $author
     * @param null $site_key * 일때는 모든 사이트
     * @return Collection
     */
    public function getItemsByCptIds(array $cpt_ids, UserInterface $user = null, $author = 'any', $site_key = null)
    {
        $result_items = new Collection();

        if($site_key != '*') $site_key = $site_key != null ? $site_key : \XeSite::getCurrentSiteKey();

        // site_key 컬럼을 가지고 있는지
        $hasSiteKey = \Schema::hasColumn('documents', 'site_key');

        foreach($cpt_ids as $cpt_id) {
            $query = CptDocument::division($cpt_id)->where('instance_id', $cpt_id);
            if($hasSiteKey == true && $site_key != '*') {
                $query = $query->where('site_key', $site_key);
            }
            if($author === 'author') {
                $query = $query->where('user_id', $user->getId());
            }

            $query->visible();

            $items = $query->get();
            $result_items = $result_items->merge($items);
        }

        return $result_items;
    }

    public function getItemsAllCpt(Request $request, $type = 'all', $cpt_id = null)
    {
        if($cpt_id == null) {
            $cpts = app('overcode.df.service')->getItemsAll();
            foreach ($cpts as $cpt) {
                $cpt_ids[] = $cpt->cpt_id;
            }
        }else{
            $cpt_ids = [$cpt_id];
        }

        $query = CptDocument::whereIn('instance_id', $cpt_ids);

        if($type == 'trash') {
            $query = $query->onlyTrashed();
        }

        // 검색조건 붙을 부분

        $paginate = $query->paginate(20)->appends($request->except('page'));

        $total = $paginate->total();
        $perPage = $paginate->perPage();
        $currentPage = $paginate->currentPage();
        $count = 0;

        // 순번 필드를 추가하여 transform
        $paginate->getCollection()->transform(function ($paginate) use ($total, $perPage, $currentPage, &$count) {
            $paginate->seq = ($total - ($perPage * ($currentPage - 1))) - $count;
            $count++;
            return $paginate;
        });

        return $paginate;
    }

    /**
     * 해당 id를 가진 문서를 휴지통으로 이동한다.
     *
     * @param array $documentIds
     */
    public function trash($documentIds)
    {
        $items = CptDocument::find($documentIds);
        foreach ($items as $item) {
//            $item->setTrash()->save();
            $item->delete();    //soft delete
        }
    }

    /**
     * 해당 id를 가진 문서를 복원한다.
     *
     * @param array $documentIds
     */

    public function restore($documentIds)
    {
        $items = CptDocument::onlyTrashed()->find($documentIds);
        foreach ($items as $item) {
//            $item->setRestore()->save();
            $item->restore();
        }
    }

    /**
     * 해당 id를 가진 문서를 삭제한다.
     *
     * @param array $documentIds
     */
    public function remove($documentIds)
    {
        $items = CptDocument::withTrashed()->find($documentIds);
        foreach ($items as $item) {
            $item->setProxyOptions($this->proxyOption($item->instance_id));
            $item->forceDelete();
        }
    }

    public function proxyOption($cpt_id = null)
    {
        $options =[];
        if ($cpt_id != null) {
            $options['table'] = CptDocument::TABLE_NAME;
            $options['id'] = $cpt_id;
        }

        return $options;
    }

    /**
     * has article permission
     *
     * @param CptDocument $item
     * @param UserInterface $user
     * @param IdentifyManager $identifyManager
     * @param bool $force
     *
     * @return bool
     */
    public function hasItemPerm(CptDocument $item, UserInterface $user, IdentifyManager $identifyManager, $force = false)
    {
        $perm = false;
        if ($force === true) {
            $perm = true;
        } elseif ($item->user_id == $user->getId()) {
            $perm = true;
        } elseif ($item->user_id == '' && $user->getId() === null &&
            $identifyManager->identified($item) === true) {
            $perm = true;
        }
        return $perm;
    }

}
