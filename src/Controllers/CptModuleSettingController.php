<?php

namespace Overcode\XePlugin\DynamicFactory\Controllers;

use Amuz\XePlugin\Maemulmoa\Components\Modules\Vendor\VendorModule;
use App\Http\Controllers\Controller;
use App\Http\Sections\SkinSection;
use App\Http\Sections\ToggleMenuSection;
use Overcode\XePlugin\DynamicFactory\Components\Modules\Cpt\CptModule;
use Overcode\XePlugin\DynamicFactory\Handlers\CptModuleConfigHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\CptPermissionHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\CptUrlHandler;
use Overcode\XePlugin\DynamicFactory\InstanceManager;
use Xpressengine\Captcha\CaptchaManager;
use Xpressengine\Captcha\Exceptions\ConfigurationNotExistsException;
use Xpressengine\Http\Request;
use Session;
use Xpressengine\Routing\InstanceRoute;

class CptModuleSettingController extends Controller
{
    /**
     * @var CptModuleConfigHandler
     */
    protected $configHandler;

    /**
     * @var CptUrlHandler
     */
    protected $cptUrlHandler;

    /**
     * @var InstanceManager
     */
    protected $instanceManager;

    /**
     * @var \Xpressengine\Presenter\Presenter
     */
    protected $presenter;

    public function __construct(
        CptModuleConfigHandler $configHandler,
        CptUrlHandler $cptUrlHandler,
        InstanceManager $instanceManager
    )
    {
        $this->configHandler = $configHandler;
        $this->cptUrlHandler = $cptUrlHandler;
        $this->instanceManager = $instanceManager;

        $this->presenter = app('xe.presenter');
        $this->presenter->setSettingsSkinTargetId(CptModule::getId());
        $this->presenter->share('cptUrlHandler', $this->cptUrlHandler);
    }

    /**
     * global config edit
     *
     * @param CptPermissionHandler $cptPermission cpt permission handler
     * @param CaptchaManager         $captcha         Captcha manager
     * @return mixed|\Xpressengine\Presenter\Presentable
     */
    public function editGlobalConfig(CptPermissionHandler $cptPermission, CaptchaManager $captcha)
    {
        $config = $this->configHandler->getDefault();

        $perms = $cptPermission->getGlobalPerms();

        $toggleMenuSection = new ToggleMenuSection(CptModule::getId());

        Session::flash('alert', ['type' => 'success', 'message' => xe_trans('xe::processed')]);

        return $this->presenter->make('global.config', [
            'config' => $config,
            'perms' => $perms,
            'toggleMenuSection' => $toggleMenuSection,
            'captcha' => $captcha,
        ]);
    }

    /**
     * global config update
     *
     * @param Request $request request
     * @return mixed
     */
    public function updateGlobalConfig(Request $request)
    {
        if ($request->get('useCaptcha') === 'true' && !app('xe.captcha')->available()) {
            throw new ConfigurationNotExistsException();
        }

        $config = $this->configHandler->getDefault();
        $inputs = $request->except('_token');

        foreach ($inputs as $key => $value) {
            $config->set($key, $value);
        }

        $params = $config->getPureAll();
        $this->configHandler->putDefault($params);

        return redirect()->to($this->cptUrlHandler->managerUrl('global.config'));
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

    /**
     * edit
     *
     * @param CaptchaManager $captcha Captcha manager
     * @param string         $instanceId document instance id
     * @return \Xpressengine\Presenter\Presentable
     */
    public function editConfig(CaptchaManager $captcha, $instanceId)
    {
        $config = $this->configHandler->get($instanceId);

        return $this->presenter->make('module.config', [
            'instanceId' => $instanceId,
            'config' => $config,
            'captcha' => $captcha
        ]);
    }

    /**
     * update
     *
     * @param Request $request request
     * @param string  $instanceId document instance id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateConfig(Request $request, $instanceId)
    {
        if ($request->get('useCaptcha') === 'true' && !app('xe.captcha')->available()) {
            throw new ConfigurationNotExistsException();
        }

        $config = $this->configHandler->get($instanceId);
        $inputs = $request->except('_token');

        foreach ($inputs as $key => $value) {
            $config->set($key, $value);
        }

        // 상위 설정 따름 처리로 disable 된 항목 제거
        foreach ($config->getPureAll() as $key => $value) {
            // 기본 설정이 아닌 항목 예외 처리
            if ($config->getParent()->get($key) !== null && isset($inputs[$key]) === false) {
                unset($config[$key]);
            }
        }

        $this->instanceManager->updateCptConfig($config->getPureAll());

        return redirect()->to($this->cptUrlHandler->managerUrl('config', ['instanceId' => $instanceId]));
    }

    /**
     * edit skin
     *
     * @param string $instanceId document instance id
     *
     * @return mixed|\Xpressengine\Presenter\Presentable
     */
    public function editSkin($instanceId)
    {
        $config = $this->configHandler->get($instanceId);

        $instance = InstanceRoute::where('instance_id',$instanceId)->first();
        $skinSection = new SkinSection($instance['module'], $instanceId);

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
