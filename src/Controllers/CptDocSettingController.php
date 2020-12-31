<?php

namespace Overcode\XePlugin\DynamicFactory\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Sections\SkinSection;
use Overcode\XePlugin\DynamicFactory\Components\Modules\CptModule\CptModule;
use Overcode\XePlugin\DynamicFactory\Handlers\ModuleConfigHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\UrlHandler;
use Xpressengine\Captcha\CaptchaManager;

class CptDocSettingController extends Controller
{
    /**
     * @var ModuleConfigHandler
     */
    protected $configHandler;

    protected $urlHandler;

    /**
     * @var \Xpressengine\Presenter\Presenter
     */
    protected $presenter;

    public function __construct(
        ModuleConfigHandler $configHandler,
        UrlHandler $urlHandler
    )
    {
        $this->configHandler = $configHandler;
        $this->urlHandler = $urlHandler;

        $this->presenter = app('xe.presenter');
        $this->presenter->setSettingsSkinTargetId(CptModule::getId());
        $this->presenter->share('urlHandler', $this->urlHandler);
    }

    public function editConfig(CaptchaManager $captcha, $instanceId)
    {
        $config = $this->configHandler->get($instanceId);

        return $this->presenter->make('module.skin', [
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
