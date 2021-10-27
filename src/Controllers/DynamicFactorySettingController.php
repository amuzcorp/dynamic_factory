<?php
namespace Overcode\XePlugin\DynamicFactory\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request as SymfonyRequest;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Overcode\XePlugin\DynamicFactory\Models\DfSlug;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Xpressengine\Category\Models\CategoryItem;
use Xpressengine\Editor\EditorHandler;
use Xpressengine\Permission\Repositories\DatabaseRepository;
use App\Http\Sections\DynamicFieldSection;
use Overcode\XePlugin\DynamicFactory\Components\Modules\Cpt\CptModule;
use Overcode\XePlugin\DynamicFactory\Exceptions\NotFoundDocumentException;
use Overcode\XePlugin\DynamicFactory\Handlers\CptPermissionHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\CptValidatorHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryConfigHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryDocumentHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryTaxonomyHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\CptUrlHandler;
use Overcode\XePlugin\DynamicFactory\Models\CategoryExtra;
use Overcode\XePlugin\DynamicFactory\Models\Cpt;
use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use Overcode\XePlugin\DynamicFactory\Models\CptTaxonomy;
use Overcode\XePlugin\DynamicFactory\Plugin;
use Overcode\XePlugin\DynamicFactory\Services\CptDocService;
use Overcode\XePlugin\DynamicFactory\Services\DynamicFactoryService;
use App\Http\Sections\EditorSection;
use XeFrontend;
use XePresenter;
use XeLang;
use XeDB;
use Route;
use Xpressengine\Category\Models\Category;
use Xpressengine\Http\Request;
use App\Http\Controllers\Controller as BaseController;
use Session;

class DynamicFactorySettingController extends BaseController
{
    protected $dfService;

    protected $dfHandler;

    protected $taxonomyHandler;

    protected $configHandler;

    /** @var ConfigHandler $dynamicFieldConfigHandler */
    protected $dynamicFieldConfigHandler;

    protected $dfDocHandler;

    protected $presenter;

    protected $cptUrlHandler;

    protected $cptValidatorHandler;

    protected $cptDocService;

    public function __construct(
        DynamicFactoryService $dynamicFactoryService,
        DynamicFactoryHandler $dynamicFactoryHandler,
        DynamicFactoryTaxonomyHandler $dynamicFactoryTaxonomyHandler,
        DynamicFactoryConfigHandler $configHandler,
        CptUrlHandler $cptUrlHandler,
        CptValidatorHandler $cptValidatorHandler,
        CptDocService $cptDocService,
        DynamicFactoryDocumentHandler $dfDocHandler
    )
    {
        $this->dfService = $dynamicFactoryService;
        $this->dfHandler = $dynamicFactoryHandler;
        $this->taxonomyHandler = $dynamicFactoryTaxonomyHandler;
        $this->dynamicFieldConfigHandler = app('xe.dynamicField');
        $this->configHandler = $configHandler;
        $this->cptUrlHandler = $cptUrlHandler;
        $this->cptValidatorHandler = $cptValidatorHandler;
        $this->cptDocService = $cptDocService;
        $this->dfDocHandler = $dfDocHandler;

        $this->presenter = app('xe.presenter');

        $this->presenter->share('cptUrlHandler', $this->cptUrlHandler);
    }

    /**
     * CPT 리스트 화면
     *
     * @return mixed
     */
    public function index()
    {
        $title = "다이나믹 팩토리";

        // set browser title
        XeFrontend::title($title);

        $cpts = $this->dfService->getItems();

        foreach ($cpts as $cpt) {
            $categories = $this->dfService->getCategories($cpt->cpt_id);
            $cpt->categories = $categories;
        }

        $cpts_from_plugin = $this->dfService->getItemsFromPlugin();

        foreach ($cpts_from_plugin as $cpt_fp) {
            $categories = $this->dfService->getCategories($cpt_fp->cpt_id);
            $cpt_fp->categories = $categories;
        }

        // output
        return $this->presenter->make('dynamic_factory::views.settings.index', [
            'title' => $title,
            'cpts' => $cpts,
            'cpts_fp' => $cpts_from_plugin
        ]);
    }

    /**
     * CPT 생성 화면
     *
     * @return mixed
     */
    public function create()
    {
        $labels = $this->dfHandler->getDefaultLabels();
        $menus = $this->dfHandler->getAdminMenus();

        return $this->presenter->make('dynamic_factory::views.settings.create', [
            'labels' => $labels,
            'menus' => $menus
        ]);
    }

