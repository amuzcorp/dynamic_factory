<?php

namespace Overcode\XePlugin\DynamicFactory\Controllers;

use App\Http\Controllers\Controller;
use Overcode\XePlugin\DynamicFactory\Components\Modules\Taxonomy\TaxonomyModule;
use Overcode\XePlugin\DynamicFactory\Handlers\TaxoModuleConfigHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\TaxoUrlHandler;
use Auth;
use XeFrontend;
use XePresenter;
use Xpressengine\Http\Request;
use Xpressengine\Routing\InstanceConfig;

class TaxoModuleController extends Controller
{
    protected $instanceId;

    public $configHandler;

    public $taxoUrlHandler;

    public $taxonomyHandler;

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
        $this->taxonomyHandler = app('overcode.df.taxonomyHandler');

        $this->config = $configHandler->get($this->instanceId);
        if ($this->config !== null) {
            $taxoUrlHandler->setInstanceId($this->config->get('instanceId'));
            $taxoUrlHandler->setConfig($this->config);
        }

        XePresenter::setSkinTargetId(TaxonomyModule::getId());
        XePresenter::share('configHandler', $configHandler);
        XePresenter::share('taxoUrlHandler', $taxoUrlHandler);
        XePresenter::share('instanceId', $this->instanceId);
        XePresenter::share('config', $this->config);
    }

    public function index(Request $request)
    {
        XeFrontend::title($this->getSiteTitle());

        $taxo_ids = $this->config->get('taxo_ids');

        $taxonomy_items = [];
        $taxo_field_types = [];
        $groups = [];

        foreach($taxo_ids as $taxo_id){
            $taxonomy_items[$taxo_id] = $this->taxonomyHandler->getCategoryItemAttributes($taxo_id);
            $taxo_field_types[$taxo_id] = $this->taxonomyHandler->getCategoryFieldTypes($taxo_id);
            $groups[$taxo_id] = $this->taxonomyHandler->getTaxFieldGroup($taxo_id);
        }

        return XePresenter::makeAll('index',
            compact('taxonomy_items', 'taxo_field_types', 'groups')
        );
    }

    public function show(Request $request, $menuUrl, $itemId)
    {
        $user = Auth::user();

        $item = $this->taxonomyHandler->getCategoryItem($itemId);
        $fieldTypes = $this->taxonomyHandler->getCategoryFieldTypes($item->category_id);

        return XePresenter::make('show', [
            'item' => $item,
            'fieldTypes' => $fieldTypes
        ]);
    }

    private function getSiteTitle()
    {
        $siteTitle = XeFrontend::output('title');

        $instanceConfig = InstanceConfig::instance();
        $menuItem = $instanceConfig->getMenuItem();

        $title = xe_trans($menuItem['title']) . ' - ' . xe_trans($siteTitle);
        $title = strip_tags(html_entity_decode($title));

        return $title;
    }

    public function hasSlug()
    {

    }

    public function slug(Request $request, $menuUrl, $strSlug)
    {
        return $this->show($request, $menuUrl, $strSlug);
    }
}
