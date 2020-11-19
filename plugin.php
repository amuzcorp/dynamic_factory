<?php
namespace Overcode\XePlugin\DynamicFactory;

use Overcode\XePlugin\DynamicFactory\Handlers\CategoryDynamicFieldHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryConfigHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryDocumentHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryTaxonomyHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\UrlHandler;
use Overcode\XePlugin\DynamicFactory\Services\DynamicFactoryService;
use Route;
use XeDynamicField;
use XeInterception;
use Xpressengine\DynamicField\ConfigHandler;
use Xpressengine\Plugin\AbstractPlugin;

class Plugin extends AbstractPlugin
{
    protected $cpts;

    protected $cpts_from_plugin;

    protected $df_config;

    public function register()
    {
        $app = app();
        $app->singleton(DynamicFactoryDocumentHandler::class, function() {
            $proxyHandler = XeInterception::proxy(DynamicFactoryDocumentHandler::class);

            return new $proxyHandler(
                app('xe.db')->connection(),
                app('xe.document.config'),
                app('xe.document.instance'),
                app('request')
            );
        });
        $app->alias(DynamicFactoryDocumentHandler::class, 'overcode.df.documentHandler');

        $app->singleton(DynamicFactoryService::class, function () {
            $dynamicFactoryHandler = app('overcode.df.handler');
            $dynamicFactoryConfigHandler = app('overcode.df.configHandler');
            $dynamicFactoryTaxonomyHandler = app('overcode.df.taxonomyHandler');
            $dynamicFactoryDocumentHandler = app('overcode.df.documentHandler');

            return new DynamicFactoryService(
                $dynamicFactoryHandler,
                $dynamicFactoryConfigHandler,
                $dynamicFactoryTaxonomyHandler,
                $dynamicFactoryDocumentHandler
            );
        });
        $app->alias(DynamicFactoryService::class, 'overcode.df.service');

        $app->singleton(DynamicFactoryHandler::class, function () {
            return new DynamicFactoryHandler();
        });
        $app->alias(DynamicFactoryHandler::class, 'overcode.df.handler');

        $app->singleton(DynamicFactoryConfigHandler::class, function () {
            return new DynamicFactoryConfigHandler(
                app('xe.config'),
                XeDynamicField::getConfigHandler()
            );
        });
        $app->alias(DynamicFactoryConfigHandler::class, 'overcode.df.configHandler');

        $app->singleton(UrlHandler::class, function ($app) {
            return new UrlHandler();
        });
        $app->alias(UrlHandler::class, 'overcode.df.url');

        $app->singleton(DynamicFactoryTaxonomyHandler::class, function() {
            return new DynamicFactoryTaxonomyHandler();
        });
        $app->alias(DynamicFactoryTaxonomyHandler::class, 'overcode.df.taxonomyHandler');
    }

    /**
     * 이 메소드는 활성화(activate) 된 플러그인이 부트될 때 항상 실행됩니다.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadCpts();
        $this->CptConfigSettingFromPlugin();
        $this->CptCategorySettingFromPlugin();
        $this->CptDynamicFieldSettingFromPlugin();

        $this->route();
        $this->registerSettingsMenus();
        $this->registerSettingsRoute();
        $this->registerCategoryRoute();
    }

    protected function registerSitesPermissions()
    {

    }

    protected function loadCpts()
    {
        $dfService = app('overcode.df.service');
        // DB 에 저장된 cpt 를 불러온다.
        $this->cpts = $dfService->getItems();

        // 타 플러그인에서 등록한 cpt 를 불러온다.
        $this->cpts_from_plugin = \XeRegister::get('dynamic_factory');

        // 타 플러그인에서 등록한 cpt 의 config 를 불러온다.
        $this->df_config = \XeRegister::get('df_config');
    }

    /**
     * 타 플러그인에서 지정한 Config 를 체크하여 없으면 생성해준다.
     */
    protected function CptConfigSettingFromPlugin()
    {
        $dfConfigHandler = app('overcode.df.configHandler');
        if(isset($this->cpts_from_plugin)) {
            foreach ($this->cpts_from_plugin as $key => $val) {
                $configName = $dfConfigHandler->getConfigName($val['cpt_id']);
                $config = $dfConfigHandler->get($configName);

                // 해당 cpt_id 로 config 를 가져와서 없으면 타 플러그인에서 불러온 config 값으로 생성해준다.
                if ($config === null || !isset($config)) {
                    $dfConfigHandler->addConfig($this->df_config[$val['cpt_id']], $configName);
                }
            }
        }
    }

    /**
     * 타 플러그인에서 지정한 Category 를 체크하여 없으면 생성해준다.
     */
    protected function CptCategorySettingFromPlugin()
    {
        $dfTaxonomyHandler = app('overcode.df.taxonomyHandler');
        $dfTaxonomyHandler->createCategoryForOut();
    }

    /**
     * 타 플러그인에서 지정한 Dynamic Field 를 체크하여 없으면 생성해준다.
     */
    protected function CptDynamicFieldSettingFromPlugin()
    {
        $dfHandler = app('overcode.df.handler');
        $dfHandler->createDynamicFieldForOut();
    }

