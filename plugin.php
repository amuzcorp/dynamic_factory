<?php
namespace Overcode\XePlugin\DynamicFactory;

use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryConfigHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryTaxonomyHandler;
use Overcode\XePlugin\DynamicFactory\Services\DynamicFactoryService;
use Route;
use Xpressengine\Plugin\AbstractPlugin;

class Plugin extends AbstractPlugin
{
    protected $cpts;

    protected $cpts_from_plugin;

    public function register()
    {
        app()->singleton(DynamicFactoryService::class, function () {
            $dynamicFactoryHandler = app('overcode.df.handler');
            $dynamicFactoryConfigHandler = app('overcode.df.configHandler');
            $dynamicFactoryTaxonomyHandler = app('overcode.df.taxonomyHandler');

            return new DynamicFactoryService(
                $dynamicFactoryHandler,
                $dynamicFactoryConfigHandler,
                $dynamicFactoryTaxonomyHandler
            );
        });
        app()->alias(DynamicFactoryService::class, 'overcode.df.service');

        app()->singleton(DynamicFactoryHandler::class, function () {
            return new DynamicFactoryHandler();
        });
        app()->alias(DynamicFactoryHandler::class, 'overcode.df.handler');

        app()->singleton(DynamicFactoryConfigHandler::class, function () {
            $configManager = app('xe.config');

            return new DynamicFactoryConfigHandler($configManager);
        });
        app()->alias(DynamicFactoryConfigHandler::class, 'overcode.df.configHandler');

        app()->singleton(DynamicFactoryTaxonomyHandler::class, function() {
            return new DynamicFactoryTaxonomyHandler();
        });
        app()->alias(DynamicFactoryTaxonomyHandler::class, 'overcode.df.taxonomyHandler');
    }

