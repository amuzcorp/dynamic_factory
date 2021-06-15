<?php
namespace Overcode\XePlugin\DynamicFactory;

use Overcode\XePlugin\DynamicFactory\Components\Modules\Cpt\CptModule;
use Overcode\XePlugin\DynamicFactory\Handlers\CptPermissionHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryConfigHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryDocumentHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryTaxonomyHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\CptModuleConfigHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\CptUrlHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\TaxoModuleConfigHandler;
use Overcode\XePlugin\DynamicFactory\Models\CptTaxonomy;
use Overcode\XePlugin\DynamicFactory\Services\CptDocService;
use Overcode\XePlugin\DynamicFactory\Services\DynamicFactoryService;
use Route;
use XeConfig;
use XeCounter;
use XeDynamicField;
use XeDocument;
use XeDB;
use XeInterception;
use Xpressengine\Config\ConfigEntity;
use Xpressengine\DynamicField\ColumnEntity;
use Xpressengine\DynamicField\ConfigHandler;
use Xpressengine\DynamicField\DynamicFieldHandler;
use Xpressengine\Plugin\AbstractPlugin;

class Plugin extends AbstractPlugin
{
    protected $cpts;

    protected $df_config;

    public function register()
    {
        $app = app();

        // DynamicFactoryDocumentHandler
        $app->singleton(DynamicFactoryDocumentHandler::class, function() {
            $proxyHandler = XeInterception::proxy(DynamicFactoryDocumentHandler::class);

            $readCounter = XeCounter::make(app('request'), 'read');
            $readCounter->setGuest();

            $voteCounter = XeCounter::make(app('request'), 'vote', ['assent', 'dissent']);

            return new $proxyHandler(
                app('xe.document'),
                app('xe.storage'),
                $readCounter,
                $voteCounter
            );
        });
        $app->alias(DynamicFactoryDocumentHandler::class, 'overcode.df.documentHandler');

        // DynamicFactoryService
        $app->singleton(DynamicFactoryService::class, function () {
            $proxyHandler = XeInterception::proxy(DynamicFactoryService::class);

            return new $proxyHandler(
                app('overcode.df.handler'),
                app('overcode.df.configHandler'),
                app('overcode.df.taxonomyHandler'),
                app('overcode.df.documentHandler'),
                app('xe.dynamicField'),
                app('xe.document')
            );
        });
        $app->alias(DynamicFactoryService::class, 'overcode.df.service');

        //CptDocService
        $app->singleton(CptDocService::class , function() {
            return new CptDocService(
                app('overcode.df.documentHandler')
            );
        });
        $app->alias(CptDocService::class, 'overcode.doc.service');

        // DynamicFactoryHandler
        $app->singleton(DynamicFactoryHandler::class, function () {
            return new DynamicFactoryHandler();
        });
        $app->alias(DynamicFactoryHandler::class, 'overcode.df.handler');

        // DynamicFactoryConfigHandler
        $app->singleton(DynamicFactoryConfigHandler::class, function () {
            return new DynamicFactoryConfigHandler(
                app('xe.config'),
                XeDynamicField::getConfigHandler()
            );
        });
        $app->alias(DynamicFactoryConfigHandler::class, 'overcode.df.configHandler');

        // CptUrlHandler
        $app->singleton(CptUrlHandler::class, function ($app) {
            return new CptUrlHandler();
        });
        $app->alias(CptUrlHandler::class, 'overcode.df.url');

        $app->singleton(Validator::class, function ($app) {
            return new Validator(app('overcode.df.cptModuleConfigHandler'), app('xe.dynamicField'));
        });
        $app->alias(Validator::class, 'overcode.df.validator');

        // DynamicFactoryTaxonomyHandler
        $app->singleton(DynamicFactoryTaxonomyHandler::class, function() {
            return new DynamicFactoryTaxonomyHandler();
        });
        $app->alias(DynamicFactoryTaxonomyHandler::class, 'overcode.df.taxonomyHandler');

        // CptModuleConfigHandler
        $app->singleton(CptModuleConfigHandler::class, function () {
            return new CptModuleConfigHandler(
                app('xe.config'),
                XeDynamicField::getConfigHandler(),
                XeDocument::getConfigHandler()
            );
        });
        $app->alias(CptModuleConfigHandler::class, 'overcode.df.cptModuleConfigHandler');

        // TaxoModuleConfigHandler
        $app->singleton(TaxoModuleConfigHandler::class, function () {
            return new TaxoModuleConfigHandler(
                app('xe.config')
            );
        });
        $app->alias(TaxoModuleConfigHandler::class, 'overcode.df.taxoModuleConfigHandler');

        // InstanceManager
        $app->singleton(InstanceManager::class, function ($app) {
            return new InstanceManager(
                XeDB::connection(),
                app('overcode.df.cptModuleConfigHandler'),
                app('overcode.df.taxoModuleConfigHandler'),
                app('overcode.df.permission')
            );
        });
        $app->alias(InstanceManager::class, 'overcode.df.instance');

        $app->singleton(CptPermissionHandler::class, function ($app) {
            $cptPermission = new CptPermissionHandler(app('xe.permission'));
            $cptPermission->setPrefix(CptModule::getId());
            return $cptPermission;
        });
        $app->alias(CptPermissionHandler::class, 'overcode.df.permission');

        $app->singleton(CommentManager::class, function ($app){
            return new CommentManager(app('xe.plugin.comment')->getHandler());
        });
        $app->alias(CommentManager::class, 'overcode.df.comment_manager');
    }

