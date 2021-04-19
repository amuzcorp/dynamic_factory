<?php

namespace Overcode\XePlugin\DynamicFactory\Controllers;

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
use Xpressengine\Editor\PurifierModules\EditorContent;
use Xpressengine\Http\Request;
use Xpressengine\Permission\Instance;
use Xpressengine\Routing\InstanceConfig;
use Xpressengine\Support\Exceptions\AccessDeniedHttpException;
use Xpressengine\Support\Purifier;
use Xpressengine\Support\PurifierModules\Html5;

class CptModuleController extends Controller
{
    protected $instanceId;

    public $cptUrlHandler;

    public $configHandler;

    public $dfDocHandler;

    public $config;

    protected $taxonomyHandler;

    protected $dfService;

    protected $identifyManager;

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

        XePresenter::setSkinTargetId(CptModule::getId());

        $current_route = app('request')->route();
        XePresenter::share('current_instance_route', ($current_route != null) ? $current_route->getName() : null);
        XePresenter::share('configHandler', $configHandler);
        XePresenter::share('cptUrlHandler', $cptUrlHandler);
        XePresenter::share('instanceId', $this->instanceId);
        XePresenter::share('config', $this->config);
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

        $dfConfig = app('overcode.df.configHandler')->getConfig($cpt_id);
        $column_labels = app('overcode.df.configHandler')->getColumnLabels($dfConfig);

        $taxonomies = $this->taxonomyHandler->getTaxonomies($cpt_id);
        $categories = [];

        foreach($taxonomies as $taxonomy) {
            $categories[$taxonomy->id]['group'] = $this->taxonomyHandler->getTaxFieldGroup($taxonomy->id);
            $categories[$taxonomy->id]['items'] = $this->taxonomyHandler->getCategoryItemAttributes($taxonomy->id,$categories[$taxonomy->id]['group']);
        }

        $paginate = $service->getItems($request, $this->config);

        return XePresenter::makeAll('index', [
            'paginate' => $paginate,
            'dfConfig' => $dfConfig,
            'column_labels' => $column_labels,
            'taxonomies' => $taxonomies,
            'categories' => $categories
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

        $cpt = $this->dfService->getItem($this->config->get('cpt_id'));
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

        return XePresenter::make('show', compact('item','fieldTypes','dynamicFieldsById','cpt', 'select_category_items'));
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
        $item = CptDocument::division($this->instanceId)->find($id);

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
}
