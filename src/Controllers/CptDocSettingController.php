<?php

namespace Overcode\XePlugin\DynamicFactory\Controllers;

use App\Http\Controllers\Controller;
use Overcode\XePlugin\DynamicFactory\Components\Modules\CptModule;
use Overcode\XePlugin\DynamicFactory\Handlers\ModuleConfigHandler;
use Xpressengine\Captcha\CaptchaManager;

class CptDocSettingController extends Controller
{
    /**
     * @var ModuleConfigHandler
     */
    protected $configHandler;

    /**
     * @var \Xpressengine\Presenter\Presenter
     */
    protected $presenter;

    public function __construct(
        ModuleConfigHandler $configHandler
    )
    {
        $this->configHandler = $configHandler;
        $this->presenter = app('xe.presenter');

        $this->presenter->setSettingsSkinTargetId(CptModule::getId());
    }

    public function editConfig(CaptchaManager $captcha, $instanceId)
    {
        $config = $this->configHandler->get($instanceId);

        return $this->presenter->make('module.config', [
            'config' => $config
        ]);
    }
}