    /**
     * 다이나믹 필드 관리 화면
     *
     * @param $cpt_id
     * @return mixed
     */
    public function createExtra($cpt_id)
    {
        $cpt = $this->dfService->getItem($cpt_id);

        $dynamicFieldSection = new DynamicFieldSection(
            'documents_' . $cpt_id,
            \XeDB::connection(),
            true
        );

        return $this->presenter->make(
            'dynamic_factory::views.settings.create_extra',
            compact('dynamicFieldSection', 'cpt'));
    }

    /**
     * CPT 저장 ACTION
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function storeCpt(Request $request)
    {
        // TODO 권한체크

        $this->validate($request, $this->cptValidatorHandler->getRules());  // CPT 유효성 검사

        // TODO 3rd Party Plugin 목록에서 cpt_id 중복 체크도 해야됨.

        $cpt = $this->dfService->storeCpt($request);

        return redirect()->route('dyFac.setting.index')->with('alert', ['type' => 'success', 'message' => xe_trans('xe::saved')]);
    }

    /**
     * CPT 수정 화면
     *
     * @param $cpt_id
     * @return mixed
     */
    public function edit($cpt_id)
    {
        $cpt = $this->dfService->getItem($cpt_id);
        $menus = $this->dfHandler->getAdminMenus();

        return $this->presenter->make('dynamic_factory::views.settings.edit', [
            'cpt' => $cpt,
            'menus' => $menus
        ]);
    }

    /**
     * CPT 수정 ACTION
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        // TODO 권한체크

        $this->validate($request, $this->cptValidatorHandler->getUpdateRules());  // CPT 유효성 검사
        $cpt = $this->dfService->updateCpt($request);

        return redirect()->back()->with('alert', ['type' => 'success', 'message' => xe_trans('xe::saved')]);
    }

    public function cpt_taxonomy(){
        $tax_id = request()->segment(count(request()->segments()));
        return $this->createTaxonomy($tax_id);
    }

    /**
     * 카테고리 등록/수정 화면
     *
     * @param null $tax_id
     * @return mixed
     */
    public function createTaxonomy($tax_id = null)
    {
        $cpt_cate_extra = new CategoryExtra();
        $cpt_taxonomy = [];
        //$cpt_cate_extra->is_hierarchy = true;

        $cpt_ids = [];
        $items = [];    // 확장필드 정보를 추가한 items
        $category = new Category();
        if($tax_id){
            $category = $this->taxonomyHandler->getCategory($tax_id);
            $cpt_cate_extra = CategoryExtra::where('category_id', $tax_id)->where('site_key', \XeSite::getCurrentSiteKey())->first();
            $cpt_taxonomy = CptTaxonomy::where('category_id', $tax_id)->where('site_key', \XeSite::getCurrentSiteKey())->get();

            $items = $this->taxonomyHandler->getCategoryDynamicField($tax_id);
        }

        foreach ($cpt_taxonomy as $cptx) {
            $cpt_ids[] = $cptx->cpt_id;
        }

        // 1. 유형 목록 불러오기 TODO 2. 다른 플러그인에서 생성된 유형 목록 불러오기
        $cpts = $this->dfService->getItems();
        $cpts_fp = $this->dfService->getItemsFromPlugin();

        //TODO tax_id 가 있으면 로드 하여 프레젠터에 보낸다.

        XeFrontend::js('/assets/core/common/js/xe.tree.js')->appendTo('body')->load();
        XeFrontend::js('plugins/dynamic_factory/assets/category/Category.js')->appendTo('body')->load();

        XeFrontend::translation([
            'xe::required',
            'xe::addItem',
            'xe::create',
            'xe::createChild',
            'xe::edit',
            'xe::unknown',
            'xe::word',
            'xe::description',
            'xe::save',
            'xe::delete',
            'xe::close',
            'xe::subCategoryDestroy',
            'xe::confirmDelete',
        ]);

        return $this->presenter->make('dynamic_factory::views.settings.create_taxonomy',
        [
            'category' => $category,
            'cpt_cate_extra' => $cpt_cate_extra,
            'cpt_ids' => $cpt_ids,
            'cpts' => $cpts,
            'cpts_fp' => $cpts_fp,
            'category_items' => $items
        ]);
    }

    /**
     * 카테고리 확장필드 화면
     *
     * @param $category_slug
     * @return mixed
     */
    public function taxonomyExtra($category_slug)
    {
        $cateExtra = $this->taxonomyHandler->getCategoryExtraBySlug($category_slug);
        $category = $this->taxonomyHandler->getCategory($cateExtra->category_id);
        $dynamicFieldSection = new DynamicFieldSection(
            'tax_'. $category_slug,
            \XeDB::connection(),
            true
        );

        return $this->presenter->make('dynamic_factory::views.settings.taxonomy_extra',
            compact('dynamicFieldSection', 'cateExtra', 'category'));
    }

