<?php
namespace Overcode\XePlugin\DynamicFactory\Controllers;

use App\Http\Sections\DynamicFieldSection;
use Overcode\XePlugin\DynamicFactory\Exceptions\NotFoundDocumentException;
use Overcode\XePlugin\DynamicFactory\Handlers\CptValidatorHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryConfigHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryTaxonomyHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\CptUrlHandler;
use Overcode\XePlugin\DynamicFactory\Models\CategoryExtra;
use Overcode\XePlugin\DynamicFactory\Models\Cpt;
use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use Overcode\XePlugin\DynamicFactory\Models\CptTaxonomy;
use Overcode\XePlugin\DynamicFactory\Models\DfSlug;
use Overcode\XePlugin\DynamicFactory\Plugin;
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

class DynamicFactorySettingController extends BaseController
{
    protected $dfService;

    protected $dfHandler;

    protected $taxonomyHandler;

    protected $configHandler;

    /** @var ConfigHandler $dynamicFieldConfigHandler */
    protected $dynamicFieldConfigHandler;

    protected $presenter;

    protected $cptUrlHandler;

    protected $cptValidatorHandler;

    public function __construct(
        DynamicFactoryService $dynamicFactoryService,
        DynamicFactoryHandler $dynamicFactoryHandler,
        DynamicFactoryTaxonomyHandler $dynamicFactoryTaxonomyHandler,
        DynamicFactoryConfigHandler $configHandler,
        CptUrlHandler $cptUrlHandler,
        CptValidatorHandler $cptValidatorHandler
    )
    {
        $this->dfService = $dynamicFactoryService;
        $this->dfHandler = $dynamicFactoryHandler;
        $this->taxonomyHandler = $dynamicFactoryTaxonomyHandler;
        $this->dynamicFieldConfigHandler = app('xe.dynamicField');
        $this->configHandler = $configHandler;
        $this->cptUrlHandler = $cptUrlHandler;
        $this->cptValidatorHandler = $cptValidatorHandler;

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

        return redirect()->route('dyFac.setting.index');
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

        return redirect()->route('dyFac.setting.edit', ['cpt_id' => $request->cpt_id]);
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

        return redirect()->route('dyFac.setting.create_taxonomy', ['tax_id' => $category_id]);  //TODO 경로 수정
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

        $configName = $this->configHandler->getConfigName($cpt_id);
        $config = $this->configHandler->get($configName);

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
        $configName = $this->configHandler->getConfigName($cpt_id);

        $config = $this->configHandler->get($configName);
        $inputs = $request->except('_token');

        foreach ($inputs as $key => $val) {
            $config->set($key, $val);
        }

        $this->configHandler->modifyConfig($config);

        return redirect()->route('dyFac.setting.edit_columns', ['cpt_id' => $cpt_id]);
    }

    public function cptDocument($type = 'list', Request $request)
    {
        $current_route_name = Route::currentRouteName();
        $route_names = explode('.', $current_route_name);
        $cpt_id = $route_names[count($route_names) - 1];

        $request->current_route_name = $current_route_name;

        $cpt = $this->dfService->getItem($cpt_id);

        if($type == 'create'){
            return $this->documentCreate($cpt, $request);
        }
        else if($type == 'edit'){
            return $this->documentEdit($cpt, $request);
        }
        else if($type == 'delete'){
            return $this->documentDelete($cpt, $request);
        }

        return $this->documentList($cpt, $request);
    }

    public function documentList(Cpt $cpt, Request $request)
    {
        $cptDocs = $this->getCptDocuments($request, $cpt);

        $config = $this->configHandler->getConfig($cpt->cpt_id);
        $column_labels = $this->configHandler->getColumnLabels($config);

        return $this->presenter->make($cpt->blades['list'],[
            'cpt' => $cpt,
            'current_route_name' => $request->current_route_name,
            'cptDocs' => $cptDocs,
            'config' => $config,
            'column_labels' => $column_labels
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

        $category_items = $this->dfService->getSelectCategoryItems($cpt->cpt_id, $item->id);

        return $this->presenter->make($cpt->blades['edit'],[
            'cpt' => $cpt,
            'taxonomies' => $taxonomies,
            'dynamicFields' => $dynamicFields,
            'cptConfig' => $cptConfig,
            'dynamicFieldsById' => $dynamicFieldsById,
            'item' => $item,
            'category_items' => $category_items
        ]);
    }

    public function documentDelete(Cpt $cpt, Request $request)
    {
        // TODO 퍼미션 체크

        $doc_id = $request->get('doc_id');

        $item = CptDocument::division($cpt->cpt_id)->find($doc_id);

        app('xe.document')->remove($item);

        return $this->presenter->makeApi([]);
    }

    public function storeCptDocument(Request $request)
    {
        //Todo 퍼미션 체크
        $document = $this->dfService->storeCptDocument($request);

        return redirect()->route('dyFac.setting.'.$request->cpt_id, ['type' => 'list']);
    }

    public function updateCptDocument(Request $request)
    {
        //$cptId =$request->get('cpt_id');

        $this->dfService->updateCptDocument($request);

        return redirect()->route('dyFac.setting.'.$request->cpt_id, ['type' => 'edit', 'doc_id' => $request->doc_id]);
    }

    public function getCptDocuments($request, $cpt)
    {
        $perPage = $request->get('perPage', 20);

        $cpt_id = $cpt->cpt_id;

        $cptDocQuery = $this->dfService->getItemsWhereQuery(array_merge($request->all(), [
            'force' => true,
            'cpt_id' => $cpt_id
        ]));

        // TODO 검색 조건 설정

        $cptDocQuery = $cptDocQuery->orderBy('created_at','desc');
        //$cptDocQuery = $this->dfService->getItemsOrderQuery($cptDocQuery, $request->all());

        return $cptDocQuery->paginate($perPage, ['*'], 'page')->appends($request->except('page'));
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
            'slug' => $slug,
        ]);
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

        return $this->presenter->makeApi([]);
    }
}
