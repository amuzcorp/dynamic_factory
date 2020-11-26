<?php

namespace Overcode\XePlugin\DynamicFactory\Controllers;

use Overcode\XePlugin\DynamicFactory\Components\Modules\CptModule;
use Overcode\XePlugin\DynamicFactory\Exceptions\NotFoundDocumentException;
use Overcode\XePlugin\DynamicFactory\Models\DfSlug;
use Overcode\XePlugin\DynamicFactory\Services\CptDocService;
use Auth;
use XeFrontend;
use XePresenter;
use App\Http\Controllers\Controller;
use Overcode\XePlugin\DynamicFactory\Handlers\ModuleConfigHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\UrlHandler;
use Xpressengine\Http\Request;
use Xpressengine\Routing\InstanceConfig;

class CptDocModuleController extends Controller
{
    protected $instanceId;

    public $urlHandler;

    public $configHandler;

    public $config;

    public function __construct(
        ModuleConfigHandler $configHandler,
        UrlHandler $urlHandler
    )
    {
        $instanceConfig = InstanceConfig::instance();
        $this->instanceId = $instanceConfig->getInstanceId();

        $this->configHandler = $configHandler;
        $this->urlHandler = $urlHandler;
        $this->config = $configHandler->get($this->instanceId);
        if ($this->config !== null) {
            $urlHandler->setInstanceId($this->config->get('instanceId'));
            $urlHandler->setConfig($this->config);
        }

        XePresenter::setSkinTargetId(CptModule::getId());
        XePresenter::share('configHandler', $configHandler);
        XePresenter::share('urlHandler', $urlHandler);
        XePresenter::share('instanceId', $this->instanceId);
        XePresenter::share('config', $this->config);
    }

    public function index(CptDocService $service, Request $request)
    {
        \XeFrontend::title($this->getSiteTitle());

        $dfConfig = app('overcode.df.configHandler')->getConfig($this->config->get('cpt_id'));
        $column_labels = app('overcode.df.configHandler')->getColumnLabels($dfConfig);

        $paginate = $service->getItems($request, $this->config);

        return XePresenter::makeAll('index', [
            'paginate' => $paginate,
            'dfConfig' => $dfConfig,
            'column_labels' => $column_labels
        ]);
    }

    public function show(
        CptDocService $service,
        Request $request,
        $menuUrl,
        $id
    )
    {
        $user = Auth::user();

        $item = $service->getItem($id, $user, $this->config);

        $dyFacConfig = app('overcode.df.configHandler')->getConfig($this->config->get('cpt_id'));
        $fieldTypes = $service->getFieldTypes($dyFacConfig);

        $dynamicFieldsById = [];
        foreach ($fieldTypes as $fieldType) {
            $dynamicFieldsById[$fieldType->get('id')] = $fieldType;
        }

        return XePresenter::make('show', [
            'item' => $item,
            'fieldTypes' => $fieldTypes,
            'dynamicFieldsById' => $dynamicFieldsById
        ]);
    }

    private function getSiteTitle()
    {
        $siteTitle = \XeFrontend::output('title');

        $instanceConfig = InstanceConfig::instance();
        $menuItem = $instanceConfig->getMenuItem();

        $title = xe_trans($menuItem['title']) . ' - ' . xe_trans($siteTitle);
        $title = strip_tags(html_entity_decode($title));

        return $title;
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

    public function slug(CptDocService $service, Request $request, $menuUrl, $strSlug)
    {
//        dd($menuUrl, $strSlug, $this->instanceId);

        $cpt_id = $this->config->get('cpt_id');

        $slug = DfSlug::where('slug', $strSlug)->where('instance_id', $cpt_id)->first();

        if ($slug === null) {
            throw new NotFoundDocumentException;
        }

        return $this->show($service, $request, $menuUrl, $slug->target_id);
    }
}
