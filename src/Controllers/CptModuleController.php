<?php

namespace Overcode\XePlugin\DynamicFactory\Controllers;

use App\Http\Sections\SkinSection;
use Overcode\XePlugin\DynamicFactory\Models\Cpt;
use Overcode\XePlugin\DynamicFactory\Plugin;
use Overcode\XePlugin\DynamicFactory\Components\Modules\Cpt\CptModule;
use Overcode\XePlugin\DynamicFactory\Exceptions\NotFoundDocumentException;
use Overcode\XePlugin\DynamicFactory\Handlers\CptPermissionHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryDocumentHandler;
use Overcode\XePlugin\DynamicFactory\IdentifyManager;
use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use Overcode\XePlugin\DynamicFactory\Models\DfSlug;
use Overcode\XePlugin\DynamicFactory\Services\CptDocService;
use Auth;
use Gate;
use Overcode\XePlugin\DynamicFactory\Services\DynamicFactoryService;
use Overcode\XePlugin\DynamicFactory\Validator;
use XeFrontend;
use XePresenter;
use XeSEO;
use App\Http\Controllers\Controller;
use Overcode\XePlugin\DynamicFactory\Handlers\CptModuleConfigHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\CptUrlHandler;
use Xpressengine\Counter\Exceptions\GuestNotSupportException;
use Xpressengine\Editor\PurifierModules\EditorContent;
use Xpressengine\Http\Request;
use Xpressengine\Media\MediaManager;
use Xpressengine\Media\Models\Media;
use Xpressengine\Permission\Instance;
use Xpressengine\Routing\InstanceConfig;
use Xpressengine\Routing\InstanceRoute;
use Xpressengine\Support\Exceptions\AccessDeniedHttpException;
use Xpressengine\Support\Exceptions\HttpXpressengineException;
use Xpressengine\Support\Purifier;
use Xpressengine\Support\PurifierModules\Html5;
use Xpressengine\User\Models\User;

class CptModuleController extends Controller
{
    protected $instanceId;
    public $cptUrlHandler;
    public $configHandler;
    public $dfDocHandler;
    public $config;
    public $cpt;
    public $taxonomyHandler;
    public $dfService;
    public $identifyManager;

    public function __construct(
        CptModuleConfigHandler $configHandler,
        CptUrlHandler $cptUrlHandler,
        DynamicFactoryDocumentHandler $dfDocHandler,
        DynamicFactoryService $dynamicFactoryService,
        IdentifyManager $identifyManager
    )
    {
        $instanceConfig = InstanceConfig::instance();
        $this->instanceId = $instanceConfig->getInstanceId();
        $instance = InstanceRoute::where('instance_id',$this->instanceId)->first();

        $this->configHandler = $configHandler;
        $this->cptUrlHandler = $cptUrlHandler;
        $this->dfDocHandler = $dfDocHandler;
        $this->config = $configHandler->get($this->instanceId);
        if ($this->config !== null) {
            $cptUrlHandler->setInstanceId($this->config->get('instanceId'));
            $cptUrlHandler->setConfig($this->config);
        }
        $this->taxonomyHandler = app('overcode.df.taxonomyHandler');
        $this->dfService = $dynamicFactoryService;
        $this->identifyManager = $identifyManager;

        $this->cpt = $this->dfService->getItem($this->config->get('cpt_id'));
        $current_route = app('request')->route();

        XePresenter::setSkinTargetId($instance['module']);
        XePresenter::share('cpt', $this->cpt);
        XePresenter::share('current_instance_route', ($current_route != null) ? $current_route->getName() : null);
        XePresenter::share('documentHandler', $dfDocHandler);
        XePresenter::share('configHandler', $configHandler);
        XePresenter::share('cptUrlHandler', $cptUrlHandler);
        XePresenter::share('instanceId', $this->instanceId);
        XePresenter::share('config', $this->config);

        //공용으로 쓰이는 js 모음
        app('xe.frontend')->js('assets/core/xe-ui-component/js/xe-form.js')->load();
        app('xe.frontend')->js('assets/core/xe-ui-component/js/xe-page.js')->load();
        app('xe.frontend')->js('assets/core/plugin/js/plugin-index.js')->before(
            [
                'assets/core/xe-ui-component/js/xe-page.js',
                'assets/core/xe-ui-component/js/xe-form.js'
            ]
        )->load();
        app('xe.frontend')->js([Plugin::asset('assets/cpt_module_common.js')])->load();
    }

