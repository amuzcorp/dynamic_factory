<?php
namespace Overcode\XePlugin\DynamicFactory\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request as SymfonyRequest;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Overcode\XePlugin\DynamicFactory\Models\DfSlug;
use Overcode\XePlugin\DynamicFactory\Models\User as XeUser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
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
            //관리자 화면 로딩속도 개선을위해
            unset($cpt->content, $cpt->pure_content);
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

        $all = $this->makeWhere(CptDocument::cpt($cpt->cpt_id), $request)->where('site_key', \XeSite::getCurrentSiteKey())->count();
        $published = $this->makeWhere(CptDocument::cpt($cpt->cpt_id), $request)->where('site_key', \XeSite::getCurrentSiteKey())->published()->public()->count();
        $publishReserved = $this->makeWhere(CptDocument::cpt($cpt->cpt_id), $request)->where('site_key', \XeSite::getCurrentSiteKey())->publishReserved()->public()->count();
        $tempBlog = $this->makeWhere(CptDocument::cpt($cpt->cpt_id), $request)->where('site_key', \XeSite::getCurrentSiteKey())->temp()->count();
        $private = $this->makeWhere(CptDocument::cpt($cpt->cpt_id), $request)->where('site_key', \XeSite::getCurrentSiteKey())->private()->count();

        $stateTypeCounts = [
            'all' => $all,
            'published' => $published,
            'publishReserved' => $publishReserved,
            'tempBlog' => $tempBlog,
            'private' => $private
        ];

        //$stateTypeCounts = [
        //            'all' => CptDocument::cpt($cpt->cpt_id)->where('site_key', \XeSite::getCurrentSiteKey())->count(),
        //            'published' => CptDocument::cpt($cpt->cpt_id)->where('site_key', \XeSite::getCurrentSiteKey())->published()->public()->count(),
        //            'publishReserved' => CptDocument::cpt($cpt->cpt_id)->where('site_key', \XeSite::getCurrentSiteKey())->publishReserved()->public()->count(),
        //            'tempBlog' => CptDocument::cpt($cpt->cpt_id)->where('site_key', \XeSite::getCurrentSiteKey())->temp()->count(),
        //            'private' => CptDocument::cpt($cpt->cpt_id)->where('site_key', \XeSite::getCurrentSiteKey())->private()->count()
        //        ];

        $config = $this->configHandler->getConfig($cpt->cpt_id);
        $column_labels = $this->configHandler->getColumnLabels($config);

        $taxonomies = app('overcode.df.taxonomyHandler')->getTaxonomies($cpt->cpt_id);

        $cptDocs = $this->getCptDocuments($request, $cpt, $config, true);

        $searchTargetWord = $request->get('search_target');
        if ($request->get('search_target') == 'pure_content') {
            $searchTargetWord = 'content';
        } elseif ($request->get('search_target') == 'title_pure_content') {
            $searchTargetWord = 'titleAndContent';
        }
        $listColumns = $config['listColumns'];
        $taxonomySet = [];
        foreach($taxonomies as $taxonomy) {
            $taxonomySet[] = 'taxo_'.$taxonomy->id;
        }
        $config['listColumns'] = array_merge($taxonomySet, $listColumns);

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

    public function getCptDocuments($request, $cpt, $config, $withOutContent = false)
    {
        $perPage = (int) $request->get('perPage', '10') ?? 10;

        $query = $this->dfService->getItemsWhereQuery(array_merge($request->all(), [
            'force' => true,
            'cpt_id' => $cpt->cpt_id
        ]));

        // 정렬
        $orderType = $request->get('order_type', '');

        // 검색 조건 추가
        $query = $this->makeWhere($query, $request);

        //TODO orderBy 오류 있어서 임시 제거
        //TODO 부산경총 오류
        if ($orderType == '') {
            // order_type 이 없을때만 dyFac Config 의 정렬을 우선 적용한다.
            $orders = $config->get('orders', []);
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
        }

//        if($withOutContent){
//            $query->select('documents.id','documents.title','documents.instance_id','documents.type','documents.user_id','documents.user_id','documents.read_count','documents.comment_count','documents.locale','documents.approved','documents.published','documents.status','documents.locale','documents.created_at','documents.updated_at','documents.published_at','documents.deleted_at','documents.ipaddress','documents.site_key');
//        }

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

    /**
     * 문서를 전체 삭제
     *
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    public function removeAllDocuments(Request $request)
    {
        $cpt_id = $request->get('cpt_id');
        if($cpt_id == null) {
            $cpts = app('overcode.df.service')->getItemsAll();
            foreach ($cpts as $cpt) {
                $cpt_ids[] = $cpt->cpt_id;
            }
        }else{
            $cpt_ids = [$cpt_id];
        }

        $query = CptDocument::whereIn('instance_id', $cpt_ids);
        //삭제된 문서만 조회
        $query = $query->onlyTrashed();
        $documentIds = $query->pluck('id');

        if(count($documentIds) < 1) {
            Session::flash('alert', ['type' => 'danger', 'message' => '삭제 가능한 문서가 없습니다']);

            return $this->presenter->makeApi([]);
        }

        if(count($documentIds) > 400) {
            $documentLists = array_chunk($documentIds->toArray(), 400);
        } else {
            $documentLists[] = $documentIds;
        }

        XeDB::beginTransaction();
        try {
            foreach($documentLists as $lists) {
                $this->cptDocService->remove($lists);
            }
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
        $dateSearch = $request->get('date_search', 'N') ?? 'N';
        if($dateSearch === 'Y') {
            if ($startDate = $request->get('start_date')) {
                $query = $query->where('created_at', '>=', $startDate . ' 00:00:00');
            }
            if ($endDate = $request->get('end_date')) {
                $query = $query->where('created_at', '<=', $endDate . ' 23:59:59');
            }
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
            $isOrWhere = array_get($data, 'taxOr','N') == 'Y';
            $query->whereHas('taxonomy', function ($qGroupWhere) use ($category_items,$isOrWhere){
                $qGroupWhere->where(function($q) use ($category_items,$isOrWhere){
                    foreach($category_items as $categoryId => $cate_items){
                        foreach($cate_items as $id) {
                            if($isOrWhere){
                                $q->orWhere('item_ids','like', '%"' .(int) $id. '"%');
                            }else{
                                $q->where('item_ids','like', '%"' .(int) $id. '"%');
                            }
                        }
                    }
                });
            });
        }

        if($request->get('userGroup') && $request->get('userGroup') !== '') {
            $userGroup_id = $request->get('userGroup');
            $from = $query->getQuery()->from;
            $table_name = 'user_group_user';
            $query->leftJoin($table_name, function($leftJoin) use($from, $table_name, $userGroup_id) {
                $leftJoin->on(sprintf('%s.%s', $from, 'user_id'),'=',sprintf('%s.%s', $table_name, 'user_id'));
            });

            $query->where(function($q) use ($table_name,$userGroup_id){
                $q->where($table_name . '.group_id', $userGroup_id);
            });
        }
        if($request->get('userCountry') && $request->get('userCountry') !== '') {
            $userCountry = $request->get('userCountry');
            $from = $query->getQuery()->from;
            $table_name = 'user';
            $query->leftJoin($table_name, function($leftJoin) use($from, $table_name) {
                $leftJoin->on(sprintf('%s.%s', $from, 'user_id'),'=',sprintf('%s.%s', $table_name, 'id'));
            });

            $query->where(function($q) use ($table_name,$userCountry){
                $q->where($table_name . '.country', $userCountry);
            });
        }
//        if($request->get('test', 0)  == 3) {
//            dd($query->first());
//        }
//        $query->GroupBy('documents.id')->select('documents.*');

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

    public function downloadCSV($cpt_id, Request $request) {
        $count = CptDocument::division($cpt_id)->where('instance_id', $cpt_id)->where('type', $cpt_id)->count();
        if($count === 0) return redirect()->back()->with('alert', ['type' => 'danger', 'message' => '문서를 하나 이상 작성 후 다운로드 해주세요.']);

        $query = $this->dfService->getItemsWhereQuery(array_merge($request->all(), [
            'force' => true,
            'cpt_id' => $cpt_id
        ]));

        $config = $this->configHandler->getConfig($cpt_id);

        // 검색 조건 추가
        $query = $this->makeWhere($query, $request);
        // 정렬
        $orderType = $request->get('order_type', '');

        //TODO orderBy 오류 있어서 임시 제거
        //TODO 부산경총 오류
        if ($orderType == ''&& $request->get('test', 0) != 88 ) {
            // order_type 이 없을때만 dyFac Config 의 정렬을 우선 적용한다.
            $orders = $config->get('orders', []);
            foreach ($orders as $order) {
                $arr_order = explode('|@|',$order);
                $sort = 'asc';
                if($arr_order[1] === 'asc') $sort = 'desc';
                $query->orderBy($arr_order[0], $sort);
            }
            $query->orderBy('head', 'asc');
        } elseif ($orderType == 'assent_count') {
            $query->orderBy('assent_count', 'asc')->orderBy('head', 'asc');
        } elseif ($orderType == 'recently_created') {
            $query->orderBy(CptDocument::CREATED_AT, 'asc')->orderBy('head', 'asc');
        } elseif ($orderType == 'recently_published') {
            $query->orderBy('published_at', 'asc')->orderBy('head', 'asc');
        } elseif ($orderType == 'recently_updated') {
            $query->orderBy(CptDocument::UPDATED_AT, 'asc')->orderBy('head', 'asc');
        }

//        if($request->get('test1', 0) === 0) {
//            $docData = $query->get();
//        } else {
//            $docData = $query->paginate($request->get('test2', 100), ['*'], 'page', $request->get('test1', 0));
//        }
        $excelPage = (int) $request->get('ep') ?: 1;
        $limit = $request->get('limitCount') ? +$request->get('limitCount') : 100;

        if($limit <= 0) $limit = 10;
        else if( $limit > 1000 ) $limit = 1000;

        $docData = $query->paginate($limit, ['*'], 'page', $excelPage);

        if(count($docData) === 0) return redirect()->back()->with('alert', ['type' => 'danger', 'message' => '조회된 문서가 0개 입니다']);
        $cpt = app('overcode.df.service')->getItem($cpt_id);
        $config = $this->configHandler->getConfig($cpt_id);
        $column_labels = $this->configHandler->getColumnLabels($config);


        $taxonomyHandler = app('overcode.df.taxonomyHandler');
        $taxonomies = $taxonomyHandler->getTaxonomies($cpt_id);

        $formOrder = [
            'no',
            'doc_id',
            'name',
            'email',
            'cpt_status',
        ];
        $excels[] = [
            'no' => '넘버',
            'doc_id' => '문서 ID',
            'name' => '작성자 이름',
            'email' => '작성자 이메일',
            'cpt_status' => '공개 속성',
        ];

        foreach($taxonomies as $taxonomy) {

            $formOrder[] = 'taxo_'. $taxonomy->id;
            $excels[0]['taxo_'. $taxonomy->id] = xe_trans($taxonomy->name);
        }

        foreach($config['formColumns'] as $index => $column) {
            /**
             * 다이나믹 필드 column 조회
             */
            $fieldType = \XeDynamicField::get($config->get('documentGroup'), $column);
            if($fieldType) {
                $label = xe_trans($column_labels[$column]);
                /**
                 * 특수 필드 사용 시 조건 추가
                 */
                if($fieldType->getTableName() === 'field_dynamic_factory_super_relate') {
                    /**
                     * RelateCPT 작성 시 2가지 필드만 필요
                     */
                    //belong_application_srf_chg
                    //hidden_belong_application
                    $formOrder[] = $column.'_srf_chg';
                    $formOrder[] = 'hidden_'.$column;

                    $excels[0][$column.'_srf_chg'] = $label.' 속성';
                    $excels[0]['hidden_'.$column] = $label.' 리스트';
                } else {
                    foreach($fieldType->getColumns() as $key => $type) {
                        if($key === 'raw_data' || $key === 'logic_builder') continue;
                        if($column === 'builded') continue;
                        if($column === 'sign') continue;
                        $formOrder[] = $column.'_'.$key;
                        if($key === 'start') {
                            $text = ' 시작';
                        } else if($key === 'end') {
                            $text = ' 종료';
                        } else if($key === 'columns') {
                            $text = ' 리스트';
                        } else if($key === 'num') {
                            $text = ' 숫자만';
                        } else if($key === 'boolean') {
                            $text = ' 택1 (1 or 0)';
                        } else if($key === 'postcode') {
                            $text = ' 우편번호';
                        } else if($key === 'address1') {
                            $text = ' 주소';
                        } else if($key === 'address2') {
                            $text = ' 상세주소';
                        } else if($key === 'text') {
                            $text = '';
                        } else {
                            $text = ' '.$key;
                        }
                        if( $fieldType->getTableName() === 'field_dynamic_field_extend_calendar') {
                            $text = $text.'시간 0000-00-00 00:00';
                        }

                        $excels[0][$column.'_'.$key] = $label.$text;
                    }
                }
            } else {
                if($column === 'content') continue;
                $excels[0][$column] = $column_labels[$column];
                $formOrder[] = $column;
            }
        }