    /**
     * 이 메소드는 활성화(activate) 된 플러그인이 부트될 때 항상 실행됩니다.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadCpts();
        $this->CptCategorySettingFromPlugin();
        $this->CptDynamicFieldSettingFromPlugin();

        $this->registerSettingsMenus();
        $this->registerSettingsRoute();
        $this->registerCategoryRoute();

        $this->interceptDynamicField();
    }

    protected function loadCpts()
    {
        $site_key = \XeSite::getCurrentSiteKey();

        $dfService = app('overcode.df.service');
        $comment_manager = app('overcode.df.comment_manager');
        $dfConfigHandler = app('overcode.df.configHandler');

        // 타 플러그인에서 등록한 cpt 의 config 를 불러온다.
        $this->df_config = \XeRegister::get('df_config');

        // DB 에 저장된 cpt 를 불러온다.
        $this->cpts = $dfService->getItemsAll();

        foreach($this->cpts as $cpt){
            //set config for from plugin
            if(isset($cpt->from_plugin))
                $this->CptConfigSettingFromPlugin($dfConfigHandler, $cpt->cpt_id);

            //set comment config
            if(isset($cpt->use_comment) && $cpt->use_comment == "Y"){
                $comment_instance_id = $comment_manager->hasCommentConfig($cpt->cpt_id);
                if($comment_instance_id == null)
                    $comment_instance_id = $comment_manager->createCommentConfig($cpt->cpt_id);


                //set comment dynamic field
                if(is_array($cpt->comment_dynamic_field) && count($cpt->comment_dynamic_field) > 0){
                    \XeRegister::push('df_df', $comment_instance_id, $cpt->comment_dynamic_field);
                }
            }

            //set document dynamic field
            if(is_array($cpt->document_dynamic_field) && count($cpt->document_dynamic_field) > 0){
                \XeRegister::push('df_df', $cpt->cpt_id, $cpt->document_dynamic_field);
            }

            //set admin menus
            $display = isset($cpt->display) ? $cpt->display : true;
            \XeRegister::push('settings/menu', $cpt->menu_path . $cpt->cpt_id, [
                'title' => $cpt->menu_name,
                'description' => $cpt->description,
                'display' => $display,
                'ordering' => $cpt->menu_order
            ]);
            \XeRegister::push('settings/menu', $cpt->menu_path . $cpt->cpt_id . '.articles', [
                'title' => $cpt->menu_name,
                'description' => $cpt->description,
                'display' => $display,
                'ordering' => 100
            ]);
            \XeRegister::push('settings/menu', $cpt->menu_path . $cpt->cpt_id . '.trash', [
                'title' => '휴지통',
                'display' => $display,
                'ordering' => 1000
            ]);

            //set routes
            Route::settings(static::getId(), function () use ($cpt){
//                Route::get(sprintf('/%s', $cpt->cpt_id), [
//                    'as' => sprintf('dyFac.setting.%s', $cpt->cpt_id),
//                    'uses' => 'DynamicFactorySettingController@documentList',
//                    'settings_menu' => sprintf('%s%s.articles', $cpt->menu_path, $cpt->cpt_id)
//                ]);
//                Route::get(sprintf('/%s/create', $cpt->cpt_id), [
//                    'as' => sprintf('dyFac.setting.%s.create', $cpt->cpt_id),
//                    'uses' => 'DynamicFactorySettingController@documentCreate'
//                ]);
                Route::get('/'.$cpt->cpt_id. '/{type?}', [
                    'as' => 'dyFac.setting.'.$cpt->cpt_id,
                    'uses' => 'DynamicFactorySettingController@cptDocument',
                    'settings_menu' => sprintf('%s%s.articles', $cpt->menu_path , $cpt->cpt_id)
                ]);
                Route::get('/trash/' . $cpt->cpt_id, [
                    'as' => 'dyFac.setting.'.$cpt->cpt_id.'.trash',
                    'uses' => 'DynamicFactorySettingController@trashAlias',
                    'settings_menu' => sprintf('%s%s.trash', $cpt->menu_path , $cpt->cpt_id)
                ]);
            },['namespace' => 'Overcode\XePlugin\DynamicFactory\Controllers']);

            //get Taxonomy
            $taxonomies = CptTaxonomy::where('cpt_id',$cpt->cpt_id)->where('site_key',$site_key)->get();
            foreach($taxonomies as $taxonomy){
                \XeRegister::push('settings/menu', $cpt->menu_path . $cpt->cpt_id . '.' . $taxonomy->category_id, [
                    'title' => xe_trans($taxonomy->category->name),
                    'display' => $display,
                    'ordering' => 500 + (int) $taxonomy->category_id
                ]);

                Route::settings(static::getId() . "/" . $cpt->cpt_id, function () use ($cpt, $taxonomy) {
                    Route::get("/taxonomy" . "/" . $taxonomy->category_id, [
                        'as' => 'dyFac.setting.cpt_taxonomy.' . $cpt->cpt_id . $taxonomy->category_id,
                        'uses' => 'DynamicFactorySettingController@cpt_taxonomy',
                        'settings_menu' => $cpt->menu_path . $cpt->cpt_id . '.' . $taxonomy->category_id
                    ]);
                },['namespace' => 'Overcode\XePlugin\DynamicFactory\Controllers']);
            }

        }
    }

    /**
     * 타 플러그인에서 지정한 Config 를 체크하여 없으면 생성해준다.
     */
    protected function CptConfigSettingFromPlugin($dfConfigHandler, $cpt_id)
    {
        $configName = $dfConfigHandler->getConfigName($cpt_id);
        $config = $dfConfigHandler->get($configName);

        // 해당 cpt_id 로 config 를 가져와서 없으면 타 플러그인에서 불러온 config 값으로 생성해준다.
        if ($config === null || !isset($config)) {
            $dfConfigHandler->addConfig($this->df_config[$cpt_id], $configName);
            $dfConfigHandler->addEditor($cpt_id);   //기본 에디터 ckEditor 로 설정
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
            'title' => '사용자 정의 문서',
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
            'ordering' => 2000
        ]);
        \XeRegister::push('settings/menu', 'setting.dynamic_factory.trash', [
            'title' => '휴지통 관리',
            'display' => true,
            'ordering' => 3000
        ]);
    }

    protected function registerSettingsRoute()
    {
        Route::group([
            'prefix' => Plugin::getId(),
            'as' => 'dyFac.',
            'namespace' => 'Overcode\XePlugin\DynamicFactory\Controllers',
            'middleware' => ['web']
        ], function() {
            Route::get('/categories', ['as' => 'categories', 'uses' => 'DynamicFactoryController@getCategories']);
            Route::post('/favorite/{id}', ['as' => 'document.favorite', 'uses' => 'DynamicFactoryController@favorite']);
            Route::get('/docSearch/{keyword?}', ['as' => 'document.search', 'uses' => 'DynamicFactoryController@docSearch']);
        });
        Route::get('/userSearch/{keyword?}', ['as' => 'dyFac.user.search', 'uses' => 'App\Http\Controllers\User\Settings\UserController@search']);

        Route::settings(static::getId() . "/taxonomy", function() {
            Route::group([
                'namespace' => 'Overcode\XePlugin\DynamicFactory\Controllers',
                'as' => 'dyFac.setting.'
            ], function(){
                Route::get('/', [ 'as' => 'category', 'uses' => 'DynamicFactorySettingController@categoryList', 'settings_menu' => 'setting.dynamic_factory.category' ]);
                Route::get('/create/{tax_id?}', [ 'as' => 'create_taxonomy', 'uses' => 'DynamicFactorySettingController@createTaxonomy' ]);
                Route::get('/extra/{category_slug}', [ 'as' => 'taxonomy_extra', 'uses' => 'DynamicFactorySettingController@taxonomyExtra' ]);

                Route::post('/destroy', [ 'as' => 'category.delete', 'uses' => 'DynamicFactorySettingController@categoryDelete' ]);
            });
        });

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

                Route::get('/create', [ 'as' => 'create', 'uses' => 'DynamicFactorySettingController@create' ]);
                Route::post('/store_cpt', ['as' => 'store_cpt', 'uses' => 'DynamicFactorySettingController@storeCpt']);
                Route::get('/edit_editor/{cpt_id}', [ 'as' => 'edit_editor', 'uses' => 'DynamicFactorySettingController@editEditor' ]);

                Route::get('/edit_columns/{cpt_id}', [ 'as' => 'edit_columns', 'uses' => 'DynamicFactorySettingController@editColumns' ]);
                Route::post('/update_columns/{cpt_id}', [ 'as' => 'update_columns', 'uses' => 'DynamicFactorySettingController@updateColumns' ]);

                Route::get('/edit_orders/{cpt_id}', [ 'as' => 'edit_orders', 'uses' => 'DynamicFactorySettingController@editOrders' ]);
                Route::post('/update_orders/{cpt_id}', [ 'as' => 'update_orders', 'uses' => 'DynamicFactorySettingController@updateOrders' ]);

                Route::get('/create_extra/{cpt_id}', [ 'as' => 'create_extra', 'uses' => 'DynamicFactorySettingController@createExtra' ]);

                Route::get('/edit/{cpt_id}', [ 'as' => 'edit', 'uses' => 'DynamicFactorySettingController@edit' ]);
                Route::post('/update/{cpt_id?}', [ 'as' => 'update', 'uses' => 'DynamicFactorySettingController@update' ]);
                Route::post('/destroy/{cpt_id}', [ 'as' => 'destroy', 'uses' => 'DynamicFactorySettingController@destroy' ]);

                Route::post('/store_cpt_tax', ['as' => 'store_cpt_tax', 'uses' => 'DynamicFactorySettingController@storeTaxonomy']);

                Route::post('/store_cpt_document', ['as' => 'store_cpt_document', 'uses' => 'DynamicFactorySettingController@storeCptDocument']);
                Route::post('/update_cpt_document', ['as' => 'update_cpt_document', 'uses' => 'DynamicFactorySettingController@updateCptDocument']);
                Route::post('/trash_cpt_documents', ['as' => 'trash_cpt_documents', 'uses' => 'DynamicFactorySettingController@trashDocuments']);
                Route::post('/remove_cpt_documents', ['as' => 'remove_cpt_documents', 'uses' => 'DynamicFactorySettingController@removeDocuments']);
                Route::post('/restore_cpt_documents', ['as' => 'restore_cpt_documents', 'uses' => 'DynamicFactorySettingController@restoreDocuments']);

                Route::get('/trash/{cpt_id?}', ['as' => 'trash', 'uses' => 'DynamicFactorySettingController@trash', 'settings_menu' => 'setting.dynamic_factory.trash']);
            });
        });

        Route::get('/dynamic_factory/hasSlug/{cpt_id}', ['as' => 'dyFac.hasSlug' , 'uses' => 'Overcode\XePlugin\DynamicFactory\Controllers\DynamicFactoryController@hasSlug']);
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

    protected function reserveSlugUrl()
    {

    }

    protected function interceptDynamicField()
    {
        intercept(
            DynamicFieldHandler::class . '@create',
            'dynamic_factory::createDynamicField',
            function ($func, ConfigEntity $config, ColumnEntity $column = null) {
                $func($config, $column);

                // remove prefix name of group
                $cptId = str_replace('documents_', '', $config->get('group'));

                /** @var Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryConfigHandler $configHandler */
                app('overcode.df.configHandler')->setCurrentSortFormColumns($cptId);
            }
        );

        intercept(
            DynamicFieldHandler::class . '@drop',
            'dynamic_factory::dropDynamicField',
            function ($func, ConfigEntity $config, ColumnEntity $column = null) {
                $func($config, $column);

                // remove prefix name of group
                $cptId = str_replace('documents_', '', $config->get('group'));

                /** @var Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryConfigHandler $configHandler */
                app('overcode.df.configHandler')->setCurrentSortFormColumns($cptId);
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
        /** @var DynamicFactoryConfigHandler $configHandler */
        $configHandler = app('overcode.df.configHandler');
        $configHandler->storeDfConfig();

        $cptModuleConfigHandler = app('overcode.df.cptModuleConfigHandler');
        $cptModuleConfigHandler->getDefault();

        $taxoModuleConfigHandler = app('overcode.df.taxoModuleConfigHandler');
        $taxoModuleConfigHandler->getDefault();

        // create default permission
        $permission = new CptPermissionHandler(app('xe.permission'));
        $permission->addGlobal();

        // lang put
        $trans = app('xe.translator');
        $trans->putFromLangDataSource('dyFac', base_path('plugins/dynamic_factory/langs/lang.php'));
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
        if ($migration->checkUpdated() === false) {
            $migration->update();
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
        if ($migration->checkUpdated() === false) {
            return false;
        }

        return true;
    }

    /**
     * 플러그인을 설치해제한다. 플러그인 디렉토리가 XpressEngine에서 삭제되기 전에 실행될 코드를 여기에 추가한다.
     *
     * @return void
     */
    public function uninstall()
    {
        // 플러그인 삭제시 테이블 같이 삭제되게 하려면 아래 주석 해제
        //$migration = new Migrations();
        //$migration->dropTables();
    }
}