    public function index(CptDocService $service, Request $request, CptPermissionHandler $cptPermissionHandler)
    {
        if (Gate::denies(
            CptPermissionHandler::ACTION_LIST,
            new Instance($cptPermissionHandler->name($this->instanceId))
        )) {
            throw new AccessDeniedHttpException;
        }

        \XeFrontend::title($this->getSiteTitle());

        $cpt_id = $this->config->get('cpt_id');

        $site_key = \XeSite::getCurrentSiteKey();

        $dfConfig = app('overcode.df.configHandler')->getConfig($cpt_id);
        $column_labels = app('overcode.df.configHandler')->getColumnLabels($dfConfig);

        $taxonomies = $this->taxonomyHandler->getTaxonomies($cpt_id);
        $categories = [];

        foreach($taxonomies as $taxonomy) {
            $categories[$taxonomy->id]['group'] = $this->taxonomyHandler->getTaxFieldGroup($taxonomy->id);
            $categories[$taxonomy->id]['items'] = $this->taxonomyHandler->getCategoryItemAttributes($taxonomy->id,$categories[$taxonomy->id]['group']);
        }

        $paginate = $service->getItems($request, $this->config, '*');

        //미디어 라이브러리 추가 (미디어 필드가 있는경우에 사용해야함)
        /** @var MediaManager $mediaManager */
        $mediaManager = app('xe.media');
        $imageHandler = $mediaManager->getHandler(Media::TYPE_IMAGE);

        return XePresenter::makeAll('index', [
            'paginate' => $paginate,
            'dfConfig' => $dfConfig,
            'column_labels' => $column_labels,
            'taxonomies' => $taxonomies,
            'categories' => $categories,
            'imageHandler' => $imageHandler
        ]);
    }

    public function show(
        CptDocService $service,
        Request $request,
        CptPermissionHandler $cptPermissionHandler,
        $menuUrl,
        $id
    )
    {
        if (Gate::denies(
            CptPermissionHandler::ACTION_READ,
            new Instance($cptPermissionHandler->name($this->instanceId))
        )) {
            throw new AccessDeniedHttpException;
        }

        $user = Auth::user();
        $item = $service->getItem($id, $user, $this->config);

        if ($this->config->get('useConsultation') === true
            && $service->hasItemPerm($item, $user, $this->identifyManager, $this->isManager()) == false
        ) {
            throw new AccessDeniedHttpException;
        }

        // 글 조회수 증가
        if ($item->display == CptDocument::DISPLAY_VISIBLE) {
            $this->dfDocHandler->incrementReadCount($item, Auth::user());
        }

        $dyFacConfig = app('overcode.df.configHandler')->getConfig($this->config->get('cpt_id'));
        $fieldTypes = $service->getFieldTypes($dyFacConfig);

        $dynamicFieldsById = [];
        foreach ($fieldTypes as $fieldType) {
            $dynamicFieldsById[$fieldType->get('id')] = $fieldType;
        }

        $select_category_items = $this->taxonomyHandler->getItemOnlyTargetId($id);

        //미디어 라이브러리 추가 (미디어 필드가 있는경우에 사용해야함)
        /** @var MediaManager $mediaManager */
        $mediaManager = app('xe.media');
        $imageHandler = $mediaManager->getHandler(Media::TYPE_IMAGE);

        return XePresenter::make('show', compact('item','fieldTypes','dynamicFieldsById', 'select_category_items', 'imageHandler'));
    }

    /**
     * 문자열을 넘겨 slug 반환
     *
     * @param Request $request request
     * @return mixed
     */
    public function hasSlug(Request $request)
    {
        $slugText = DfSlug::convert('', $request->get('slug'));
        $slug = DfSlug::make($slugText, $request->get('id'));

        return XePresenter::makeApi([
            'slug' => $slug
        ]);
    }

    public function slug(
        CptDocService $service,
        Request $request,
        CptPermissionHandler $cptPermissionHandler,
        $menuUrl,
        $strSlug)
    {
        $cpt_id = $this->config->get('cpt_id');

        $slug = DfSlug::where('slug', $strSlug)->where('instance_id', $cpt_id)->first();

        if ($slug === null) {
            throw new NotFoundDocumentException;
        }

        return $this->show($service, $request, $cptPermissionHandler, $menuUrl, $slug->target_id);
    }

