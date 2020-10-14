<?php
namespace Overcode\XePlugin\DynamicFactory;

use Route;
use Xpressengine\Plugin\AbstractPlugin;
use XeRegister;

class Plugin extends AbstractPlugin
{
    /**
     * 이 메소드는 활성화(activate) 된 플러그인이 부트될 때 항상 실행됩니다.
     *
     * @return void
     */
    public function boot()
    {
        // implement code

        $this->route();
        $this->registerSettingsMenus();
        $this->registerSettingsRoute();
    }

    protected function registerSitesPermissions()
    {

    }

    protected function registerSettingsMenus()
    {
        \XeRegister::push('settings/menu', 'dynamic_factory', [
            'title' => 'Dynamic Factory',
            'description' => 'CPT를 생성하고 관리합니다.',
            'display' => true,
            'ordering' => 5000
        ]);
        \XeRegister::push('settings/menu', 'dynamic_factory.index', [
            'title' => 'DF 관리',
            'description' => '생성된 CPT를 열람합니다.',
            'display' => true,
            'ordering' => 1000
        ]);
    }

    protected function registerSettingsRoute()
    {
        Route::settings(static::getId(), function() {
            Route::group([
                'namespace' => 'Overcode\XePlugin\DynamicFactory\Controllers',
                'as' => 'd_fac.setting.'
            ], function(){
                Route::get('/', [
                    'as' => 'index',
                    'uses' => 'DynamicFactoryController@index',
                    'settings_menu' => 'dynamic_factory.index'
                ]);
                Route::get('/create', [ 'as' => 'create', 'uses' => 'DynamicFactoryController@create' ]);
                Route::post('/store_cpt', ['as' => 'store_cpt', 'uses' => 'DynamicFactoryController@storeCpt']);
            });
        });
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
