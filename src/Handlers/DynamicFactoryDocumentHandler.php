<?php

namespace Overcode\XePlugin\DynamicFactory\Handlers;

use Illuminate\Http\Request;
use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use Xpressengine\Category\Models\CategoryItem;
use Xpressengine\Config\ConfigEntity;
use Xpressengine\Counter\Counter;
use Xpressengine\Database\Eloquent\Builder;
use Xpressengine\Document\DocumentHandler;
use Xpressengine\Storage\File;
use Xpressengine\Storage\Storage;
use Xpressengine\User\UserInterface;

class DynamicFactoryDocumentHandler
{
    /**
     * @var DocumentHandler
     */
    protected $documentHandler;

    /**
     * @var Storage
     */
    protected $storage;

    /**
     * @var Counter
     */
    protected $readCounter;

    /**
     * @var Counter
     */
    protected $voteCounter;


    public function __construct(
        DocumentHandler $documentHandler,
        Storage $storage,
        Counter $readCounter,
        Counter $voteCounter
    )
    {
        $this->documentHandler = $documentHandler;
        $this->storage = $storage;
        $this->readCounter = $readCounter;
        $this->voteCounter = $voteCounter;
    }

    public function store($attributes)
    {
        $cpt_id = $attributes['cpt_id'];

        $attributes['instance_id'] = $cpt_id;
        $attributes['type'] = $cpt_id;

        $doc = $this->documentHandler->add($attributes);

        $cptDoc = CptDocument::division($cpt_id)->find($doc->id);

        $this->setFiles($cptDoc, $attributes);

        return $cptDoc;
    }

    public function update($doc, $inputs)
    {
        $attributes = $doc->getAttributes();

        foreach ($inputs as $name => $value) {
            if (array_key_exists($name, $attributes)) {
                $doc->{$name} = $value;
            }
        }

        $this->documentHandler->put($doc);

        $this->setFiles($doc, $inputs);

        return $doc->find($doc->id);
    }

    public function incrementReadCount(CptDocument $doc, UserInterface $user)
    {
        if ($this->readCounter->has($doc->id, $user) === false) {
            $this->readCounter->add($doc->id, $user);
        }

        $doc->read_count = $this->readCounter->getPoint($doc->id);
        $doc->timestamps = false;
        $doc->save();
    }

    public function makeWhere(Builder $query, Request $request, ConfigEntity $config)
    {
        if ($request->get('title_pure_content') != null && $request->get('title_pure_content') !== '') {
            $query = $query->whereNested(function ($query) use ($request) {
                $query->where('title', 'like', sprintf('%%%s%%', $request->get('title_pure_content')))
                    ->orWhere('pure_content', 'like', sprintf('%%%s%%', $request->get('title_pure_content')));
            });
        }

        if ($request->get('title_content') != null && $request->get('title_content') !== '') {
            $query = $query->whereNested(function ($query) use ($request) {
                $query->where('title', 'like', sprintf('%%%s%%', $request->get('title_content')))
                    ->orWhere('content', 'like', sprintf('%%%s%%', $request->get('title_content')));
            });
        }

        if ($request->get('writer') != null && $request->get('writer') !== '') {
            $query = $query->where('writer', $request->get('writer'));
        }

        if ($request->get('user_id') !== null && $request->get('user_id') !== '') {
            $query = $query->where('user_id', $request->get('user_id'));
        }

        $category_items = [];

        $data = $request->except('_token');
        foreach($data as $id => $value){
            if(strpos($id, 'taxo_', 0) === 0) {
                foreach($value as $val) {
                    if(isset($val)) {
                        $category_items[] = $val;
                    }
                }
            }
        }

        if(count($category_items) > 0) {
            $query->leftJoin(
                'df_taxonomy',
                sprintf('%s.%s', $query->getQuery()->from, 'id'),
                '=',
                sprintf('%s.%s', 'df_taxonomy', 'target_id')
            );
        }

        foreach($category_items as $item_id) {
            $categoryItem = CategoryItem::find($data);
            if ($categoryItem !== null) {
                $query = $query->where('df_taxonomy.item_ids', 'like', '%"'. $item_id .'"%');
            }

        }

        if ($request->get('category_item_id') !== null && $request->get('category_item_id') !== '') {
            $categoryItem = CategoryItem::find($request->get('category_item_id'));
            if ($categoryItem !== null) {
                $targetCategoryItemIds = $categoryItem->descendants(false)->get()->pluck('id');

                $query = $query->whereIn('board_category.item_id', $targetCategoryItemIds);
            }
        }

        if ($request->get('start_created_at') != null && $request->get('start_created_at') !== '') {
            $query = $query->where('created_at', '>=', $request->get('start_created_at') . ' 00:00:00');
        }

        if ($request->get('end_created_at') != null && $request->get('end_created_at') !== '') {
            $query = $query->where('created_at', '<=', $request->get('end_created_at') . ' 23:59:59');
        }

        if ($searchTagName = $request->get('searchTag')) {
            $targetTags = \XeTag::similar($searchTagName, 15, $config->get('boardId'));

            $tagUsingBoardItemIds = [];
            foreach ($targetTags as $targetTag) {
                $tagUsingBoardItems = \XeTag::fetchByTag($targetTag['id']);

                foreach ($tagUsingBoardItems as $tagUsingBoardItem) {
                    $tagUsingBoardItemIds[] = $tagUsingBoardItem->taggable_id;
                }
            }

            $tagUsingBoardItemIds = array_unique($tagUsingBoardItemIds);

            $query = $query->whereIn('id', $tagUsingBoardItemIds);
        }

        $query->getProxyManager()->wheres($query->getQuery(), $request->all());

        return $query;
    }

    public function makeOrder(Builder $query, Request $request, ConfigEntity $config)
    {
        $orderType = $request->get('order_type', '');
        if ($orderType === '' && $config->get('orderType') != null) {
            $orderType = $config->get('orderType', '');
        }

        if ($orderType == '') {
            $query->orderBy('head', 'desc');
        } elseif ($orderType == 'assent_count') {
            $query->orderBy('assent_count', 'desc')->orderBy('head', 'desc');
        } elseif ($orderType == 'recently_created') {
            $query->orderBy(CptDocument::CREATED_AT, 'desc')->orderBy('head', 'desc');
        } elseif ($orderType == 'recently_updated') {
            $query->orderBy(CptDocument::UPDATED_AT, 'desc')->orderBy('head', 'desc');
        }

        $query->getProxyManager()->orders($query->getQuery(), $request->all());

        return $query;
    }

    /**
     * set files
     *
     * @param CptDocument $doc
     * @param array $args
     * @return array
     */
    protected function setFiles(CptDocument $doc, array $args)
    {
        $fileIds = [];
        if (empty($args['_files']) === false) {
            $this->storage->sync($doc->getKey(), $args['_files']);
        }
        return $fileIds;
    }

    protected function unsetFiles(CptDocument $doc, array $fileIds)
    {
        $files = File::whereIn('id', array_diff($doc->getFileIds(), $fileIds))->get();
        foreach ($files as $file) {
            $this->storage->unBind($doc->id, $file, true);
        }
    }

}