    public function create(
        CptDocService $service,
        Request $request,
        Validator $validator,
        CptPermissionHandler $cptPermission
    )
    {
        if (Gate::denies(
            CptPermissionHandler::ACTION_CREATE,
            new Instance($cptPermission->name($this->instanceId))
        )) {
            throw new AccessDeniedHttpException;
        }

        // if use consultation option Guest cannot create article
        if ($this->config->get('useConsultation') === true && Auth::check() === false) {
            throw new AccessDeniedHttpException;
        }

        $cpt_id = $this->config->get('cpt_id');

        $taxonomies = $this->taxonomyHandler->getTaxonomies($cpt_id);
        $rules = $validator->getCreateRule(Auth::user(), $this->config);

        $cptConfig = $this->dfService->getCptConfig($cpt_id);
        $fieldTypes = $service->getFieldTypes($cptConfig);

        $dynamicFieldsById = [];
        foreach ($fieldTypes as $fieldType) {
            $dynamicFieldsById[$fieldType->get('id')] = $fieldType;
        }

        return XePresenter::make('create', [
            'taxonomies' => $taxonomies,
            'rules' => $rules,
            'head' => '',
            'fieldTypes' => $fieldTypes,
            'cptConfig' => $cptConfig,
            'dynamicFieldsById' => $dynamicFieldsById
        ]);
    }

    public function store(
        CptDocService $service,
        Request $request,
        Validator $validator,
        CptPermissionHandler $cptPermission
    ) {
        if (Gate::denies(
            CptPermissionHandler::ACTION_CREATE,
            new Instance($cptPermission->name($this->instanceId))
        )) {
            throw new AccessDeniedHttpException;
        }

        // if use consultation option Guest cannot create article
        if ($this->config->get('useConsultation') === true && Auth::check() === false) {
            throw new AccessDeniedHttpException;
        }

        $purifier = new Purifier();
        $purifier->allowModule(EditorContent::class);
        $purifier->allowModule(HTML5::class);

        $inputs = $request->all();
        $originInputs = $request->originAll();
        $inputs['title'] = htmlspecialchars($originInputs['title'], ENT_COMPAT | ENT_HTML401, 'UTF-8', false);

        if ($this->isManager()) {
            $inputs['content'] = $originInputs['content'];
        } else {
            $inputs['content'] = $purifier->purify($originInputs['content']);
        }

        $request->replace($inputs);

        // 유효성 체크
        $this->validate($request, $validator->getCreateRule(Auth::user(), $this->config));

        // 공지 등록 권한 확인
        // 비밀글 등록 설정 확인

        $request->request->add(['cpt_id' => $this->config->get('cpt_id')]); // cpt_id 추가
        $item = $this->dfService->storeCptDocument($request);

        return XePresenter::redirect()
            ->to($this->cptUrlHandler->getShow($item, $request->query->all()))
            ->setData(['item' => $item]);
    }

    public function edit(
        CptDocService $service,
        Request $request,
        Validator $validator,
        IdentifyManager $identifyManager,
        $menuUrl,
        $id
    ) {
        //$item = CptDocument::division($this->instanceId)->find($id);
        $user = Auth::user();
        $item = $service->getItem($id, $user, $this->config);

        if ($item == null) {
            throw new NotFoundDocumentException;
        }

        // 비회원이 작성 한 글일 때 인증페이지로 이동
        if ($this->isManager() !== true &&
            $item->isGuest() === true &&
            $identifyManager->identified($item) === false &&
            Auth::user()->getRation() != 'super') {
            return xe_redirect()->to($this->cptUrlHandler->get('guest.id', [
                'id' => $item->id,
                'referrer' => app('url')->current(),
            ]));
        }

        // 접근 권한 확인
        if ($service->hasItemPerm($item, Auth::user(), $identifyManager, $this->isManager()) == false) {
            throw new AccessDeniedHttpException;
        }

        $thumb = $this->dfDocHandler->getThumb($item->id);

        $cpt_id = $this->config->get('cpt_id');

        $taxonomies = $this->taxonomyHandler->getTaxonomies($cpt_id);
        $rules = $validator->getEditRule(Auth::user(), $this->config);

        $cptConfig = $this->dfService->getCptConfig($cpt_id);
        $fieldTypes = $service->getFieldTypes($cptConfig);

        $dynamicFieldsById = [];
        foreach ($fieldTypes as $fieldType) {
            $dynamicFieldsById[$fieldType->get('id')] = $fieldType;
        }

        XeSEO::notExec();

        return XePresenter::make('edit', [
            'item' => $item,
            'thumb' => $thumb,
            'taxonomies' => $taxonomies,
            'rules' => $rules,
            'parent' => null,
            'fieldTypes' => $fieldTypes,
            'cptConfig' => $cptConfig,
            'dynamicFieldsById' => $dynamicFieldsById
        ]);
    }

