<?php
namespace Overcode\XePlugin\DynamicFactory\Services;

use Illuminate\Support\Collection;
use Overcode\XePlugin\DynamicFactory\Exceptions\NotFoundDocumentException;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryDocumentHandler;
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

    public function getItems(Request $request, ConfigEntity $config, $id = null)
    {
        $model = CptDocument::division($config->get('cpt_id'));
        $query = $model->where('instance_id', $config->get('cpt_id'));

        $this->handler->makeWhere($query, $request, $config);
        $this->handler->makeOrder($query, $request, $config);

        $paginate = $query->paginate(10)->appends($request->except('page'));

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

        foreach($cpt_ids as $cpt_id) {
            $query = CptDocument::division($cpt_id)->where('instance_id', $cpt_id);
            if($author === 'author') {
                $query = $query->where('user_id', $user->getId());
            }
            $items = $query->get();
            $result_items = $result_items->merge($items);
        }

        return $result_items;
    }
}
