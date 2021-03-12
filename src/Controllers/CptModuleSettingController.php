<?php

namespace Overcode\XePlugin\DynamicFactory\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Sections\SkinSection;
use Overcode\XePlugin\DynamicFactory\Components\Modules\Cpt\CptModule;
use Overcode\XePlugin\DynamicFactory\Handlers\CptModuleConfigHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\CptPermissionHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\CptUrlHandler;
use Xpressengine\Captcha\CaptchaManager;
use Xpressengine\Http\Request;

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

    /**
     * global permission edit
     *
     * @param CptPermissionHandler $cptPermission
     * @return mixed|\Xpressengine\Presenter\Presentable
     */
    public function editGlobalPermission(CptPermissionHandler $cptPermission)
    {
        $perms = $cptPermission->getGlobalPerms();

        return $this->presenter->make('global.permission', [
            'perms' => $perms,
        ]);
    }

    /**
     * global permission update
     *
     * @param Request $request request
     * @param CptPermissionHandler $cptPermission
     * @return mixed
     */
    public function updateGlobalPermission(Request $request, CptPermissionHandler $cptPermission)
    {
        $cptPermission->setGlobal($request);

        return redirect()->to($this->cptUrlHandler->managerUrl('global.permission'));
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

    /**
     * edit permission
     *
     * @param CptPermissionHandler  $cptPermission
     * @param string                $instanceId
     *
     * @return mixed|\Xpressengine\Presenter\Presentable
     */
    public function editPermission(CptPermissionHandler $cptPermission, $instanceId)
    {
        $config = $this->configHandler->get($instanceId);

        $perms = $cptPermission->getPerms($instanceId);

        return $this->presenter->make('module.permission', [
            'config' => $config,
            'instanceId' => $instanceId,
            'perms' => $perms,
        ]);
    }

    /**
     * update permission
     *
     * @param Request               $request
     * @param CptPermissionHandler  $cptPermission
     * @param string                $instanceId
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePermission(Request $request, CptPermissionHandler $cptPermission, $instanceId)
    {
        $cptPermission->set($request, $instanceId);

        return redirect()->to($this->cptUrlHandler->managerUrl('permission', ['instanceId' => $instanceId]));
    }
}