    //destroy
    public function destroy(
        CptDocService $service,
        Request $request,
        Validator $validator,
        IdentifyManager $identifyManager,
        $menuUrl,
        $id
    ){
        $this->validate($request, [
            'id' => 'cpt_id',
        ]);

        $remove = CptDocument::where('id', $request->id)->delete();
        if(!$remove) return redirect()->back()->with('alert', ['type' => 'danger', 'message' => '오류가 발생했습니다']);

        if($request->get('redirectUrl') != null){
            return redirect()->to($request->get('redirectUrl'))->with('alert', ['type' => 'success', 'message' => xe_trans('xe::deleted')]);
        }else{
            return redirect()->to($this->cptUrlHandler->get('index'))->with('alert', ['type' => 'success', 'message' => xe_trans('xe::deleted')]);
//            return redirect()->back()->with('alert', ['type' => 'success', 'message' => xe_trans('xe::deleted')]);
        }
    }

    public function update(
        CptDocService $service,
        Request $request,
        Validator $validator,
        IdentifyManager $identifyManager,
        $menuUrl
    ) {
        $item = CptDocument::division($this->instanceId)->find($request->get('id'));

        // 비회원이 작성 한 글 인증
        if ($this->isManager() !== true &&
            $item->isGuest() === true &&
            $identifyManager->identified($item) === false &&
            Auth::user()->getRating() != 'super') {
            return xe_redirect()->to($this->cptUrlHandler->get('guest.id', [
                'id' => $item->id,
                'referrer' => $this->cptUrlHandler->get('edit', ['id' => $item->id]),
            ]));
        }

        $purifier = new Purifier();
        $purifier->allowModule(EditorContent::class);
        $purifier->allowModule(HTML5::class);

        $inputs = $request->all();
        $originInputs = $request->originAll();
        $inputs['title'] = htmlspecialchars($originInputs['title'], ENT_COMPAT | ENT_HTML401, 'UTF-8', false);

        if ($this->isManager()) {
            $inputs['content'] = $originInputs['content'];
        } else {
            $inputs['content'] = $purifier->purify($originInputs['content']);
        }

        $request->replace($inputs);

        $this->validate($request, $validator->getEditRule(Auth::user(), $this->config));

        if ($service->hasItemPerm($item, Auth::user(), $identifyManager, $this->isManager()) == false) {
            throw new AccessDeniedHttpException;
        }

        $request->request->add(['cpt_id' => $this->config->get('cpt_id')]); // cpt_id 추가
        $request->request->add(['doc_id' => $request->get('id')]);  // doc_id 추가
        $item = $this->dfService->updateCptDocument($request);

        return XePresenter::redirect()
            ->to($this->cptUrlHandler->getShow($item, $request->query->all()))
            ->setData(['item' => $item]);
    }

    /**
     * is manager
     *
     * @return bool
     */
    protected function isManager()
    {
        $cptPermission = app('overcode.df.permission');
        return Gate::allows(
            CptPermissionHandler::ACTION_MANAGE,
            new Instance($cptPermission->name($this->instanceId))
        ) ? true : false;
    }

    /**
     * get site title
     *
     * @return string
     */
    public function getSiteTitle()
    {
        $siteTitle = \XeFrontend::output('title');

        $instanceConfig = InstanceConfig::instance();
        $menuItem = $instanceConfig->getMenuItem();

        $title = xe_trans($menuItem['title']) . ' - ' . xe_trans($siteTitle);
        $title = strip_tags(html_entity_decode($title));

        return $title;
    }

    public function favorite(Request $request)
    {
        $id = $request->id;
        if (Auth::check() === false) {
            throw new AccessDeniedHttpException;
        }
        $item = app('overcode.doc.service')->getItemOnlyId($id);

        $userId = Auth::user()->getId();
        $favorite = false;
        if ($this->dfDocHandler->hasFavorite($item->id, $userId) === false) {
            $this->dfDocHandler->addFavorite($item->id, $userId);
            $favorite = true;
        } else {
            $this->dfDocHandler->removeFavorite($item->id, $userId);
        }

        return \XePresenter::makeApi(['favorite' => $favorite]);
    }

