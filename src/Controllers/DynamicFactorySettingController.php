<?php
namespace Overcode\XePlugin\DynamicFactory\Controllers;

use App\Http\Sections\DynamicFieldSection;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryTaxonomyHandler;
use Overcode\XePlugin\DynamicFactory\Models\CategoryExtra;
use Overcode\XePlugin\DynamicFactory\Models\Cpt;
use Overcode\XePlugin\DynamicFactory\Models\CptTaxonomy;
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

    /** @var ConfigHandler $dynamicFieldConfigHandler */
    protected $dynamicFieldConfigHandler;

    const DEFAULT_MENU_ORDER = '500';

    public function __construct(
        DynamicFactoryService $dynamicFactoryService,
        DynamicFactoryHandler $dynamicFactoryHandler,
        DynamicFactoryTaxonomyHandler $dynamicFactoryTaxonomyHandler
    )
    {
        $this->dfService = $dynamicFactoryService;
        $this->dfHandler = $dynamicFactoryHandler;
        $this->taxonomyHandler = $dynamicFactoryTaxonomyHandler;
        $this->dynamicFieldConfigHandler = app('xe.dynamicField');
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

        // output
        return XePresenter::make('dynamic_factory::views.settings.index', [
            'title' => $title,
            'cpts' => $cpts
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

//        dd($menus);

        return XePresenter::make('dynamic_factory::views.settings.create', [
            'menu_order' => self::DEFAULT_MENU_ORDER,
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
//            Plugin::getId() . '_' . $cpt_id,
            'documents_' . $cpt_id,
            \XeDB::connection(),
            true
        );

        return XePresenter::make(
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

        return XePresenter::make('dynamic_factory::views.settings.edit', [
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
        $cpt = $this->dfService->updateCpt($request);

        return redirect()->route('dyFac.setting.edit', ['cpt_id' => $request->cpt_id]);
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
        else if($type == 'update'){
            return $this->documentUpdate($cpt, $request);
        }
        else if($type == 'delete'){
            return $this->documentDelete($cpt, $request);
        }

        return $this->documentList($cpt, $request);
    }

    public function documentList(Cpt $cpt, Request $request)
    {
        return XePresenter::make('dynamic_factory::views.documents.list',[
            'cpt' => $cpt,
            'current_route_name' => $request->current_route_name
        ]);
    }

    public function documentCreate(Cpt $cpt, Request $request)
    {
        $taxonomies = $this->taxonomyHandler->getTaxonomies($cpt->cpt_id);

        $dynamicFields = $this->dynamicFieldConfigHandler->gets('documents_' . $cpt->cpt_id);

        return XePresenter::make('dynamic_factory::views.documents.create',[
            'cpt' => $cpt,
            'taxonomies' => $taxonomies,
            'dynamicFields' => $dynamicFields
        ]);
    }

    public function documentUpdate(Cpt $cpt, Request $request)
    {

    }

    public function documentDelete(Cpt $cpt, Request $request)
    {

    }

    public function storeCptDocument(Request $request)
    {
        //Todo 퍼미션 체크
        $document = $this->dfService->storeCptDocument($request);

        return redirect()->route('dyFac.setting.'.$request->cpt_id, ['type' => 'list']);
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
        $category = new Category();
        if($tax_id){
            $category = $this->taxonomyHandler->getCategory($tax_id);
            $cpt_cate_extra = CategoryExtra::where('category_id', $tax_id)->first();
            $cpt_taxonomy = CptTaxonomy::where('category_id', $tax_id)->get();


        }

        foreach ($cpt_taxonomy as $cptx) {
            $cpt_ids[] = $cptx->cpt_id;
        }

        // 1. 유형 목록 불러오기 TODO 2. 다른 플러그인에서 생성된 유형 목록 불러오기
        $cpts = $this->dfService->getItems();

        //TODO tax_id 가 있으면 로드 하여 프레젠터에 보낸다.

        XeFrontend::js('/assets/core/common/js/xe.tree.js')->appendTo('body')->load();
        XeFrontend::js('/assets/core/category/Category.js')->appendTo('body')->load();

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

        return XePresenter::make('dynamic_factory::views.settings.create_taxonomy',
        [
            'category' => $category,
            'cpt_cate_extra' => $cpt_cate_extra,
            'cpt_ids' => $cpt_ids,
            'cpts' => $cpts
        ]);
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
}