    /**
     * 이 메소드는 활성화(activate) 된 플러그인이 부트될 때 항상 실행됩니다.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadCpts();

        $this->route();
        $this->registerSettingsMenus();
        $this->registerSettingsRoute();
    }

    protected function registerSitesPermissions()
    {

    }

    protected function loadCpts()
    {
        $dfService = app('overcode.df.service');
        $this->cpts = $dfService->getItems();

        $this->cpts_from_plugin = \XeRegister::get('dynamic_factory');
    }

    protected function registerSettingsMenus()
    {
        \XeRegister::push('settings/menu', 'dynamic_factory', [
            'title' => 'Dynamic Factory',
            'description' => '사용자 정의 유형과 확장 필드와 분류를 생성하고 관리합니다.',
            'display' => true,
            'ordering' => 5000
        ]);
        \XeRegister::push('settings/menu', 'dynamic_factory.index', [
            'title' => '사용자 정의 유형 관리',
            'display' => true,
            'ordering' => 1000
        ]);

        foreach($this->cpts as $val){
            \XeRegister::push('settings/menu', $val->menu_path . $val->cpt_id, [
                'title' => $val->menu_name,
                'description' => $val->description,
                'display' => true,
                'ordering' => $val->menu_order
            ]);
        }

        if($this->cpts_from_plugin) {
            foreach ($this->cpts_from_plugin as $val) {
                \XeRegister::push('settings/menu', $val->cpt_id, [
                    'title' => $val->menu_name,
                    'description' => $val->description,
                    'display' => true,
                    'ordering' => $val->menu_order
                ]);
            }
        }
    }

    protected function registerSettingsRoute()
    {
        Route::settings(static::getId(), function() {
            Route::group([
                'namespace' => 'Overcode\XePlugin\DynamicFactory\Controllers',
                'as' => 'dyFac.setting.'
            ], function(){
                Route::get('/', [
                    'as' => 'index',
                    'uses' => 'DynamicFactorySettingController@index',
                    'settings_menu' => 'dynamic_factory.index'
                ]);
                Route::get('/create', [ 'as' => 'create', 'uses' => 'DynamicFactorySettingController@create' ]);
                Route::post('/store_cpt', ['as' => 'store_cpt', 'uses' => 'DynamicFactorySettingController@storeCpt']);
                Route::get('/create_extra/{cpt_id}', [ 'as' => 'create_extra', 'uses' => 'DynamicFactorySettingController@createExtra' ]);
                Route::get('/edit/{cpt_id}', [ 'as' => 'edit', 'uses' => 'DynamicFactorySettingController@edit' ]);
                Route::post('/update/{cpt_id?}', [ 'as' => 'update', 'uses' => 'DynamicFactorySettingController@update' ]);
                Route::get('/create_taxonomy/{tax_id?}', [ 'as' => 'create_taxonomy', 'uses' => 'DynamicFactorySettingController@createTaxonomy' ]);
                Route::post('/store_cpt_tax', ['as' => 'store_cpt_tax', 'uses' => 'DynamicFactorySettingController@storeTaxonomy']);
            });
        });

        Route::settings(static::getId(), function () {
            foreach($this->cpts as $val) {
                Route::get('/'.$val->cpt_id. '/{type?}', [
                    'as' => 'dyFac.setting.'.$val->cpt_id,
                    'uses' => 'DynamicFactorySettingController@cptDocument',
                    'settings_menu' => $val->menu_path . $val->cpt_id
                ]);
            }
        },['namespace' => 'Overcode\XePlugin\DynamicFactory\Controllers']);

        if($this->cpts_from_plugin) {
            Route::settings(static::getId(), function () {
                foreach ($this->cpts_from_plugin as $val) {
                    Route::get('/'.$val->cpt_id. '/{type?}', [
                        'as' => 'dyFac.setting.'.$val->cpt_id,
                        'uses' => 'DynamicFactorySettingController@cptDocument',
                        'settings_menu' => $val->cpt_id
                    ]);
                }
            },['namespace' => 'Overcode\XePlugin\DynamicFactory\Controllers']);
        }
    }

    protected function route()
    {
        // implement code

        Route::fixed(
            $this->getId(),
            function () {
                Route::get('/', [
                    'as' => 'dynamic_factory::index','uses' => 'Overcode\XePlugin\DynamicFactory\Controllers\Controller@index'
                ]);
            }
        );

    }

    protected function reserveSlugUrl()
    {

    }

    /**
     * 플러그인이 활성화될 때 실행할 코드를 여기에 작성한다.
     *
     * @param string|null $installedVersion 현재 XpressEngine에 설치된 플러그인의 버전정보
     *
     * @return void
     */
    public function activate($installedVersion = null)
    {
        // implement code
    }

    /**
     * 플러그인을 설치한다. 플러그인이 설치될 때 실행할 코드를 여기에 작성한다
     *
     * @return void
     */
    public function install()
    {
        $migration = new Migrations();
        if ($migration->checkInstalled() === false) {
            $migration->install();
        }

        /** @var DynamicFactoryConfigHandler $configHandler */
        $configHandler = app('overcode.df.configHandler');
        $configHandler->storeDfConfig();
    }

    /**
     * 해당 플러그인이 설치된 상태라면 true, 설치되어있지 않다면 false를 반환한다.
     * 이 메소드를 구현하지 않았다면 기본적으로 설치된 상태(true)를 반환한다.
     *
     * @return boolean 플러그인의 설치 유무
     */
    public function checkInstalled()
    {
        $migration = new Migrations();
        if ($migration->checkInstalled() === false) {
            return false;
        }

        return true;
    }

    /**
     * 플러그인을 업데이트한다.
     *
     * @return void
     */
    public function update()
    {
        $migration = new Migrations();
        if ($migration->checkInstalled() === false) {
            $migration->install();
        }
    }

    /**
     * 해당 플러그인이 최신 상태로 업데이트가 된 상태라면 true, 업데이트가 필요한 상태라면 false를 반환함.
     * 이 메소드를 구현하지 않았다면 기본적으로 최신업데이트 상태임(true)을 반환함.
     *
     * @return boolean 플러그인의 설치 유무,
     */
    public function checkUpdated()
    {
        $migration = new Migrations();
        if ($migration->checkInstalled() === false) {
            return false;
        }

        return true;
    }
}
