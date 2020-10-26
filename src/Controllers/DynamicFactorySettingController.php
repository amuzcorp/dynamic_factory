<?php
namespace Overcode\XePlugin\DynamicFactory\Controllers;

use App\Http\Sections\DynamicFieldSection;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryTaxonomyHandler;
use Overcode\XePlugin\DynamicFactory\Models\CategoryExtra;
use Overcode\XePlugin\DynamicFactory\Models\CptTaxonomy;
use Overcode\XePlugin\DynamicFactory\Plugin;
use Overcode\XePlugin\DynamicFactory\Services\DynamicFactoryService;
use App\Http\Sections\EditorSection;
use XeFrontend;
use XePresenter;
use XeLang;
use XeDB;
use Xpressengine\Category\Models\Category;
use Xpressengine\Http\Request;
use App\Http\Controllers\Controller as BaseController;

class DynamicFactorySettingController extends BaseController
{
    protected $dfService;

    protected $dfHandler;

    protected $taxonomyHandler;

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
    }

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

    public function create()
    {
        return XePresenter::make('dynamic_factory::views.settings.create', [
            'menu_order' => self::DEFAULT_MENU_ORDER,
             'labels' => $this->dfHandler->getDefaultLabels()
        ]);
    }

    public function createExtra($cpt_id)
    {
        $cpt = $this->dfService->getItem($cpt_id);

        $dynamicFieldSection = new DynamicFieldSection(
            Plugin::getId() . '_' . $cpt_id,
            \XeDB::connection(),
            true
        );

        return XePresenter::make(
            'dynamic_factory::views.settings.create_extra',
            compact('dynamicFieldSection', 'cpt'));
    }

    public function storeCpt(Request $request)
    {
        // TODO 권한체크

        $cpt = $this->dfService->storeCpt($request);

        return redirect()->route('dyFac.setting.index');
    }

    public function edit($cpt_id)
    {
        $cpt = $this->dfService->getItem($cpt_id);

        return XePresenter::make('dynamic_factory::views.settings.edit', [
            'cpt' => $cpt
        ]);
    }

    public function update(Request $request)
    {
        // TODO 권한체크
        $cpt = $this->dfService->updateCpt($request);

        return redirect()->route('dyFac.setting.edit', ['cpt_id' => $request->cpt_id]);
    }

    public function cptDocument($type = 'list')
    {

        return XePresenter::make('dynamic_factory::views.documents.list');
    }

    public function createTaxonomy($tax_id = null)
    {
        $cpt_cate_extra = new CategoryExtra();
        $cpt_taxonomy = [];
        $cpt_cate_extra->is_hierarchy = true;

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

        return XePresenter::make('dynamic_factory::views.settings.create_taxonomy',
        [
            'category' => $category,
            'cpt_cate_extra' => $cpt_cate_extra,
            'cpt_ids' => $cpt_ids,
            'cpts' => $cpts
        ]);
    }

    public function storeTaxonomy(Request $request)
    {
        //$taxonomyItem = $this->taxonomyHandler->createTaxonomy($taxonomyAttribute);
        $temp = $this->dfService->storeCptTaxonomy($request);

        return redirect()->back();  //TODO 경로 수정
    }
}
