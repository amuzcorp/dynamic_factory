<?php
namespace Overcode\XePlugin\DynamicFactory\Services;

use Illuminate\Support\Collection;
use Overcode\XePlugin\DynamicFactory\Exceptions\NotFoundDocumentException;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryDocumentHandler;
use Overcode\XePlugin\DynamicFactory\Models\Cpt;
use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use Xpressengine\Config\ConfigEntity;
use Xpressengine\Http\Request;
use Xpressengine\User\UserInterface;

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
     * @param null $site_key (없으면 자신의 사이트 문서만, 있으면 해당 사이트의 문서만, all_site 면 모든 사이트의 문서)
     * @return mixed
     */
    public function getItems(Request $request, ConfigEntity $config, $site_key = null)
    {
        $model = CptDocument::division($config->get('cpt_id'));

        $query = $model->where('instance_id', $config->get('cpt_id'));

        // site_key 컬럼을 가지고 있는지
        $hasSiteKey = \Schema::hasColumn('documents', 'site_key');

        if($hasSiteKey == true) {
            if ($site_key == null) {
                $query->where('site_key', \XeSite::getCurrentSiteKey());
            } else if ($site_key == 'all_site') {

            } else {
                $query->where('site_key', $site_key);
            }
        }

        $query->visible();

        $this->handler->makeWhere($query, $request, $config);
        $this->handler->makeOrder($query, $request, $config);

        $perPage = 10;

        $paginate = $query->paginate($perPage)->appends($request->except('page'));

        $total = $paginate->total();
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

    public function getItem($id, UserInterface $user, ConfigEntity $config, $force = false)
    {
        $item = CptDocument::division($config->get('cpt_id'))->find($id);

        if ($item === null) {
            throw new NotFoundDocumentException;
        }

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
     * @return Collection
     */
    public function getItemsByCptIds(array $cpt_ids, UserInterface $user = null, $author = 'any')
    {
        $result_items = new Collection();
        $site_key = \XeSite::getCurrentSiteKey();

        // site_key 컬럼을 가지고 있는지
        $hasSiteKey = \Schema::hasColumn('documents', 'site_key');

        foreach($cpt_ids as $cpt_id) {
            $query = CptDocument::division($cpt_id)->where('instance_id', $cpt_id);
            if($hasSiteKey == true) {
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

    public function getItemsAllCpt(Request $request, $type = 'all')
    {
        $cpt_ids = [];
        $cpts = app('overcode.df.service')->getItemsAll();
        foreach($cpts as $cpt){
            $cpt_ids[] = $cpt->cpt_id;
        }

        $query = CptDocument::whereIn('instance_id', $cpt_ids);

        if($type == 'trash') {
            $query = $query->whereIn('status', [CptDocument::STATUS_TRASH, CptDocument::STATUS_TRASH_NOTICE]);
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
        foreach($items as $item) {
            $item->setTrash()->save();
        }
    }

    /**
     * 해당 id를 가진 문서를 복원한다.
     *
     * @param array $documentIds
     */

    public function restore($documentIds)
    {
        $items = CptDocument::find($documentIds);
        foreach ($items as $item) {
            $item->setRestore()->save();
        }
    }

}
