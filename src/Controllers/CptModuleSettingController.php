<?php

namespace Overcode\XePlugin\DynamicFactory\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Sections\SkinSection;
use Overcode\XePlugin\DynamicFactory\Components\Modules\Cpt\CptModule;
use Overcode\XePlugin\DynamicFactory\Handlers\CptModuleConfigHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\CptUrlHandler;
use Xpressengine\Captcha\CaptchaManager;

class CptModuleSettingController extends Controller
{
    /**
     * @var CptModuleConfigHandler
     */
    protected $configHandler;

    protected $cptUrlHandler;

    /**
     * @var \Xpressengine\Presenter\Presenter
     */
    protected $presenter;

    public function __construct(
        CptModuleConfigHandler $configHandler,
        CptUrlHandler $cptUrlHandler
    )
    {
        $this->configHandler = $configHandler;
        $this->cptUrlHandler = $cptUrlHandler;

        $this->presenter = app('xe.presenter');
        $this->presenter->setSettingsSkinTargetId(CptModule::getId());
        $this->presenter->share('cptUrlHandler', $this->cptUrlHandler);
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

        $skinSection = new SkinSection(CptModule::getId(), $instanceId);

        return $this->presenter->make('module.skin', [
            'config' => $config,
            'instanceId' => $instanceId,
            'skinSection' => $skinSection
        ]);
    }
}
