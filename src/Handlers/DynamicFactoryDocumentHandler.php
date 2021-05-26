<?php

namespace Overcode\XePlugin\DynamicFactory\Handlers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Overcode\XePlugin\DynamicFactory\Components\Modules\Cpt\CptModule;
use Overcode\XePlugin\DynamicFactory\Exceptions\AlreadyExistFavoriteHttpException;
use Overcode\XePlugin\DynamicFactory\Exceptions\NotFoundFavoriteHttpException;
use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use Overcode\XePlugin\DynamicFactory\Models\DfFavorite;
use Overcode\XePlugin\DynamicFactory\Models\DfThumb;
use Xpressengine\Category\Models\CategoryItem;
use Xpressengine\Config\ConfigEntity;
use Xpressengine\Counter\Counter;
use Xpressengine\Database\Eloquent\Builder;
use Xpressengine\Document\DocumentHandler;
use Xpressengine\Media\Models\Media;
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


        // 발행시각 정규식 검사
        $published_at = array_get($attributes, 'published_at', '');

        $pattern = "/([0-9]{4})\-(0[1-9]|1[0-2])\-(0[1-9]|[1-2][0-9]|3[0-1])\s(2[0-3]|[01][0-9])\:([0-5][0-9])\:([0-5][0-9])/";
        if (!preg_match($pattern, $published_at)) {
            // 빈값이거나 잘못된 형식일 경우 현재 시각으로
            array_set($attributes, 'published_at', date('Y-m-d H:i:s'));
        }

        if (isset($attributes['cpt_status']) === true) {
            switch ($attributes['cpt_status']) {
                case 'public':
                    $attributes['status'] = CptDocument::STATUS_PUBLIC;
                    $attributes['approved'] = CptDocument::APPROVED_APPROVED;
                    $attributes['display'] = CptDocument::DISPLAY_VISIBLE;
                    break;

                case 'private':
                    $attributes['status'] = CptDocument::STATUS_PRIVATE;
                    $attributes['approved'] = CptDocument::APPROVED_APPROVED;
                    $attributes['display'] = CptDocument::DISPLAY_SECRET;
                    break;

                case 'temp':
                    $attributes['status'] = CptDocument::STATUS_TEMP;
                    $attributes['approved'] = CptDocument::APPROVED_WAITING;
                    $attributes['display'] = CptDocument::DISPLAY_HIDDEN;
                    break;
            }
        }
