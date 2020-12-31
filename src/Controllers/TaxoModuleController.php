<?php

namespace Overcode\XePlugin\DynamicFactory\Controllers;

use App\Http\Controllers\Controller;
use Overcode\XePlugin\DynamicFactory\Components\Modules\Taxonomy\TaxonomyModule;
use Overcode\XePlugin\DynamicFactory\Handlers\TaxoModuleConfigHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\TaxoUrlHandler;
use Auth;
use XeFrontend;
use XePresenter;
use Xpressengine\Routing\InstanceConfig;

class TaxoModuleController extends Controller
{
    protected $instanceId;

    public $configHandler;

    public $taxoUrlHandler;

    public $config;

    public function __construct(
        TaxoModuleConfigHandler $configHandler,
        TaxoUrlHandler $taxoUrlHandler
    )
    {
        $instanceConfig = InstanceConfig::instance();
        $this->instanceId = $instanceConfig->getInstanceId();

        $this->configHandler = $configHandler;
        $this->taxoUrlHandler = $taxoUrlHandler;

        XePresenter::setSkinTargetId(TaxonomyModule::getId());
        XePresenter::share('configHandler', $configHandler);
        XePresenter::share('taxoUrlHandler', $taxoUrlHandler);
        XePresenter::share('instanceId', $this->instanceId);
        XePresenter::share('config', $this->config);
    }

    public function index()
    {

    }

    public function show()
    {

    }

}