//        $excels[0]['user_country'] = '작성자 국가';
        $excels[0]['created_at'] = '작성일';
        $excels[0]['week'] = 'Week (1~52)';
        $excels[0]['YYYY-mm-dd'] = 'YYYY-mm-dd';
//        $formOrder[] = 'user_country';
        $formOrder[] = 'created_at';
        $formOrder[] = 'week';
        $formOrder[] = 'YYYY-mm-dd';

        $headers = array(
            "Content-type" => "Type:text/csv; charset=UTF-8;",
            "Content-Disposition" => 'attachment; filename='.$cpt->menu_name.'_'.date('Y_m_d H_i_s').'.csv',
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
        );

        $headerText = '';
        foreach($formOrder as $column) {
            if($headerText === '') {
                $headerText = $column;
            } else {
                if($column === 'id') {
                    $headerText = $headerText.',doc_id';
                } else {
                    $headerText = $headerText.','.$column;
                }
            }
        }

        $test = explode(',', $headerText);
        if((int) $request->get('test' , 0) === 1) {
            dd($docData);
        }
        foreach($docData as $index => $data) {
            $inx = $index + 1;
            $doc_items = $data->getAttributes();
            $relateCptId = '';
            foreach($test as $key => $val) {

                if(strpos($val,"taxo_") !== false) {
                    $category_id = (int) str_replace('taxo_', '', $val);
                    $categories = app('overcode.df.taxonomyHandler')->getItemOnlyTargetId($data->id)->where('category_id', $category_id)->pluck('id');
//                    $excels[$inx][$val] = json_enc($categories);
                    $excels[$inx][$val] = json_enc($categories);
                    continue;
                }
                $writer_data = XeUser::where('id', $doc_items['user_id'])->first();
                if($val === 'no') {
                    $excels[$inx][$val] = $inx + 1;
                    continue;
                }

                if($val === 'email') {
                    if($writer_data) $excels[$inx][$val] = $writer_data->email;
                    else $excels[$inx][$val] = '-';
                    continue;
                }

                if($val === 'name') {
                    if($writer_data) $excels[$inx][$val] = $writer_data->display_name;
                    else $excels[$inx][$val] = '-';
                    continue;
                }

//                if($val === 'user_country') {
//                    $country = $writer_data->country ?? '-';
//                    $excels[$inx][$val] = '-';
//                    if($writer_data) {
//                        if($country == 'in') {
//                            $excels[$inx][$val] = 'India';
//                        } else if($country == 'us') {
//                            $excels[$inx][$val] = 'USA';
//                        } else {
//                            $excels[$inx][$val] = 'Korea';
//                        }
//                    }
//                    continue;
//                }

                //   $formOrder[] = 'week';
                //        $formOrder[] = 'yyyy_mm_dd';
                if($val === 'week') {
                    $dt = Carbon::parse($data->created_at);
                    $excels[$inx][$val] = $dt->weekOfYear ?: 0;
                    continue;
                }
                if($val === 'YYYY-mm-dd') {
                    $excels[$inx][$val] = date('Y-m-d', strtotime($data->created_at));
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
                    if(count($item_realteCptData) === 0){
                        $excels[$inx][$relateCptId.'_srf_chg'] = 1;
                        $excels[$inx][$val] = '[]';
                    } else {
                        $excels[$inx][$val] = json_enc($item_realteCptData);
                    }
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

        if((int) $request->get('test' , 0) === 2) {
            dd($docData);
        }

        header("Content-Type:text/csv; charset=utf-8");
        header("Content-Disposition:attachment;filename=".$cpt->menu_name."_".date('Y_m_d H_i_s').".csv");
        header("Pragma: no-cache");
        header("Expires: 0");

        //fopen 전 이거 넣어야 한글 안 깨짐
        echo "\xEF\xBB\xBF";

        $file = fopen('php://output', 'w');
        fputcsv($file, $formOrder);

        foreach($excels as $item) {
            fputcsv($file, $item);
        }

        $output = stream_get_contents($file);
        fclose($file);

        return $output;
    }


    public function downloadExcel($cpt_id, Request $request) {
        $count = CptDocument::division($cpt_id)->where('instance_id', $cpt_id)->where('type', $cpt_id)->count();
        if($count === 0) return redirect()->back()->with('alert', ['type' => 'danger', 'message' => '문서를 하나 이상 작성 후 다운로드 해주세요.']);

        $query = $this->dfService->getItemsWhereQuery(array_merge($request->all(), [
            'force' => true,
            'cpt_id' => $cpt_id
        ]));

        // 검색 조건 추가
        $query = $this->makeWhere($query, $request);

        $limit = $request->get('limitCount') ? +$request->get('limitCount') : 100;
        if($limit <= 0) $limit = 10;
        else if($limit > 1000) $limit = 1000;
        $page = (int) $request->get('ep') ?: 1;
        if($page < 1) $page = 1;

        $query->orderBy(CptDocument::CREATED_AT, 'asc');

        $docData = $query->paginate($limit, ['*'], 'page', $page);

        if(count($docData) === 0) return redirect()->back()->with('alert', ['type' => 'danger', 'message' => '조회된 문서가 0개 입니다']);

        $taxonomyHandler = app('overcode.df.taxonomyHandler');
        $taxonomies = $taxonomyHandler->getTaxonomies($cpt_id);

        $cells = [
            [40,'no'],
            [20,'doc_id'],
            [30,'name'],
            [30,'email'],
            [50,'cpt_status'],
        ];
        $excels = [
            [
                'no' => '넘버',
                'doc_id' => '문서 ID',
                'name' => '작성자 이름',
                'email' => '작성자 이메일',
                'cpt_status' => '공개 속성',
            ]
        ];

        foreach($taxonomies as $taxonomy) {
            $cells[] = [40, 'taxo_'. $taxonomy->id];
            $excels[0]['taxo_'. $taxonomy->id] =  xe_trans($taxonomy->name);
        }

        $headerText = '';
        foreach($cells as $column) {
            if($headerText === '') {
                $headerText = $column[1];
            } else {
                if($column === 'id') {
                    $headerText = $headerText.',doc_id';
                } else {
                    $headerText = $headerText.','.$column[1];
                }
            }
        }

        $test = explode(',', $headerText);

        foreach($docData as $index => $data) {
            $inx = $index + 1;
            if(!$data) continue;
            $relateCptId = '';
            foreach($test as $key => $val) {

                if(strpos($val,"taxo_") !== false) {
                    $category_id = (int) str_replace('taxo_', '', $val);
                    $categories = app('overcode.df.taxonomyHandler')->getItemOnlyTargetId($data->id)->where('category_id', $category_id);
                    $cateText = '';
                    foreach($categories as $category) {
                        if($cateText === '') {
                            $cateText = xe_trans($category->word);
                        } else {
                            $cateText = $cateText.', '.xe_trans($category->word);
                        }
                    }

                    if($cateText === '') {
                        $cateText = '선택된 카테고리 없음';
                    }
//                    $excels[$inx][$val] = json_enc($categories);
                    $excels[$inx][$val] = $cateText;
                    continue;
                }
                $writer_data = XeUser::where('id', $data->user_id)->first();
                if($val === 'no') {
                    $excels[$inx][$val] = $inx + 1;
                    continue;
                }

                if($val === 'email') {
                    if($writer_data) $excels[$inx][$val] = $writer_data->email;
                    else $excels[$inx][$val] = '대상회원 정보가 없습니다';
                    continue;
                }

                if($val === 'name') {
                    if($writer_data) $excels[$inx][$val] = $writer_data->display_name;
                    else $excels[$inx][$val] = '대상회원 정보가 없습니다';
                    continue;
                }

                if($val == "app_version_text") {
                    $excels[$inx][$val] = (string) $data->$val;
                }

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
                        $excels[$inx][$val] = $data->id;
                        continue;
                    }
                    //Content에 포함된 /r/n으로 인한 오작동 방지용 json 인코딩
                    if($val === 'content') {
                        $data->$val = str_replace("\r\n", '<br>', $data->$val);
                    }

                    if(strpos($val, 'belong_') !== false) {
                        if(!$data->belongDocument($val, 'documents_'.$data->instance_id)->first()) {
                            $excels[$inx][$val] = '선택된 문서 없음';
                            continue;
                        } else {
                            $relateDocName = '';
                            foreach($data->belongDocument($val, 'documents_'.$data->instance_id) as $relateDocument) {
                                if($relateDocName === '') {
                                    $relateDocName = $relateDocument->title;
                                } else {
                                    $relateDocName = $relateDocName.', '.$relateDocument->title;
                                }
                            }
                            $excels[$inx][$val] = $relateDocName;
                            continue;
                        }
                    }
                    $excels[$inx][$val] = $data->$val;
                }
            }
        }

        $callback = function () use ($cells, $excels) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $alpha = range('A', 'Z');
            foreach($cells as $i => $val){
                $cellName = $alpha[$i].'1';
                $sheet->getColumnDimension($alpha[$i])->setWidth($val[0]);
                $sheet->getRowDimension('1')->setRowHeight(25);
                $sheet->setCellValue($cellName, $val[1]);
                $sheet->getStyle($cellName)->getFont()->setBold(true);
                $sheet->getStyle($cellName)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($cellName)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            }

            for ($i = 2; $row = array_shift($excels); $i++) {
                foreach ($cells as $key => $val) {
                    if(count($row) <= 1) continue;
                    if(!isset($row[$val[1]])) continue;
                    $cellName = $alpha[$key].$i;
                    $sheet->setCellValue($cellName, $row[$val[1]]);
                }
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
//            dd($writer);
        };
//        $callback();

        $filename = '엑셀 다운로드';
        $headers = array(
            "Content-type" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "Content-Disposition" => 'attachment; filename=' . $filename . '.xlsx',
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        );

        return \Illuminate\Support\Facades\Response::stream($callback, 200, $headers);
        return [$headers, $callback];

    }

    public function downloadFOTAExcel($cpt_id, Request $request) {
        $count = CptDocument::division($cpt_id)->where('instance_id', $cpt_id)->where('type', $cpt_id)->count();
        if($count === 0) return redirect()->back()->with('alert', ['type' => 'danger', 'message' => '문서를 하나 이상 작성 후 다운로드 해주세요.']);

        $query = $this->dfService->getItemsWhereQuery(array_merge($request->all(), [
            'force' => true,
            'cpt_id' => $cpt_id
        ]));

        $config = $this->configHandler->getConfig($cpt_id);

        // 검색 조건 추가
        $query = $this->makeWhere($query, $request);
        // 정렬
        $orderType = $request->get('order_type', '');

        $perPage = $request->get('limitCount') ? +$request->get('limitCount') : 100;
        if($perPage <= 0) $perPage = 10;
        $page = (int) $request->get('ep') ?: 1;
        if($page < 1) $page = 1;

        //TODO orderBy 오류 있어서 임시 제거
        //TODO 부산경총 오류
        if ($orderType == '') {
            // order_type 이 없을때만 dyFac Config 의 정렬을 우선 적용한다.
            $orders = $config->get('orders', []);
            foreach ($orders as $order) {
                $arr_order = explode('|@|',$order);
                $sort = 'asc';
                if($arr_order[1] === 'asc') $sort = 'desc';
                $query->orderBy($arr_order[0], $sort);
            }
            $query->orderBy('head', 'asc');
        } elseif ($orderType == 'assent_count') {
            $query->orderBy('assent_count', 'asc')->orderBy('head', 'asc');
        } elseif ($orderType == 'recently_created') {
            $query->orderBy(CptDocument::CREATED_AT, 'asc')->orderBy('head', 'asc');
        } elseif ($orderType == 'recently_published') {
            $query->orderBy('published_at', 'asc')->orderBy('head', 'asc');
        } elseif ($orderType == 'recently_updated') {
            $query->orderBy(CptDocument::UPDATED_AT, 'asc')->orderBy('head', 'asc');
        }

        $docData = $query->paginate($perPage, ['*'], 'page', $page);

        if(count($docData) === 0) return redirect()->back()->with('alert', ['type' => 'danger', 'message' => '조회된 문서가 0개 입니다']);
        $cpt = app('overcode.df.service')->getItem($cpt_id);
        $config = $this->configHandler->getConfig($cpt_id);
        $column_labels = $this->configHandler->getColumnLabels($config);

        $taxonomyHandler = app('overcode.df.taxonomyHandler');
        $taxonomies = $taxonomyHandler->getTaxonomies($cpt_id);

        $cells = [
            [10,'no'],
            [20,'doc_id'],
            [20,'name'],
            [30,'email'],
            [10,'cpt_status'],
            [20,'title'],
//            [50,'content'],
            [10,'binary_pass'],
        ];
        $excels = [
            [
                'no' => '넘버',
                'doc_id' => '문서 ID',
                'name' => '작성자 이름',
                'email' => '작성자 이메일',
                'cpt_status' => '공개 속성',
                'title' => '제목',
//                'content' => '로그내용',
                'binary_pass' => '바이너리 전송',
            ]
        ];

        foreach($taxonomies as $taxonomy) {
            $cells[] = [20, 'taxo_'. $taxonomy->id];
            $excels[0]['taxo_'. $taxonomy->id] =  xe_trans($taxonomy->name);
        }
        foreach($config['formColumns'] as $index => $column) {
            /**
             * 다이나믹 필드 column 조회
             */
            $fieldType = \XeDynamicField::get($config->get('documentGroup'), $column);
            if($fieldType) {
                $label = xe_trans($column_labels[$column]);
                /**
                 * 특수 필드 사용 시 조건 추가
                 */

                if($fieldType->getTableName() === 'field_dynamic_factory_super_relate' || $fieldType->getTableName() === 'df_super_relate' ) {
//                    $cells[] = [40, $column];
//                    $excels[0][$column] =  $label;
                } else {
                    foreach($fieldType->getColumns() as $key => $type) {
                        if($key === 'raw_data' || $key === 'logic_builder') continue;
                        if($column === 'builded') continue;
                        if($column === 'sign') continue;
                        if($column === 'app_version') continue;
                        if($key === 'start') {
                            $text = ' 시작';
                        } else if($key === 'end') {
                            $text = ' 종료';
                        } else if($key === 'columns') {
                            $text = ' 리스트';
                        } else if($key === 'num') {
                            $text = ' 숫자만';
                        } else if($key === 'boolean') {
                            $text = ' 택1 (1 or 0)';
                        } else if($key === 'postcode') {
                            $text = ' 우편번호';
                        } else if($key === 'address1') {
                            $text = ' 주소';
                        } else if($key === 'address2') {
                            $text = ' 상세주소';
                        } else if($key === 'text') {
                            $text = '';
                        } else {
                            $text = ' '.$key;
                        }
                        if( $fieldType->getTableName() === 'field_dynamic_field_extend_calendar') {
                            $text = $text.'시간 0000-00-00 00:00';
                        }

                        $excels[0][$column.'_'.$key] = $label.$text;
                        $cells[] = [30, $column.'_'.$key];
                    }
//                    dd($fieldType->getRules(), $column , array_keys($fieldType->getRules())[0]);
                }
            } else {
                if($column === 'title' || $column === 'content') {
                    continue;
                } else {
                    $label = $column;
                }
                $cells[] = [20, $column];
                $excels[0][$column] = $label;
            }
        }

        $excels[0]['created_at'] = '작성일';
        $excels[0]['user_group'] = '작성자 그룹';
//        $excels[0]['user_country'] = '작성자 국가';
        $excels[0]['week'] = 'Week (1~52)';
        $excels[0]['YYYY'] = 'YYYY';
        $excels[0]['mm-dd'] = 'mm-dd';
        if($cpt_id == 'lg_blackbox_fota_log' || $cpt_id == 'lge_global_fota_log') {
            $excels[0]['app_version_text'] = '어플리케이션 버전';
        }
        $cells[] = [20, 'created_at'];
        $cells[] = [20, 'user_group'];
//        $cells[] = [20, 'user_country'];
        $cells[] = [10, 'week'];
        $cells[] = [10, 'YYYY'];
        $cells[] = [10, 'mm-dd'];
        if($cpt_id == 'lg_blackbox_fota_log' || $cpt_id == 'lge_global_fota_log') {
            $cells[] = [10, 'app_version_text'];
        }

        $headerText = '';
        foreach($cells as $column) {
            if($headerText === '') {
                $headerText = $column[1];
            } else {
                if($column === 'id') {
                    $headerText = $headerText.',doc_id';
                } else {
                    $headerText = $headerText.','.$column[1];
                }
            }
        }

        $test = explode(',', $headerText);

        foreach($docData as $index => $data) {
            $inx = $index + 1;
            if(!$data) continue;
            $relateCptId = '';
            $user = app('xe.user')->users()->with('groups', 'emails', 'accounts')->find($data->user_id);
            foreach($test as $key => $val) {

                if(strpos($val,"taxo_") !== false) {
                    $category_id = (int) str_replace('taxo_', '', $val);
                    $categories = app('overcode.df.taxonomyHandler')->getItemOnlyTargetId($data->id)->where('category_id', $category_id);
                    $cateText = '';
                    foreach($categories as $category) {
                        if($cateText === '') {
                            $cateText = xe_trans($category->word);
                        } else {
                            $cateText = $cateText.', '.xe_trans($category->word);
                        }
                    }

                    if($cateText === '') {
                        $cateText = '선택된 카테고리 없음';
                    }
//                    $excels[$inx][$val] = json_enc($categories);
                    $excels[$inx][$val] = $cateText;
                    continue;
                }
                $writer_data = XeUser::where('id', $data->user_id)->first();
                if($val === 'no') {
                    $excels[$inx][$val] = $inx + 1;
                    continue;
                }

                if($val === 'email') {
                    if($writer_data) $excels[$inx][$val] = $writer_data->email;
                    else $excels[$inx][$val] = '대상회원 정보가 없습니다';
                    continue;
                }

                if($val === 'name') {
                    if($writer_data) $excels[$inx][$val] = $writer_data->display_name;
                    else $excels[$inx][$val] = '대상회원 정보가 없습니다';
                    continue;
                }

                if($val === 'created_at') {
                    $excels[$inx][$val] = date('Y-m-d H:i:s', strtotime($data->created_at));
                    continue;
                }

                if($val === 'week') {
                    $dt = Carbon::parse($data->created_at);
                    $excels[$inx][$val] = $dt->weekOfYear ?: 0;
                    continue;
                }

                if($val === 'YYYY') {
                    $excels[$inx][$val] = date('Y', strtotime($data->created_at));
                    continue;
                }

                if($val === 'mm-dd') {
                    $excels[$inx][$val] = date('m-d', strtotime($data->created_at));
                    continue;
                }

                if($val === 'user_group') {
                    $groups = '-';
                    if($user) {
                        foreach ($user->groups as $group) {
                            if ($groups == '-') {
                                $groups = $group->name;
                            } else {
                                $groups = $groups . ',' . $group->name;
                            }
                        }
                    }
                    $excels[$inx][$val] = $groups;
                    continue;
                }

//                if($val === 'user_country') {
//                    $country = $user->country ?? '-';
//                    $excels[$inx][$val] = '-';
//                    if($user) {
//                        if($country == 'in') {
//                            $excels[$inx][$val] = 'India';
//                        } else if($country == 'us') {
//                            $excels[$inx][$val] = 'USA';
//                        } else {
//                            $excels[$inx][$val] = 'Korea';
//                        }
//                    }
//                    continue;
//                }

                if($val == "app_version_text") {
                    $excels[$inx][$val] = (string) $data->$val;
                    continue;
                }

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
                        $excels[$inx][$val] = $data->id;
                        continue;
                    }
                    //Content에 포함된 /r/n으로 인한 오작동 방지용 json 인코딩
                    if($val === 'binary_pass') {
                        $data->binary_pass = '-';
                        if(strpos($data->content, 'PGM 해쉬전송에 실패함') !== false || strpos($data->content, 'PGM hash transfer failed') !== false) {
                            $data->binary_pass = "-";
                        } else if(strpos($data->content, 'Binary Transfer Ended') !== false || strpos($data->content, '바이너리 전송 종료됨') !== false) {
                            if(app('xe.translator')->getLocale() == 'ko') {
                                $data->binary_pass = "Hex 전달";
                            } else {
                                $data->binary_pass = "Hex Transfer";
                            }
                        }
                    }

                    if(strpos($val, 'belong_') !== false) {
                        if(!$data->belongDocument($val, 'documents_'.$data->instance_id)->first()) {
                            $excels[$inx][$val] = '선택된 문서 없음';
                            continue;
                        } else {
                            $relateDocName = '';
                            foreach($data->belongDocument($val, 'documents_'.$data->instance_id) as $relateDocument) {
                                if($relateDocName === '') {
                                    $relateDocName = $relateDocument->title;
                                } else {
                                    $relateDocName = $relateDocName.', '.$relateDocument->title;
                                }
                            }
                            $excels[$inx][$val] = $relateDocName;
                            continue;
                        }
                    }
                    $excels[$inx][$val] = $data->$val;
                }
            }
        }

        $callback = function () use ($cells, $excels) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $alpha = range('A', 'Z');
            foreach($cells as $i => $val){
                $cellName = $alpha[$i].'1';
                $sheet->getColumnDimension($alpha[$i])->setWidth($val[0]);
                $sheet->getRowDimension('1')->setRowHeight(25);
                $sheet->setCellValue($cellName, $val[1]);
                $sheet->getStyle($cellName)->getFont()->setBold(true);
                $sheet->getStyle($cellName)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($cellName)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            }

            for ($i = 2; $row = array_shift($excels); $i++) {
                foreach ($cells as $key => $val) {
                    if(count($row) <= 1) continue;
                    if(!isset($row[$val[1]])) continue;
                    $cellName = $alpha[$key].$i;
                    $sheet->setCellValue($cellName, $row[$val[1]]);
                }
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
//            dd($writer);
        };