//        dd($attributes, 1);
        $doc = $this->documentHandler->add($attributes);

        $cptDoc = CptDocument::division($cpt_id)->find($doc->id);

        $this->setFiles($cptDoc, $attributes);
        $this->saveCover($cptDoc, $attributes);

        return $cptDoc;
    }

    public function update(CptDocument $doc, $inputs)
    {
        $attributes = $doc->getAttributes();

        if (isset($inputs['cpt_status']) === true) {
            switch ($inputs['cpt_status']) {
                case 'public':
                    $doc->setPublic();
                    break;

                case 'private':
                    $doc->setPrivate();
                    break;

                case 'temp':
                    $doc->setTemp();
                    break;
            }
        }

        // 발행시각 정규식 검사
        $published_at = array_get($inputs, 'published_at', '');

        $pattern = "/([0-9]{4})\-(0[1-9]|1[0-2])\-(0[1-9]|[1-2][0-9]|3[0-1])\s(2[0-3]|[01][0-9])\:([0-5][0-9])\:([0-5][0-9])/";
        if (!preg_match($pattern, $published_at)) {
            // 빈값이거나 잘못된 형식일 경우 현재 시각으로 unset
            unset($inputs['published_at']);
        }

        foreach ($inputs as $name => $value) {
            if (array_key_exists($name, $attributes)) {
                $doc->{$name} = $value;
            } else if($name == '_hashTags' || $name == '_tags'){
                $doc->{$name} = $value;
            }
        }
        $doc->doc_id = $doc->id;
        $doc->cpt_id = $doc->instance_id;

        $this->documentHandler->put($doc);

        $this->setFiles($doc, $inputs);
        $this->saveCover($doc, $inputs);

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

        if(array_get($data, 'taxOr') == 'Y') {
            $query->where(function ($q) use ($category_items, $data) {
                foreach($category_items as $item_id) {
                    $categoryItem = CategoryItem::find($data);
                    if ($categoryItem !== null) {
                        $q->orWhere('df_taxonomy.item_ids', 'like', '%"' . $item_id . '"%');
                    }
                }
            });
        }else {
            foreach($category_items as $item_id) {
                $categoryItem = CategoryItem::find($data);
                if ($categoryItem !== null) {
                    $query = $query->where('df_taxonomy.item_ids', 'like', '%"' . $item_id . '"%');
                }
            }
        }

        /*if ($request->get('category_item_id') !== null && $request->get('category_item_id') !== '') {
            $categoryItem = CategoryItem::find($request->get('category_item_id'));
            if ($categoryItem !== null) {
                $targetCategoryItemIds = $categoryItem->descendants(false)->get()->pluck('id');

                $query = $query->whereIn('board_category.item_id', $targetCategoryItemIds);
            }
        }*/

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

    protected function saveCover(CptDocument $doc, array $args)
    {
        $fileIds = [];

        if (isset($args['_coverId']) == false || $args['_coverId'] == null) {
            if($doc->thumb != null) {
                $doc->thumb()->delete();
            }
        } else {
            if ($thumbnail = $doc->thumb) {
                if ($thumbnail->df_thumbnail_file_id !== $args['_coverId']) {
                    $this->saveThumb($doc, $args['_coverId']);
                }
            } else {
                $this->saveThumb($doc, $args['_coverId']);
            }
        }

        return $fileIds;
    }

    public function getThumb($docId)
    {
        $thumb = DfThumb::find($docId);

        return $thumb;
    }

    protected function saveThumb(CptDocument $doc, $fileId)
    {
        /** @var \Xpressengine\Media\MediaManager $mediaManager */
        $mediaManager = \App::make('xe.media');

        // find file by document id
        $file = \XeStorage::find($fileId);

        // check file
        if ($file == false) {
            // cover image 를 찾을 수 없음
        }

        // get file
        /**
         * set thumbnail size
         */
        $dimension = 'L';

        $media = \XeMedia::getHandler(Media::TYPE_IMAGE)->getThumbnail(
            $mediaManager->make($file),
            CptModule::THUMBNAIL_TYPE,
            $dimension
        );
        $fileId = $file->id;
        $thumbnailPath = $media->url();
        $externalPath = '';

        $model = DfThumb::find($doc->id);
        if ($model === null) {
            $model = new DfThumb;
        }

        $model->fill([
            'target_id' => $doc->id,
            'df_thumbnail_file_id' => $fileId,
            'df_thumbnail_external_path' => $externalPath,
            'df_thumbnail_path' => $thumbnailPath,
        ]);
        $model->save();
    }


    /**
     * check has favorite
     *
     * @param string $boardId board id
     * @param string $userId  user id
     * @return bool
     */
    public function hasFavorite($DocId, $userId)
    {
        return DfFavorite::where('target_id', $DocId)->where('user_id', $userId)->exists();
    }

    /**
     * add favorite
     * @param string $df Id board id
     * @param string $userId  user id
     */
    public function addFavorite($DocId, $userId)
    {
        if ($this->hasFavorite($DocId, $userId) === true) {
            throw new AlreadyExistFavoriteHttpException;
        }

        $favorite = new DfFavorite;
        $favorite->target_id = $DocId;
        $favorite->user_id = $userId;
        $favorite->save();

        return $favorite;
    }

    /**
     * remove favorite
     *
     * @param string $boardId board id
     * @param string $userId  user id
     * @return void
     */
    public function removeFavorite($DocId, $userId)
    {
        if ($this->hasFavorite($DocId, $userId) === false) {
            throw new NotFoundFavoriteHttpException;
        }

        DfFavorite::where('target_id', $DocId)->where('user_id', $userId)->delete();
    }

}