    /**
     * 카테고리 저장 ACTION
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function storeTaxonomy(Request $request)
    {
        $category_id = $this->dfService->storeCptTaxonomy($request);

        return redirect()->back()->with('alert', ['type' => 'success', 'message' => xe_trans('xe::saved')]);
    }

    public function editEditor($cpt_id)
    {
        $cpt = $this->dfService->getItem($cpt_id);

        $editorSection = new EditorSection($cpt_id);

        return $this->presenter->make(
            'dynamic_factory::views.settings.edit_editor', [
                'cpt' => $cpt,
                'editorSection' => $editorSection
            ]
        );
    }

    public function editColumns($cpt_id)
    {
        $cpt = $this->dfService->getItem($cpt_id);

        $config = $this->configHandler->getConfig($cpt_id);

        $sortListColumns = $this->configHandler->getSortListColumns($config);
        $sortFormColumns = $this->configHandler->getSortFormColumns($config);
        $columnLabels = $this->configHandler->getColumnLabels($config);

        return $this->presenter->make(
            'dynamic_factory::views.settings.edit_columns', [
                'cpt' => $cpt,
                'sortListColumns' => $sortListColumns,
                'sortFormColumns' => $sortFormColumns,
                'columnLabels' => $columnLabels,
                'config' => $config
            ]
        );
    }

    public function updateColumns($cpt_id, Request $request)
    {
        $config = $this->configHandler->getConfig($cpt_id);
        $inputs = $request->except('_token');

        foreach ($inputs as $key => $val) {
            $config->set($key, $val);
        }

        $this->configHandler->modifyConfig($config);

        return redirect()->back()->with('alert', ['type' => 'success', 'message' => xe_trans('xe::saved')]);
    }

    public function editOrders($cpt_id)
    {
        $cpt = $this->dfService->getItem($cpt_id);

        $config = $this->configHandler->getConfig($cpt_id);

        $orderList = $this->configHandler->getOrderListColumns($config);

        return $this->presenter->make('dynamic_factory::views.settings.edit_orders', [
            'cpt' => $cpt,
            'sortListColumns' => $orderList['sortListColumns'],
            'columnLabels' => $orderList['columnLabels'],
            'config' => $config
        ]);
    }

    public function updateOrders($cpt_id, Request $request)
    {
        $config = $this->configHandler->getConfig($cpt_id);
        $inputs = $request->except('_token');

        // 모두 삭제 했을 경우에 빈 배열을 저장한다
        if(!isset($inputs['orders'])) {
            $inputs['orders'] = [];
        }

        foreach ($inputs as $key => $val) {
            $config->set($key, $val);
        }

        $this->configHandler->modifyConfig($config);

        return redirect()->back()->with('alert', ['type' => 'success', 'message' => xe_trans('xe::saved')]);
    }

    public function editPermission($cpt_id)
    {
        $cpt = $this->dfService->getItem($cpt_id);

        $config = $this->configHandler->getConfig($cpt_id);
        $cptPermission = app('overcode.df.permission');
        $perms = $cptPermission->getPerms($cpt_id);

        return $this->presenter->make('dynamic_factory::views.settings.permission', [
            'cpt' => $cpt,
            'config' => $config,
            'perms' => $perms
        ]);
    }

    public function updatePermission(Request $request, CptPermissionHandler $cptPermission, $cpt_id) {
        $cptPermission->set($request, $cpt_id);

        return redirect()->to(route('dyFac.setting.edit_permission', ['cpt_id' => $cpt_id]));
    }

    public function cptDocument($type = 'list', Request $request)
    {
        $current_route_name = Route::currentRouteName();
        $route_names = explode('.', $current_route_name);
        $cpt_id = $route_names[count($route_names) - 1];

        $request->current_route_name = $current_route_name;

        $cpt = $this->dfService->getItem($cpt_id);

        $permission_check = app('overcode.df.permission')->get($cpt_id,\XeSite::getCurrentSiteKey());

        if(!$permission_check) {
            if(!\DB::table('permissions')->where('site_key', \XeSite::getCurrentSiteKey())->where('name',CptModule::getId().'.'.$cpt_id)->first()) {
                \DB::table('permissions')->insert([
                    'site_key' => \XeSite::getCurrentSiteKey(), 'name' => CptModule::getId() . '.' . $cpt_id, 'grants' => '[]',
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
            }
        }

        if($type == 'create') return $this->documentCreate($cpt, $request);
        else if($type == 'edit') return $this->documentEdit($cpt, $request);

        return $this->documentList($cpt, $request);
    }

    /**
     * CPT 관리자 문서 리스트
     *
     * @param Cpt $cpt
     * @param Request $request
     * @return mixed
     */
    public function documentList(Cpt $cpt, Request $request)
    {
        $orderNames = [
            'assent_count' => '추천순',
            'recently_created' => '최신순',
            'recently_published' => '최근 발행순',
            'recently_updated' => '최근 수정순',
        ];

        $stateTypeCounts = [
            'all' => CptDocument::cpt($cpt->cpt_id)->where('site_key', \XeSite::getCurrentSiteKey())->count(),
            'published' => CptDocument::cpt($cpt->cpt_id)->where('site_key', \XeSite::getCurrentSiteKey())->published()->public()->count(),
            'publishReserved' => CptDocument::cpt($cpt->cpt_id)->where('site_key', \XeSite::getCurrentSiteKey())->publishReserved()->public()->count(),
            'tempBlog' => CptDocument::cpt($cpt->cpt_id)->where('site_key', \XeSite::getCurrentSiteKey())->temp()->count(),
            'private' => CptDocument::cpt($cpt->cpt_id)->where('site_key', \XeSite::getCurrentSiteKey())->private()->count()
        ];

        $config = $this->configHandler->getConfig($cpt->cpt_id);
        $column_labels = $this->configHandler->getColumnLabels($config);

        $taxonomies = app('overcode.df.taxonomyHandler')->getTaxonomies($cpt->cpt_id);

        $cptDocs = $this->getCptDocuments($request, $cpt, $config);

        $searchTargetWord = $request->get('search_target');
        if ($request->get('search_target') == 'pure_content') {
            $searchTargetWord = 'content';
        } elseif ($request->get('search_target') == 'title_pure_content') {
            $searchTargetWord = 'titleAndContent';
        }

        return $this->presenter->make($cpt->blades['list'],[
            'cpt' => $cpt,
            'current_route_name' => $request->current_route_name,
            'cptDocs' => $cptDocs,
            'config' => $config,
            'column_labels' => $column_labels,
            'searchTargetWord' => $searchTargetWord,
            'stateTypeCounts' => $stateTypeCounts,
            'orderNames' => $orderNames,
            'taxonomies' => $taxonomies
        ]);
    }