    protected function registerSettingsMenus()
    {
        \XeRegister::push('settings/menu', 'setting.dynamic_factory', [
            'title' => 'Dynamic Factory',
            'description' => '확장 필드와 카테고리를 조합한 사용자 정의 문서 생성 및 관리를 할 수 있습니다.',
            'display' => true,
            'ordering' => 100
        ]);
        \XeRegister::push('settings/menu', 'setting.dynamic_factory.index', [
            'title' => '사용자 정의 문서',
            'display' => true,
            'ordering' => 1000
        ]);
        \XeRegister::push('settings/menu', 'setting.dynamic_factory.category', [
            'title' => '카테고리',
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
            /*foreach ($this->cpts_from_plugin as $val) {
                \XeRegister::push('settings/menu', $val->menu_path . $val->cpt_id, [
                    'title' => $val->menu_name,
                    'description' => $val->description,
                    'display' => true,
                    'ordering' => $val->menu_order
                ]);
            }*/

            foreach ($this->cpts_from_plugin as $val) {
                \XeRegister::push('settings/menu', $val['menu_path'] . $val['cpt_id'], [
                    'title' => $val['menu_name'],
                    'description' => $val['description'],
                    'display' => true,
                    'ordering' => $val['menu_order']
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
                    'settings_menu' => 'setting.dynamic_factory.index'
                ]);
                Route::get('/category_list', [ 'as' => 'category', 'uses' => 'DynamicFactorySettingController@categoryList', 'settings_menu' => 'setting.dynamic_factory.category' ]);
                Route::post('/delete_category', [ 'as' => 'category.delete', 'uses' => 'DynamicFactorySettingController@categoryDelete' ]);

                Route::get('/create', [ 'as' => 'create', 'uses' => 'DynamicFactorySettingController@create' ]);
                Route::post('/store_cpt', ['as' => 'store_cpt', 'uses' => 'DynamicFactorySettingController@storeCpt']);
                Route::get('/edit_editor/{cpt_id}', [ 'as' => 'edit_editor', 'uses' => 'DynamicFactorySettingController@editEditor' ]);

                Route::get('/edit_columns/{cpt_id}', [ 'as' => 'edit_columns', 'uses' => 'DynamicFactorySettingController@editColumns' ]);
                Route::post('/update_columns/{cpt_id}', [ 'as' => 'update_columns', 'uses' => 'DynamicFactorySettingController@updateColumns' ]);

                Route::get('/create_extra/{cpt_id}', [ 'as' => 'create_extra', 'uses' => 'DynamicFactorySettingController@createExtra' ]);

                Route::get('/edit/{cpt_id}', [ 'as' => 'edit', 'uses' => 'DynamicFactorySettingController@edit' ]);
                Route::post('/update/{cpt_id?}', [ 'as' => 'update', 'uses' => 'DynamicFactorySettingController@update' ]);

                Route::get('/create_taxonomy/{tax_id?}', [ 'as' => 'create_taxonomy', 'uses' => 'DynamicFactorySettingController@createTaxonomy' ]);
                Route::get('/taxonomy_extra/{category_slug}', [ 'as' => 'taxonomy_extra', 'uses' => 'DynamicFactorySettingController@taxonomyExtra' ]);

                Route::post('/store_cpt_tax', ['as' => 'store_cpt_tax', 'uses' => 'DynamicFactorySettingController@storeTaxonomy']);

                Route::post('/store_cpt_document', ['as' => 'store_cpt_document', 'uses' => 'DynamicFactorySettingController@storeCptDocument']);
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
                /*foreach ($this->cpts_from_plugin as $val) {
                    Route::get('/'.$val->cpt_id. '/{type?}', [
                        'as' => 'dyFac.setting.'.$val->cpt_id,
                        'uses' => 'DynamicFactorySettingController@cptDocument',
                        'settings_menu' => $val->menu_path . $val->cpt_id
                    ]);
                }*/
                foreach ($this->cpts_from_plugin as $val) {
                    Route::get('/'.$val['cpt_id']. '/{type?}', [
                        'as' => 'dyFac.setting.'.$val['cpt_id'],
                        'uses' => 'DynamicFactorySettingController@cptDocument',
                        'settings_menu' => $val['menu_path'] . $val['cpt_id']
                    ]);
                }
            },['namespace' => 'Overcode\XePlugin\DynamicFactory\Controllers']);
        }
    }

    protected function registerCategoryRoute()
    {
        Route::settings('df_category', function () {

            // 이하 신규
            Route::group(['prefix' => '{id}', 'where' => ['id' => '[0-9]+'], 'namespace' => 'Overcode\XePlugin\DynamicFactory\Controllers'], function () {
                Route::get('/', ['as' => 'df.category.show', 'uses' => 'CustomCategoryController@show']);
                Route::post('item/store', [
                    'as' => 'df.category.edit.item.store',
                    'uses' => 'CustomCategoryController@storeItem'
                ]);
                Route::post('item/update', [
                    'as' => 'df.category.edit.item.update',
                    'uses' => 'CustomCategoryController@updateItem'
                ]);
                Route::post('item/destroy/{force?}', [
                    'as' => 'df.category.edit.item.destroy',
                    'uses' => 'CustomCategoryController@destroyItem'
                ]);
                Route::post('item/move', [
                    'as' => 'df.category.edit.item.move',
                    'uses' => 'CustomCategoryController@moveItem'
                ]);
                Route::get('item/roots', [
                    'as' => 'df.category.edit.item.roots',
                    'uses' => 'CustomCategoryController@roots'
                ]);
                Route::get('item/children', [
                    'as' => 'df.category.edit.item.children',
                    'uses' => 'CustomCategoryController@children'
                ]);
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