    /**
     * 투표 정보
     *
     * @param Request $request request
     * @param string  $id      document id
     * @return \Xpressengine\Presenter\Presentable
     */
    public function showVote(Request $request, $id)
    {
        // display 설정
        $display =['assent' => true, 'dissent' => true];
        if ($this->config->get('assent') !== true) {
            $display['assent'] = false;
        }

        if ($this->config->get('dissent') !== true) {
            $display['dissent'] = false;
        }

        $user = Auth::user();

        $item = CptDocument::division($this->instanceId)->find($id);

        $voteCounter = $this->dfDocHandler->getVoteCounter();
        $vote = $voteCounter->getByName($id, $user);

        return XePresenter::makeApi([
            'display' => $display,
            'id' => $id,
            'counts' => [
                'assent' => $item->assent_count,
                'dissent' => $item->dissent_count,
            ],
            'voteAt' => $vote ? $vote->counter_option : null,
        ]);
    }

    /**
     * 좋아요 추가, 삭제
     *
     * @param Request $request request
     * @param string  $menuUrl first segment
     * @param string  $option  options
     * @param string  $id      document id
     * @return \Xpressengine\Presenter\Presentable
     */
    public function vote(Request $request, $menuUrl, $option, $id)
    {
        $author = Auth::user();

        $item = CptDocument::division($this->instanceId)->find($id);

        try {
            $this->dfDocHandler->vote($item, $author, $option);
        } catch (GuestNotSupportException $e) {
            throw new AccessDeniedHttpException;
        }

        return $this->showVote($request, $id);
    }

    /**
     * get voted user list
     *
     * @param Request $request request
     * @param string  $menuUrl first segment
     * @param string  $option  options
     * @param string  $id      document id
     * @return mixed
     */
    public function votedUsers(Request $request, $menuUrl, $option, $id)
    {
        $limit = $request->get('limit', 10);

        $item = CptDocument::division($this->instanceId)->find($id);

        $counter = $this->dfDocHandler->getVoteCounter();
        $logModel = $counter->newModel();
        $logs = $logModel->where('counter_name', $counter->getName())->where('target_id', $id)
            ->where('counter_option', $option)->take($limit)->get();

        return api_render('votedUsers', [
            'urlHandler' => $this->cptUrlHandler,
            'option' => $option,
            'item' => $item,
            'logs' => $logs,
        ]);
    }

    /**
     * get voted user modal
     *
     * @param Request $request request
     * @param string  $menuUrl first segment
     * @param string  $option  options
     * @param string  $id      document id
     * @return mixed
     */
    public function votedModal(Request $request, $menuUrl, $option, $id)
    {
        $item = CptDocument::division($this->instanceId)->find($id);

        $counter = $this->dfDocHandler->getVoteCounter();
        $logModel = $counter->newModel();
        $count = $logModel->where('counter_name', $counter->getName())->where('target_id', $id)
            ->where('counter_option', $option)->count();

        return api_render('votedModal', [
            'urlHandler' => $this->cptUrlHandler,
            'option' => $option,
            'item' => $item,
            'count' => $count,
        ]);
    }

    /**
     * get voted user list
     *
     * @param Request $request request
     * @param string  $menuUrl first segment
     * @param string  $option  options
     * @param string  $id      document id
     * @return mixed
     */
    public function votedUserList(Request $request, $menuUrl, $option, $id)
    {
        $startId = $request->get('startId');
        $limit = $request->get('limit', 10);

        $item = CptDocument::division($this->instanceId)->find($id);

        $counter = $this->dfDocHandler->getVoteCounter();
        $logModel = $counter->newModel();
        $query = $logModel->where('counter_name', $counter->getName())->where('target_id', $id)
            ->where('counter_option', $option);

        if ($startId != null) {
            $query->where('id', '<', $startId);
        }

        $logs = $query->orderBy('id', 'desc')->take($limit)->get();
        $list = [];
        foreach ($logs as $log) {
            /** @var User $user */
            $user = $log->user;
            $profilePage = '#';
            if ($user->getId() != '') {
                $profilePage = route('user.profile', ['user' => $user->getId()]);
            }
            $list[] = [
                'id' => $user->getId(),
                'displayName' => $user->getDisplayName(),
                'profileImage' => $user->getProfileImage(),
                'createdAt' => (string)$log->created_at,
                'profilePage' => $profilePage,
            ];
        }

        $nextStartId = 0;
        if (count($logs) == $limit) {
            $nextStartId = $logs->last()->id;
        }

        return XePresenter::makeApi([
            'item' => $item,
            'list' => $list,
            'nextStartId' => $nextStartId,
        ]);
    }
}
