<?php

namespace Overcode\XePlugin\DynamicFactory\Handlers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Overcode\XePlugin\DynamicFactory\Components\Modules\Cpt\CptModule;
use Overcode\XePlugin\DynamicFactory\Exceptions\AlreadyExistFavoriteHttpException;
use Overcode\XePlugin\DynamicFactory\Exceptions\NotFoundFavoriteHttpException;
use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use Overcode\XePlugin\DynamicFactory\Models\DfFavorite;
use Overcode\XePlugin\DynamicFactory\Models\DfThumb;
use Overcode\XePlugin\DynamicFactory\Models\User as XeUser;
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

    /**
     * get read counter
     *
     * @return Counter
     */
    public function getReadCounter()
    {
        return $this->readCounter;
    }

    /**
     * get vote counter
     *
     * @return Counter
     */
    public function getVoteCounter()
    {
        return $this->voteCounter;
    }

    /**
     * 글 등록
     *
     * @param $attributes
     * @return CptDocument
     */
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

        $doc = $this->documentHandler->add($attributes);

        $cptDoc = CptDocument::division($cpt_id)->find($doc->id);

        $this->setFiles($cptDoc, $attributes);
        $this->saveCover($cptDoc, $attributes);

        return $cptDoc;
    }

    /**
     * 글 수정
     *
     * @param CptDocument $doc
     * @param $inputs
     * @return CptDocument
     */
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

    /**
     * increment read count
     *
     * @param CptDocument $doc
     * @param UserInterface $user
     * @return void
     */
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
        if($request->get('status','') != '') $query->whereIn('status',explode(",",$request->get('status','')));
        if($request->get('display','') != '') $query->whereIn('display',explode(",",$request->get('display','')));

        if($request->get('document_ids','') != ''){
            $documentIDs = is_array($request->get('document_ids')) ? $request->get('document_ids') : json_dec($request->get('document_ids'));
            $query->whereIn('id', $documentIDs);
        }

        //검색한 ID의 문서가 Related 된 문서만 조회
        if($request->get('belong_document_id','') != '') {
            $belong_document_id = $request->get('belong_document_id');
            $target_field = $request->get('target_field') ?: '';
            $target_document = $request->get('target_document') ?: '';
            $target_type = $request->get('target_type') ?: '';
            if ($target_field !== '' && $target_document !== '') {
                $targetDocumentIds = [];
                if ($target_type === 'user') {
                    $targetDocument = XeUser::where('id', $belong_document_id)->first();
                    if ($targetDocument) {
                        $targetDocumentIds = $targetDocument->hasDocument($target_field, $target_document)->pluck('id');
                    }
                } else {
                    $targetDocument = CptDocument::where('id', $belong_document_id)->first();
                    if ($targetDocument) {
                        $targetDocumentIds = $targetDocument->belongDocument($target_field, $target_document)->pluck('id');
                    }
                }
                if (count($targetDocumentIds) !== 0) {
                    $query->whereIn('id', $targetDocumentIds);
                }
            }
        }

        //!!!!!!검색어 검색!!!!!!
        if($request->get('search_keyword', '') !== '') {
            if ($request->get('search_target') == 'title') {
                $query = $query->where(
                    'title',
                    'like',
                    sprintf('%%%s%%', implode('%', explode(' ', $request->get('search_keyword'))))
                );
            }

            if ($request->get('search_target') == 'title_start') {
                $query = $query->where(
                    'title',
                    'like',
                    sprintf('%s%%', implode('%', explode(' ', $request->get('search_keyword'))))
                );
            }

            if ($request->get('search_target') == 'title_end') {
                $query = $query->where(
                    'title',
                    'like',
                    sprintf('%%%s', implode('%', explode(' ', $request->get('search_keyword'))))
                );
            }

            if ($request->get('search_target') == 'pure_content') {
                $query = $query->where(
                    'pure_content',
                    'like',
                    sprintf('%%%s%%', implode('%', explode(' ', $request->get('search_keyword'))))
                );
            }

            if ($request->get('search_target') == 'title_pure_content') {
                $query = $query->whereNested(function ($query) use ($request) {
                    $query->where(
                        'title',
                        'like',
                        sprintf('%%%s%%', implode('%', explode(' ', $request->get('search_keyword'))))
                    )->orWhere(
                        'pure_content',
                        'like',
                        sprintf('%%%s%%', implode('%', explode(' ', $request->get('search_keyword'))))
                    );
                });
            }

            if ($request->get('search_target') == 'writer') {
                $query = $query->where('writer', 'like', sprintf('%%%s%%', $request->get('search_keyword')));
            }
            //작성자 ID 검색
            if ($request->get('search_target') == 'writerId') {
                $writers = \XeUser::where(
                    'email',
                    'like',
                    '%' . $request->get('search_keyword') . '%'
                )->selectRaw('id')->get();

                $writerIds = [];
                foreach ($writers as $writer) {
                    $writerIds[] = $writer['id'];
                }

                $query = $query->whereIn('user_id', $writerIds);
            }
        }
        //!!!!!!검색어 검색!!!!!!

        if ($request->get('title_pure_content','') !== '') {
            $query->whereNested(function ($q) use ($request) {
                $q->where('title', 'like', sprintf('%%%s%%', $request->get('title_pure_content')))
                    ->orWhere('pure_content', 'like', sprintf('%%%s%%', $request->get('title_pure_content')));
            });
        }

        if ($request->get('title_content','') !== '') {
            $query->whereNested(function ($q) use ($request) {
                $q->where('title', 'like', sprintf('%%%s%%', $request->get('title_content')))
                    ->orWhere('content', 'like', sprintf('%%%s%%', $request->get('title_content')));
            });
        }

        if ($request->get('writer','') !== '') $query->where('writer', $request->get('writer'));

        if ($request->get('user_id','') !== '') $query = $query->where('user_id', $request->get('user_id'));

        if($request->get('user_ids','') != ''){
            $userIds = is_array($request->get('user_ids')) ? $request->get('user_ids') : json_dec($request->get('user_ids'));
            $query->whereIn('user_id', $userIds);
        }

        // 이 배열은 category_id 가 key 가 되고 item_id를 val에 배열로 넣는다.
        $category_items = [];

        $data = $request->except('_token');
        foreach($data as $id => $value){
            if(strpos($id, 'taxo_', 0) === 0) {
                foreach($value as $val) {
                    if(isset($val)) {
                        $category_id = explode("_",$id);
                        if(is_array($val)){
                            $category_items[$category_id[1]] = $val;
                        }else{
                            if(!isset($category_items[$category_id[1]])) $category_items[$category_id[1]] = [];
                            $category_items[$category_id[1]][] = $val;
                        }
                    }
                }
            }
        }

        if(count($category_items) > 0) {
            $from = $query->getQuery()->from;
            foreach($category_items as $category_id => $selected_items){
                $table_name = "taxonomy_{$category_id}";
                $query->leftJoin("df_taxonomy as " . $table_name, function($leftJoin) use($from,$table_name,$category_id) {
                    $leftJoin->on(sprintf('%s.%s', $from, 'id'),'=',sprintf('%s.%s', $table_name, 'target_id'))
                    ->where($table_name . ".category_id",$category_id);
                });
            }
        }

        if(array_get($data, 'taxOr', 'N') == 'Y') {
            $query->where(function ($q) use ($category_items, $data) {
                foreach($category_items as $item_id) {
                    $categoryItem = CategoryItem::find($data);
                    if ($categoryItem !== null) {
                        $q->orWhere('df_taxonomy.item_ids', 'like', '%"' . $item_id . '"%');
                    }
                }
            });
        }else{
            foreach($category_items as $category_id => $selected_items){
                $table_name = "taxonomy_{$category_id}";
                $query->where(function($q) use ($table_name,$selected_items){
                    foreach($selected_items as $item_id) $q->where($table_name . '.item_ids', 'like', '%"' . (int) $item_id . '"%');
                });
            }
        }

        /*if ($request->get('category_item_id') !== null && $request->get('category_item_id') !== '') {
            $categoryItem = CategoryItem::find($request->get('category_item_id'));
            if ($categoryItem !== null) {
                $targetCategoryItemIds = $categoryItem->descendants(false)->get()->pluck('id');

                $query = $query->whereIn('board_category.item_id', $targetCategoryItemIds);
            }
        }*/

        if ($request->get('start_created_at','') !== '') $query->where('created_at', '>=', $request->get('start_created_at') . ' 00:00:00');
        if ($request->get('end_created_at','') !== '') $query = $query->where('created_at', '<=', $request->get('end_created_at') . ' 23:59:59');

        // Tag 검색
        if ($request->get('searchTag')) {
            //JSON 인코딩 체크
            if(is_array($request->get('searchTag'))) {
                $searchTagName = $request->get('searchTag');
            } else {
                json_decode($request->get('searchTag'));
                if (json_last_error() === 0) {
                    $searchTagName = json_dec($request->get('searchTag'));
                } else {
                    $searchTagName = $request->get('searchTag');
                }
            }

            $targetTags = new Collection;

            if(is_array($searchTagName)) {
                foreach($searchTagName as $tagName) {
                    $tags = \XeTag::similar($tagName, 15, $config->get('boardId'));
                    foreach($tags as $tag) {
                        $targetTags->push($tag);
                    }
                }
            } else {
                $targetTags = \XeTag::similar($searchTagName, 15, $config->get('boardId'));
            }

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
        //쿼리를 느리게만든 주범
//        $query->GroupBy('documents.id')->select('documents.*');
        return $query;
    }

    public function makeOrder(Builder $query, Request $request, ConfigEntity $config)
    {
        $orderType = $request->get('order_type', '');
//        if ($orderType === '' && $config->get('orderType') != null) {
//            $orderType = $config->get('orderType', '');
//        }

        if ($orderType == '') {
            // order_type 이 없을때만 dyFac Config 의 정렬을 우선 적용한다.
            $orders = $request->get('orders', []);
            foreach ($orders as $order) {
                $arr_order = explode('|@|',$order);
                $query->orderBy($arr_order[0], $arr_order[1]);
            }

            $query->orderBy('head', 'desc');
        } elseif ($orderType == 'assent_count') {
            $query->orderBy('assent_count', 'desc')->orderBy('head', 'desc');
        } elseif ($orderType == 'recently_created') {
            $query->orderBy(CptDocument::CREATED_AT, 'desc')->orderBy('head', 'desc');
        } elseif ($orderType == 'recently_published') {
            $query->orderBy('published_at', 'desc')->orderBy('head', 'desc');
        } elseif ($orderType == 'recently_updated') {
            $query->orderBy(CptDocument::UPDATED_AT, 'desc')->orderBy('head', 'desc');
        } elseif ($orderType == 'title_asc') {
            $query->orderBy('title', 'asc')->orderBy('head', 'desc');
        } elseif ($orderType == 'title_desc') {
            $query->orderBy('title', 'desc')->orderBy('head', 'desc');
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
    public function hasFavorite($DocId, $userId = null)
    {
        if($userId == null) $userId = auth()->user()->getId();
        return DfFavorite::where('target_id', $DocId)->where('user_id', $userId)->exists();
    }

    /**
     * add favorite
     * @param string $df Id board id
     * @param string $userId  user id
     */
    public function addFavorite($DocId, $userId = null)
    {
        if($userId == null) $userId = auth()->user()->getId();
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
    public function removeFavorite($DocId, $userId = null)
    {
        if($userId == null) $userId = auth()->user()->getId();
        if ($this->hasFavorite($DocId, $userId) === false) {
            throw new NotFoundFavoriteHttpException;
        }

        DfFavorite::where('target_id', $DocId)->where('user_id', $userId)->delete();
    }

    /**
     * @param CptDocument   $document
     * @param UserInterface $user
     * @param string        $option 'assent' or 'dissent'
     * @param int           $point  vote point
     * @return void
     */
    public function vote(CptDocument $document, UserInterface $user, $option, $point = 1)
    {
        if ($this->voteCounter->has($document->id, $user, $option) === false) {
            $this->incrementVoteCount($document, $user, $option, $point);
        } else {
            $this->decrementVoteCount($document, $user, $option);
        }
    }

    /**
     * increment vote count
     *
     * @param CptDocument   $document
     * @param UserInterface $user   user
     * @param string        $option 'assent' or 'dissent'
     * @param int           $point  vote point
     * @return void
     */
    public function incrementVoteCount(CptDocument $document, UserInterface $user, $option, $point = 1)
    {
        $this->voteCounter->add($document->id, $user, $option, $point);

        $columnName = 'assent_count';
        if ($option == 'dissent') {
            $columnName = 'dissent_count';
        }
        $document->{$columnName} = $this->voteCounter->getPoint($document->id, $option);
        $document->save();
    }

    /**
     * decrement vote count
     *
     * @param CptDocument   $document
     * @param UserInterface $user   user
     * @param string        $option 'assent' or 'dissent'
     * @return void
     */
    public function decrementVoteCount(CptDocument $document, UserInterface $user, $option)
    {
        $this->voteCounter->remove($document->id, $user, $option);

        $columnName = 'assent_count';
        if ($option == 'dissent') {
            $columnName = 'dissent_count';
        }
        $document->{$columnName} = $this->voteCounter->getPoint($document->id, $option);
        $document->save();
    }

    /**
     * has vote
     *
     * @param CptDocument   $document
     * @param UserInterface $user user
     * @param string        $option 'assent' or 'dissent'
     * @return bool
     */
    public function hasVote(CptDocument $document, $user, $option)
    {
        return $this->voteCounter->has($document->id, $user, $option);
    }

}
