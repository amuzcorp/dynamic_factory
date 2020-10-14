<?php
namespace Overcode\XePlugin\DynamicFactory\Controllers;

use XeFrontend;
use XePresenter;
use XeLang;
use Plugin;
use Xpressengine\Http\Request;
//use Overcode\XePlugin\DynamicFactory\Models\??;
use App\Http\Controllers\Controller as BaseController;

class DynamicFactoryController extends BaseController
{
    public function __construct()
    {
        //XeFrontend::css('plugins/dynamic_factory/assets/style.css')->load();
    }

    public function index()
    {
        //$title = xe_trans('dynamic_factory::dynamic_factory');
        $title = "제목";

        // set browser title
        XeFrontend::title($title);

        // output
        return XePresenter::make('dynamic_factory::views.settings.index', [
            'title' => $title
        ]);
    }

    public function create()
    {
        return XePresenter::make('dynamic_factory::views.settings.create');
    }

    public function storeCpt(Request $request)
    {
        return redirect()->back();
    }
}
