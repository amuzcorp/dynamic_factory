<?php

namespace Overcode\XePlugin\DynamicFactory\Components\Modules\Cpt;

use Overcode\XePlugin\DynamicFactory\Models\DfSlug;
use Route;
use XeSkin;
use View;
use Xpressengine\Menu\AbstractModule;

class CptModule extends AbstractModule
{
    const THUMBNAIL_TYPE = 'spill';

    /**
     * boot
     *
     * @return void
     */
    public static function boot()
    {
        self::registerSettingsRoute();
        self::registerInstanceRoute();
    }

    /**
     * Register Plugin Manage Route
     *
     * @return void
     */
    protected static function registerSettingsRoute()
    {
        Route::settings(self::getId(), function () {
            // global
            Route::get(
                '/global/permission',
                ['as' => 'settings.cpt.cpt.global.permission', 'uses' => 'CptModuleSettingController@editGlobalPermission']
            );
            Route::post(
                '/global/permission/update',
                ['as' => 'settings.cpt.cpt.global.permission.update', 'uses' => 'CptModuleSettingController@updateGlobalPermission']
            );

            // module
            Route::get('config/{instanceId}', ['as' => 'settings.cpt.cpt.config', 'uses' => 'CptModuleSettingController@editConfig']);
            Route::post(
                'config/update/{instanceId}',
                ['as' => 'settings.cpt.cpt.config.update', 'uses' => 'CptModuleSettingController@updateConfig']
            );
            Route::get('permission/{instanceId}', ['as' => 'settings.cpt.cpt.permission', 'uses' => 'CptModuleSettingController@editPermission']);
            Route::post(
                'permission/update/{instanceId}',
                ['as' => 'settings.cpt.cpt.permission.update', 'uses' => 'CptModuleSettingController@updatePermission']
            );
            Route::get('skin/edit/{instanceId}', ['as' => 'settings.cpt.cpt.skin', 'uses' => 'CptModuleSettingController@editSkin']);
        }, ['namespace' => 'Overcode\XePlugin\DynamicFactory\Controllers']);
    }

    /**
     * Register Plugin Instance Route
     *
     * @return void
     */
    protected static function registerInstanceRoute()
    {
        Route::instance(self::getId(), function () {
            Route::get('/', ['as' => 'index', 'uses' => 'CptModuleController@index']);
            Route::get('/show/{id}', ['as' => 'show', 'uses' => 'CptModuleController@showByItemId']);

            Route::get('/create', ['as' => 'create', 'uses' => 'CptModuleController@create']);
            Route::post('/store', ['as' => 'store', 'uses' => 'CptModuleController@store']);

            Route::get('/edit/{id}', ['as' => 'edit', 'uses' => 'CptModuleController@edit']);
            Route::post('/update', ['as' => 'update', 'uses' => 'CptModuleController@update']);

            Route::delete('/destroy/{id}', ['as' => 'destroy', 'uses' => 'CptModuleController@destroy']);

            Route::post('/favorite/{id}', ['as' => 'favorite', 'uses' => 'CptModuleController@favorite']);

            Route::get('/{slug}', ['as' => 'slug', 'uses' => 'CptModuleController@slug']);
        }, ['namespace' => 'Overcode\XePlugin\DynamicFactory\Controllers']);

        DfSlug::setReserved([
            'index', 'create', 'edit', 'destroy', 'show', 'identify', 'revision', 'store', 'preview', 'temporary',
            'trash', 'certify', 'update', 'vote', 'manageMenus', 'comment', 'file', 'suggestion', 'slug', 'hasSlug',
            'favorite'
        ]);
    }

    /**
     * Return Create Form View
     * @return mixed
     */
    public function createMenuForm()
    {
        $skins = XeSkin::getList('module/cpt@cpt');

        $dfService = app('overcode.df.service');
        $cpts = $dfService->getItemsAll();

        return View::make('dynamic_factory::components/Modules/Cpt/views/create', [
            'skins' => $skins,
            'cpts' => $cpts
        ])->render();
    }

    /**
     * Process to Store
     *
     * @param string $instanceId to store instance id
     * @param array $menuTypeParams for menu type store param array
     * @param array $itemParams except menu type param array
     *
     * @return mixed
     * @internal param $inputs
     *
     */
    public function storeMenu($instanceId, $menuTypeParams, $itemParams)
    {

        $input = $menuTypeParams;
        $input['instanceId'] = $instanceId;

        app('overcode.df.instance')->createCpt($input);
    }

    /**
     * Return Edit Form View
     *
     * @param string $instanceId to edit instance id
     *
     * @return mixed
     */
    public function editMenuForm($instanceId)
    {
        $skins = XeSkin::getList(self::getId());

        $dfService = app('overcode.df.service');
        $cpts = $dfService->getItemsAll();

        return View::make('dynamic_factory::components/Modules/Cpt/views/edit', [
            'instanceId' => $instanceId,
            'config' => app('overcode.df.cptModuleConfigHandler')->get($instanceId),
            'skins' => $skins,
            'cpts' => $cpts
        ])->render();
    }

    /**
     * Process to Update
     *
     * @param string $instanceId to update instance id
     * @param array $menuTypeParams for menu type update param array
     * @param array $itemParams except menu type param array
     *
     * @return mixed
     * @internal param $inputs
     *
     */
    public function updateMenu($instanceId, $menuTypeParams, $itemParams)
    {
        $menuTypeParams['instanceId'] = $instanceId;
        app('overcode.df.instance')->updateCptConfig($menuTypeParams);
    }

    /**
     * displayed message when menu is deleted.
     *
     * @param string $instanceId to summary before deletion instance id
     *
     * @return string
     */
    public function summary($instanceId)
    {
        // TODO: Implement summary() method.
    }

    /**
     * Process to delete
     *
     * @param string $instanceId to delete instance id
     *
     * @return mixed
     */
    public function deleteMenu($instanceId)
    {
        // TODO: Implement deleteMenu() method.
    }

    /**
     * Return URL about module's detail setting
     * getInstanceSettingURI
     *
     * @param string $instanceId instance id
     * @return mixed
     */
    public static function getInstanceSettingURI($instanceId)
    {
//        return route('settings.cpt.cpt.config', $instanceId);
        return route('settings.cpt.cpt.skin', $instanceId);
    }

    /**
     * Get menu type's item object
     *
     * @param string $id item id of menu type
     * @return mixed
     */
    public function getTypeItem($id)
    {
        // TODO: Implement getTypeItem() method.
    }
}
