<?php
namespace Overcode\XePlugin\DynamicFactory\Controllers;

use App\Http\Sections\DynamicFieldSection;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryHandler;
use Overcode\XePlugin\DynamicFactory\Plugin;
use Overcode\XePlugin\DynamicFactory\Services\DynamicFactoryService;
use App\Http\Sections\EditorSection;
use XeFrontend;
use XePresenter;
use XeLang;
use XeDB;
use Xpressengine\Http\Request;
//use Overcode\XePlugin\DynamicFactory\Models\??;
use App\Http\Controllers\Controller as BaseController;

class DynamicFactoryController extends BaseController
{
    protected $dfService;

    protected $dfHandler;

    const DEFAULT_MENU_ORDER = '500';

    public function __construct(DynamicFactoryService $dynamicFactoryService, DynamicFactoryHandler $dynamicFactoryHandler)
    {
        //XeFrontend::css('plugins/dynamic_factory/assets/style.css')->load();
        $this->dfService = $dynamicFactoryService;
        $this->dfHandler = $dynamicFactoryHandler;
    }

    public function index()
    {
        $title = "다이나믹 팩토리";

        // set browser title
        XeFrontend::title($title);

        $cpts = $this->dfService->getItems();

        $editorSection = new EditorSection(Plugin::getId());

        // output
        return XePresenter::make('dynamic_factory::views.settings.index', [
            'title' => $title,
            'cpts' => $cpts,
            'editorSection' => $editorSection
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
        $dynamicFieldSection = new DynamicFieldSection(
            //'cpt_' . $request->get('id'),
            'documents_' . Plugin::getId(),
            \XeDB::connection(),
            true
        );

        return XePresenter::make(
            'dynamic_factory::views.settings.create_extra',
            compact('dynamicFieldSection'));
    }

    public function storeCpt(Request $request)
    {
        // TODO 권한체크

        $cpt = $this->dfService->storeCpt($request);

        return redirect()->route('d_fac.setting.index');
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

        return redirect()->route('d_fac.setting.edit', ['cpt_id' => $request->cpt_id]);
    }

    public function dynamic()
    {
        $type = 'aaa';

        return $type;
    }
}
