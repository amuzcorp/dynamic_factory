<?php

namespace Overcode\XePlugin\DynamicFactory\Handlers;

use Illuminate\Http\Request;
use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use Xpressengine\Category\Models\CategoryItem;
use Xpressengine\Config\ConfigEntity;
use Xpressengine\Counter\Counter;
use Xpressengine\Database\Eloquent\Builder;
use Xpressengine\Database\VirtualConnectionInterface as VirtualConnection;
use Xpressengine\Document\ConfigHandler;
use Xpressengine\Document\DocumentHandler;
use Xpressengine\Document\InstanceManager;
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

        return $this->documentHandler->add($attributes);
    }

    public function update($doc, $inputs)
    {
        $attributes = $doc->getAttributes();

        foreach ($inputs as $name => $value) {
            if (array_key_exists($name, $attributes)) {
                $doc->{$name} = $value;
            }
        }

        return $this->documentHandler->put($doc);
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
}