//        $callback();

        $filename = '엑셀 다운로드';
        $headers = array(
            "Content-type" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "Content-Disposition" => 'attachment; filename=' . $filename . '.xlsx',
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        );

        return \Illuminate\Support\Facades\Response::stream($callback, 200, $headers);
        return [$headers, $callback];

    }

    public function isJson($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
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
            if($key === 0 || $key === 1) continue;

            $relateCptId = '';
            for($i = 0; $i < count($forms[0]); $i++) {

                if(count($val) !== count($forms[0])) {
                    break;
                }
                if($forms[0][$i] === 'YYYY-mm-dd' || $forms[0][$i] === 'week') {
                    continue;
                }

                //다운로드할때 <br>로 바뀐 \r\n 원상복구
                if(strpos($forms[0][$i],"taxo_") !== false) {
                    $category_id = (int) str_replace('taxo_', '', $forms[0][$i]);

                    $decode = [];
                    if(strpos($val[$i], ',') !== false) $decode = json_dec($val[$i]);
                    else if(strpos($val[$i] , '|') !== false) $decode = json_dec(str_replace('|', ',', $val[$i]));
                    $new_cate = [];
                    foreach($decode as $decode_val) {
                        $new_cate[] = (string) $decode_val;
                    }
                    $params[$index]['cate_item_id_'.$category_id] = $new_cate;
                    //$params[$index]['cate_item_id_'.$category_id] = json_dec($val[$i]);
                }
                else if($forms[0][$i] === 'content') {
                    $params[$index][$forms[0][$i]] = str_replace('<br>', "\r\n", $val[$i]);
                }
                //RelateCPT [field ID] + _srf_chg 필드가 존재하면 field ID 기록
                else if(strpos($forms[0][$i],"_t_id")) {
                    $relateId = str_replace('_t_id', '', $forms[0][$i]);
                    $params[$index][$relateId.'_srf_chg'] = 1;
                    $params[$index]['hidden_'.$relateId][] = $val[$i];
                }
                else if(strpos($forms[0][$i],"_srf_chg")) {
//                    $relateCptId = str_replace('_srf_chg', '', $forms[0][$i]);
//                    $params[$index][$forms[0][$i]] = $val[$i];
                }
                //CSV에 기록된 RelateCPT ID 정보 json decode
                else if($forms[0][$i] === 'hidden_'.$relateCptId) {
//                    if($val[$i] === '') {
//                        $params[$index][$forms[0][$i]] = '[]';
//                    }
//                    if($val[$i] !== '') {
//                        $val[$i] = str_replace('|', ',', $val[$i]);
//                        $params[$index][$forms[0][$i]] = json_dec($val[$i]);
//                    }
//                    else $params[$index][$forms[0][$i]] = '[]';
                }
                //calendar 기록 양식에 맞게 컨버트 [ 0 => 일자 , 1 => 시간 (시:분) ]
                else if(strpos($forms[0][$i],"_date_start") !== false || strpos($forms[0][$i],"_date_end") !== false) {
                    $val[$i] = str_replace('.', '-', $val[$i]);
                    $date = date('Y-m-d', strtotime($val[$i]));
                    $time = date('H:i', strtotime($val[$i]));
                    if(strpos($forms[0][$i],"_date_end") !== false && $time === '00:00') {
                        $time = '23:59';
                    }
                    $params[$index][$forms[0][$i]] = [
                        $date,
                        $time
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
            foreach ($params as $key => $val) {
                //문서에 기록된 CPT ID
                $target_cpt_id = $cpt_id;

                //기록된 doc_id 로 작성된 CPT 문서가 있는지 체크
                $cptDocument = null;
                if(isset($val['doc_id']) && $val['doc_id'] !== "" && $val['doc_id'] !== null)
                    $cptDocument = CptDocument::division($target_cpt_id)->where('instance_id',$target_cpt_id)->where('id', $val['doc_id'])->first();

                foreach($val as $doc_key => $value) {
                    if(substr( $doc_key, (strlen($doc_key) - 7), strlen($doc_key) ) === "_column" && $val[$doc_key] === "") {
                        unset($val[$doc_key]);
                    }
                    if($doc_key === 'created_at') {
                        unset($val[$doc_key]);
                    }
                    if($doc_key === 'updated_at') {
                        unset($val[$doc_key]);
                    }
                }

                $val['user_id'] = '';
                $val['writer'] = '';
                if($val['cpt_status'] === '') $val['cpt_status'] = 'public';

                if($val['email'] !== '') {
                    $user = XeUser::where('email', $val['email'])->first();
                    if($user) {
                        $val['user_id'] = $user->id;
                        $val['writer'] = $user->display_name;
                    }
                }

                if(!$val['user_id'] || $val['user_id'] === '') {
                    $val['user_id'] = \Auth::user()->id;
                }
                if(!$val['writer'] || $val['writer'] === '') {
                    $val['writer'] = \Auth::user()->display_name;
                }

                if(!$val['title'] || $val['title'] === '') {
                    $val['title'] = date('Y-m-d H:i:s').' 문서 작성';
                }
                if(!isset($val['content'])) {
                    $val['content'] = '<p>'.date('Y-m-d H:i:s').'등록 </p>';
                }

                //Slug 추가
                if($cptDocument) {
                    $val['slug'] = $cptDocument->getSlug();
                } else {
                    unset($val['parent_id']);
                    unset($val['doc_id']);
                    $val['published_at'] = '____-__-__ __:__:__';
                    $val['slug'] = DfSlug::make($val['title'], $target_cpt_id);
                }
                unset($val['email']);

                //토큰 추가
                $val['_token'] = csrf_token();
                $val['_coverId'] = [];
                $val['_files'] = [];
                $inputs['format'] = $editor->htmlable() ? CptDocument::FORMAT_HTML : CptDocument::FORMAT_NONE;
                $inputs = new SymfonyRequest($val);
                $inputs = Request::createFromBase($inputs);
                $inputs->request->add(
                    [
                        'cpt_id' => $target_cpt_id,
                    ]
                );

                //CPT 문서가 존재하고 doc ID가 있으면 update
                if($cptDocument) app('overcode.df.service')->updateCptDocument($inputs);
                else $in = app('overcode.df.service')->storeCptDocument($inputs);
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
