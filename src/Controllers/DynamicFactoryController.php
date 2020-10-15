<?php
namespace Overcode\XePlugin\DynamicFactory\Controllers;

use Overcode\XePlugin\DynamicFactory\Services\DynamicFactoryService;
use XeFrontend;
use XePresenter;
use XeLang;
use Plugin;
use Xpressengine\Http\Request;
//use Overcode\XePlugin\DynamicFactory\Models\??;
use App\Http\Controllers\Controller as BaseController;

class DynamicFactoryController extends BaseController
{
    protected $dfService;

    public function __construct(DynamicFactoryService $dynamicFactoryService)
    {
        //XeFrontend::css('plugins/dynamic_factory/assets/style.css')->load();
        $this->dfService = $dynamicFactoryService;
    }

    public function index()
    {
        $title = "다이나믹 팩토리";

        // set browser title
        XeFrontend::title($title);

        // output
        return XePresenter::make('dynamic_factory::views.settings.index', [
            'title' => $title
        ]);
    }

    public function create()
    {
        $menu_order = 100;

        return XePresenter::make('dynamic_factory::views.settings.create', [
            'menu_order' => $menu_order
        ]);
    }

    public function storeCpt(Request $request)
    {
        // TODO 권한체크

        $cpt = $this->dfService->storeCpt($request);

        return redirect()->route('d_fac.setting.index');
    }

    public function test(Request $request)
    {
        //$items = $this->dService->getItemsJson($request->all());

        return XePresenter::makeApi($request->all());
    }

    public function dynamic()
    {
        $type = 'aaa';

        return $type;
    }
}