    public function documentCreate(Cpt $cpt, Request $request)
    {
        $taxonomies = $this->taxonomyHandler->getTaxonomies($cpt->cpt_id);

        $dynamicFields = $this->dynamicFieldConfigHandler->gets('documents_' . $cpt->cpt_id);

        $dynamicFieldsById = [];
        foreach ($dynamicFields as $dyField) {
            $dynamicFieldsById[$dyField->getConfig()->get('id')] = $dyField->getConfig();
        }
        $cptConfig = $this->dfService->getCptConfig($cpt->cpt_id);

        XeFrontend::js('assets/vendor/jqueryui/jquery-ui.min.js')->load();

        return $this->presenter->make($cpt->blades['create'],[
            'cpt' => $cpt,
            'taxonomies' => $taxonomies,
            'dynamicFields' => $dynamicFields,
            'cptConfig' => $cptConfig,
            'dynamicFieldsById' => $dynamicFieldsById
        ]);
    }

    public function documentEdit(Cpt $cpt, Request $request)
    {
        $taxonomies = $this->taxonomyHandler->getTaxonomies($cpt->cpt_id);

        $dynamicFields = $this->dynamicFieldConfigHandler->gets('documents_' . $cpt->cpt_id);

        $dynamicFieldsById = [];
        foreach ($dynamicFields as $dyField) {
            $dynamicFieldsById[$dyField->getConfig()->get('id')] = $dyField->getConfig();
        }
        $cptConfig = $this->dfService->getCptConfig($cpt->cpt_id);

        $item = CptDocument::division($cpt->cpt_id)->find($request->doc_id);

        if ($item === null) {
            throw new NotFoundDocumentException;
        }

        $thumb = $this->dfDocHandler->getThumb($item->id);

        $category_items = $this->dfService->getSelectCategoryItems($cpt->cpt_id, $item->id);

        return $this->presenter->make($cpt->blades['edit'],[
            'cpt' => $cpt,
            'taxonomies' => $taxonomies,
            'dynamicFields' => $dynamicFields,
            'cptConfig' => $cptConfig,
            'dynamicFieldsById' => $dynamicFieldsById,
            'item' => $item,
            'thumb' => $thumb,
            'category_items' => $category_items
        ]);
    }

