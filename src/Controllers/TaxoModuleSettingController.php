<?php

namespace Overcode\XePlugin\DynamicFactory\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Sections\SkinSection;
use Overcode\XePlugin\DynamicFactory\Components\Modules\Taxonomy\TaxonomyModule;
use Overcode\XePlugin\DynamicFactory\Handlers\TaxoModuleConfigHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\TaxoUrlHandler;
use Xpressengine\Captcha\CaptchaManager;

class TaxoModuleSettingController extends Controller
{
    protected $configHandler;

    protected $taxoUrlHandler;

    protected $presenter;

    public function __construct(
        TaxoModuleConfigHandler $configHandler,
        TaxoUrlHandler $taxoUrlHandler
    )
    {
        $this->configHandler = $configHandler;
        $this->taxoUrlHandler = $taxoUrlHandler;

        $this->presenter = app('xe.presenter');
        $this->presenter->setSettingsSkinTargetId(TaxonomyModule::getId());
        $this->presenter->share('taxoUrlHandler', $this->taxoUrlHandler);
    }

    public function editConfig(CaptchaManager $captcha, $instanceId)
    {
        $config = $this->configHandler->get($instanceId);

        return $this->presenter->make('module.config', [
            'instanceId' => $instanceId,
            'config' => $config
        ]);
    }

    public function editSkin($instanceId)
    {
        $config = $this->configHandler->get($instanceId);

        $skinSection = new SkinSection(TaxonomyModule::getId(), $instanceId);

        return $this->presenter->make('module.skin', [
            'config' => $config,
            'instanceId' => $instanceId,
            'skinSection' => $skinSection
        ]);

    }
}