    /*public function documentDelete(Cpt $cpt, Request $request)
    {
        // TODO 퍼미션 체크

        $doc_id = $request->get('doc_id');

        $item = CptDocument::division($cpt->cpt_id)->find($doc_id);

        app('xe.document')->remove($item);

        return $this->presenter->makeApi([]);
    }*/

    public function storeCptDocument(Request $request)
    {
        //Todo 퍼미션 체크
        $document = $this->dfService->storeCptDocument($request);

        return redirect()->route('dyFac.setting.'.$request->cpt_id, ['type' => 'list']);
    }

    public function updateCptDocument(Request $request)
    {
        $this->dfService->updateCptDocument($request);

//        return redirect()->route('dyFac.setting.'.$request->cpt_id, ['type' => 'edit', 'doc_id' => $request->doc_id]);
        return redirect()->back()->with('alert', ['type' => 'success', 'message' => xe_trans('xe::saved')]);
    }

    //DocumentWriter 위젯 전용
    public function storeRendingCptDocument(Request $request) {
        //Todo 퍼미션 체크
        $document = $this->dfService->storeCptDocument($request);
        $after_work = $request->after_work;

        return redirect()->route('dyFac.document.rending_store_result', ['status' => 'success', 'result' => $document, 'after_work' => $after_work]);
    }

    public function getCptDocuments($request, $cpt, $config)
    {
        $perPage = $request->get('perPage', 20);

        $query = $this->dfService->getItemsWhereQuery(array_merge($request->all(), [
            'force' => true,
            'cpt_id' => $cpt->cpt_id
        ]));

        // 검색 조건 추가
        $query = $this->makeWhere($query, $request);

        // 정렬
        $orderType = $request->get('order_type', '');

        if ($orderType == '') {
            // order_type 이 없을때만 dyFac Config 의 정렬을 우선 적용한다.
            $orders = $config->get('orders', []);
            foreach ($orders as $order) {
                $arr_order = explode('|@|',$order);
                $query->orderBy($arr_order[0], $arr_order[1]);
            }
            //TODO 오류 있어서 임시 제거
//            $query->orderBy('head', 'desc');
        } elseif ($orderType == 'assent_count') {
            $query->orderBy('assent_count', 'desc')->orderBy('head', 'desc');
        } elseif ($orderType == 'recently_created') {
            $query->orderBy(CptDocument::CREATED_AT, 'desc')->orderBy('head', 'desc');
        } elseif ($orderType == 'recently_published') {
            $query->orderBy('published_at', 'desc')->orderBy('head', 'desc');
        } elseif ($orderType == 'recently_updated') {
            $query->orderBy(CptDocument::UPDATED_AT, 'desc')->orderBy('head', 'desc');
        }

        $paginate = $query->paginate($perPage, ['*'], 'page')->appends($request->except('page'));

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

    public function categoryList()
    {
        $categories = $this->dfService->getCategoryExtras();

        return $this->presenter->make('dynamic_factory::views.settings.category', [
            'categories' => $categories
        ]);
    }

    public function categoryDelete(Request $request)
    {
        $return_value = '';

        if(isset($request->id)){
            $return_value = $this->taxonomyHandler->deleteCategory($request->id);
        }

        return $this->presenter->makeApi([ 'return_value' => $return_value]);
    }

    /**
     * cpt_id 로 cpt 와 config, dynamic_field, taxonomy 에서 cpt_id 제거, relate_cpt 에서 cpt_id 제거
     * 그리고 해당 cpt 에 종속된 documents 를 삭제한다.
     *
     * @param $cpt_id
     * @param Request $request
     * @return mixed
     */
    public function destroy($cpt_id, Request $request)
    {
        // TODO 최고관리자 또는 자신이 작성한 CPT 만 삭제 가능하게 권한 체크
        $this->dfService->destroyCpt($cpt_id);

 // TODO CPT 권한 삭제
        \DB::table('permissions')->where('name', CptModule::getId().'.'.$cpt_id)->delete();

        return $this->presenter->makeApi([]);
    }

    /**
     * 휴지통 관리
     *
     * @param Request $request
     */
    public function trash(Request $request, $cpt_id = null)
    {
        $listColumns = $this->configHandler->getDefaultListColumns();

        $column_labels = $this->configHandler->getDefaultColumnLabels();
        $cptDocs = $this->cptDocService->getItemsAllCpt($request, 'trash', $cpt_id);

        return $this->presenter->make('dynamic_factory::views.documents.trash',compact(
            'cpt_id',
            'listColumns',
            'column_labels',
            'cptDocs'
        ));
    }

    public function trashAlias(Request $request){
        $cpt_id = request()->segment(count(request()->segments()));
        return $this->trash($request,$cpt_id);
    }

    /**
     * 휴지통으로
     *
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    public function trashDocuments(Request $request)
    {
        $documentIds = $request->get('id');
        $documentIds = is_array($documentIds) ? $documentIds : [$documentIds];

        XeDB::beginTransaction();
        try {
            $this->cptDocService->trash($documentIds);
        }catch (\Exception $e) {
            XeDB::rollback();

            throw $e;
        }
        XeDB::commit();

        Session::flash('alert', ['type' => 'success', 'message' => xe_trans('xe::processed')]);

        return $this->presenter->makeApi([]);
    }

    /**
     * 휴지통 문서를 복원
     *
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    public function restoreDocuments(Request $request)
    {
        $documentIds = $request->get('id');
        $documentIds = is_array($documentIds) ? $documentIds : [$documentIds];

        XeDB::beginTransaction();
        try {
            $this->cptDocService->restore($documentIds);
        }catch (\Exception $e) {
            XeDB::rollback();

            throw $e;
        }
        XeDB::commit();

        Session::flash('alert', ['type' => 'success', 'message' => xe_trans('xe::processed')]);

        return $this->presenter->makeApi([]);
    }

    /**
     * 문서를 완전 삭제
     *
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    public function removeDocuments(Request $request)
    {
        $documentIds = $request->get('id');
        $documentIds = is_array($documentIds) ? $documentIds : [$documentIds];

        XeDB::beginTransaction();
        try {
            $this->cptDocService->remove($documentIds);
        }catch (\Exception $e) {
            XeDB::rollback();

            throw $e;
        }
        XeDB::commit();

        Session::flash('alert', ['type' => 'success', 'message' => xe_trans('xe::processed')]);

        return $this->presenter->makeApi([]);
    }

    protected function makeWhere($query, $request)
    {
        //기간 검색
        if ($startDate = $request->get('start_date')) {
            $query = $query->where('created_at', '>=', $startDate . ' 00:00:00');
        }
        if ($endDate = $request->get('end_date')) {
            $query = $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }

        $stateType = $request->get('stateType', 'all');
        switch ($stateType) {
            case 'all':
//                $query = $query->withTrashed();
                break;

            case 'published':
                $query = $query->public()->published();
                break;

            case 'publishReserved':
                $query = $query->public()->publishReserved();
                break;

            case 'tempBlog':
                $query = $query->temp();
                break;

            case 'private':
                $query = $query->private();
                break;

            case 'trash':
                $query = $query->onlyTrashed();
                break;
        }

        //검색어 검색
        if ($request->get('search_target') == 'title') {
            $query = $query->where(
                'title',
                'like',
                sprintf('%%%s%%', implode('%', explode(' ', $request->get('search_keyword'))))
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

        $category_items = [];

        $data = $request->except('_token');
        foreach($data as $id => $value){
            if(strpos($id, 'taxo_', 0) === 0) {
                if($value) {
                    $category_items[] = $value;
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
        } else {
            foreach($category_items as $item_id) {
                $categoryItem = CategoryItem::find($data);
                if ($categoryItem !== null) {
                    $query = $query->where('df_taxonomy.item_ids', 'like', '%"' . $item_id . '"%');
                }
            }
        }

        $query->GroupBy('documents.id');

        //필터 검색
        /*if ($state = $request->get('search_state')) {
            list($searchField, $searchValue) = explode('|', $state);

            $query->where($searchField, $searchValue);
        }*/

        //게시판 검색
        /*if ($targetBoard = $request->get('search_board')) {
            $query->where('instance_id', $targetBoard);
        }*/

        return $query;
    }

    public function downloadCSV($cpt_id) {
        $docData = CptDocument::division($cpt_id)->where('instance_id', $cpt_id)->where('type', $cpt_id)->get();

        if(!$docData) return redirect()->back()->with('alert', ['type' => 'danger', 'message' => '다운로드할 문서 정보가 존재 하지 않습니다']);
        $cpt = app('overcode.df.service')->getItem($cpt_id);

        //application/pdf
        $headers = array(
            "Content-type" => "application/vnd.ms-excel; charset=UTF-8;",
            "Content-Disposition" => 'attachment; filename='.$cpt->menu_name.'_'.date('Y_m_d H_i_s').'.csv',
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
        );
        $headerText = 'no,cpt_status';
        $relateCptId = '';
        foreach($docData[0]->getAttributes() as $key => $val) {

            //불필요한 필드 삭제
            if($key == 'user_type'||$key == 'pure_content'||$key == 'head'||
                $key == 'reply'||$key == 'ipaddress'||
                $key == 'approved'||$key == 'assent_count'||
                $key == 'comment_count'||$key == 'read_count'||
                $key == 'certify_key'||$key == 'dissent_count'||
                $key == 'approved'||$key == 'published'||$key == 'status'||
                $key == 'display'||$key == 'format'||$key == 'locale'||$key == 'parent_id' || $key == 'deleted_at') {
                continue;
            }

            if(strpos($key,"_srf_chg")) {
                $relateCptId = str_replace('_srf_chg', '', $key);
            }
            if($key === $relateCptId.'_s_id' || $key === $relateCptId.'_s_group' || $key === $relateCptId.'_s_type' || $key === $relateCptId.'_t_id' || $key === $relateCptId.'_t_group'
                || $key === $relateCptId.'_t_type' || $key === $relateCptId.'_ordering') {
                continue;
            }

            if($key === 'id') {
                $headerText = $headerText.',doc_id';
            } else {
                $headerText = $headerText.','.$key;
            }
        }

        $test = explode(',', $headerText);

        $excels = [];
        foreach($docData as $inx => $data) {
            $doc_items = $data->getAttributes();
            $relateCptId = '';
            foreach($test as $key => $val) {

                if($val === 'no') {
                    $excels[$inx][$val] = $inx + 1;
                    continue;
                }

                /* Relate Cpt 데이터 기록 json encode */
                if(strpos($val,"_srf_chg")) {
                    $relateCptId = str_replace('_srf_chg', '', $val);
                    $excels[$inx][$val] = 1;
                } else if($val === 'hidden_'.$relateCptId) {
                    $item_realteCptData = [];
                    foreach($data->hasDocument($relateCptId) as $relate_data) {
                        $item_realteCptData[] = $relate_data->id;
                    }
                    $excels[$inx][$val] = json_enc($item_realteCptData);
                }
                /* Relate Cpt 데이터 기록 json encode */

                //다운로드 시점 문서 공개속성 기록
                else if($val === 'cpt_status') {
                    if($data->isPublic()) {
                        $excels[$inx][$val] = 'public';
                    } else if($data->isTemp()) {
                        $excels[$inx][$val] = 'temp';
                    } else {
                        $excels[$inx][$val] = 'private';
                    }
                }
                //일반 데이터 + 일반 Field 분류 데이터
                else {
                    // Key - id > Key - doc_id로 저장
                    if($val === 'doc_id') {
                        $excels[$inx][$val] = $doc_items['id'];
                        continue;
                    }
                    //Content에 포함된 /r/n으로 인한 오작동 방지용 json 인코딩
                    if($val === 'content') {
                        $doc_items[$val] = str_replace("\r\n", '<br>', $doc_items[$val]);
                    }
                    $excels[$inx][$val] = $doc_items[$val];
                }

            }
        }

        $callback = function () use ($headerText, $excels) {
            $file = fopen('php://output', 'w');

            fputs($file, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

            fwrite($file, $headerText."\n");
            foreach ($excels as $data) {
                foreach($data as $key => $val) {
                    fwrite($file, $val . ",");
                }
                fwrite($file, "\n");
            }
            fclose($file);
        };

        return \Illuminate\Support\Facades\Response::stream($callback, 200, $headers);
    }

    public function uploadCSV(Request $request) {
        //업로드 진행한 CPT 문서 Instance ID
        $cpt_id = $request->get('cpt_id');

        $editor = \XeEditor::get($cpt_id);
        $config = $editor->getConfig();

        //업로드한 파일
        $uploadedFile = $request->file('csv_file');

        /* !!CSV 파일 읽기전 파일 서버에 저장!! */
        $extensions = array_map(function ($v) {
            return trim($v);
        }, explode(',', $config->get('extensions', '')));
        if (array_search('*', $extensions) === false
            && !in_array(strtolower($uploadedFile->getClientOriginalExtension()), $extensions)) {
            throw new HttpException(
                Response::HTTP_NOT_ACCEPTABLE,
                xe_trans('xe::msgAvailableUploadingFiles', [
                    'extensions' => $config->get('extensions'),
                    'uploadFileName' => $uploadedFile->getClientOriginalName()
                ])
            );
        }
        /* !!CSV 파일 읽기전 파일 서버에 저장!! */

        /* !!CSV 파일 읽기!! */
        $file = \XeStorage::upload($uploadedFile, EditorHandler::FILE_UPLOAD_PATH);
        $filename = 'storage/app/'.$file->path.'/'.$file->filename;
        $forms = [];
        if (($handle = fopen($filename, "r")) !== FALSE) {
            while (($line = fgetcsv($handle)) !== FALSE) {
                $forms[] = $line;
            }
            fclose($handle);
        }
        /* !!CSV 파일 읽기!! */

        $params = [];
        $index = 0;
        foreach($forms as $key => $val) {
            //CSV A1 라인 continue
            if($key === 0) continue;

            $relateCptId = '';
            for($i = 0; $i < count($forms[0]); $i++) {
                //다운로드할때 <br>로 바뀐 \r\n 원상복구
                if($forms[0][$i] === 'content') {
                    $params[$index][$forms[0][$i]] = str_replace('<br>', "\r\n", $val[$i]);
                }
                //RelateCPT [field ID] + _srf_chg 필드가 존재하면 field ID 기록
                else if(strpos($forms[0][$i],"_srf_chg")) {
                    $relateCptId = str_replace('_srf_chg', '', $forms[0][$i]);
                    $params[$index][$forms[0][$i]] = $val[$i];
                }
                //CSV에 기록된 RelateCPT ID 정보 json decode
                else if($forms[0][$i] === 'hidden_'.$relateCptId) {
                    $params[$index][$forms[0][$i]] = json_dec($val[$i]);
                }
                //calendar 기록 양식에 맞게 컨버트 [ 0 => 일자 , 1 => 시간 (시:분) ]
                else if(strpos($forms[0][$i],"_date_start") || strpos($forms[0][$i],"_date_end")) {
                    $params[$index][$forms[0][$i]] = [
                        date('Y-m-d', strtotime($val[$i])),
                        date('H:i', strtotime($val[$i]))
                    ];
                }
                //특수 필드가 아닌 일반 필드는 값 그대로 저장
                else {
                    $params[$index][$forms[0][$i]] = $val[$i];
                }

            }
            $index++;
        }

        XeDB::beginTransaction();
        try {
            foreach ($params as $val) {

                //문서에 기록된 CPT ID
                $target_cpt_id = $val['instance_id'];

                //CSV 업로드 진행중인 CPT 와 기록된 CPT ID가 다를경우 continue
                if($cpt_id !== $target_cpt_id) continue;

                //기록된 doc_id 로 작성된 CPT 문서가 있는지 체크
                $cptDocument = CptDocument::division($target_cpt_id)->where('instance_id',$target_cpt_id)->where('id', $val['doc_id'])->first();

                unset($val['type']);
                unset($val['email']);
                unset($val['updated_at']);
                unset($val['instance_id']);
                unset($val['deleted_at']);
                unset($val['certify_key']);
                unset($val['read_count']);
                unset($val['comment_count']);
                unset($val['assent_count']);
                unset($val['dissent_count']);
                unset($val['approved']);
                unset($val['published_at']);
                unset($val['status']);
                unset($val['display']);
                unset($val['format']);
                unset($val['locale']);
                unset($val['head']);
                unset($val['reply']);
                unset($val['ipaddress']);
                unset($val['site_key']);
                unset($val['created_at']);

                //Slug 추가
                if($cptDocument) {
                    $val['slug'] = $cptDocument->getSlug();
                } else {
                    unset($val['parent_id']);
                    unset($val['doc_id']);
                    $val['published_at'] = '____-__-__ __:__:__';
                    $val['slug'] = DfSlug::make($val['title'], $target_cpt_id);
                }

                //토큰 추가
                $val['_token'] = csrf_token();

                $inputs = new SymfonyRequest($val);
                $inputs = Request::createFromBase($inputs);
                $inputs->request->add(
                    [
                        'cpt_id' => $target_cpt_id,
                    ]
                );

                //CPT 문서가 존재하고 doc ID가 있으면 update
                if($cptDocument) app('overcode.df.service')->updateCptDocument($inputs);
                else app('overcode.df.service')->storeCptDocument($inputs);
            }
            //exception
        } catch (\Exception $e) {
            XeDB::rollback();
            return  redirect()->back()->with('alert', ['type' => 'danger', 'message' => $e->getMessage()]);
        }
        XeDB::commit();

        return redirect()->back()->with('alert', ['type' => 'success', 'message' => '작업을 완료 했습니다.']);
    }
}
