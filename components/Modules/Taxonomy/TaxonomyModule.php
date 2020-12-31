<?php

namespace Overcode\XePlugin\DynamicFactory\Components\Modules\Taxonomy;

use Route;
use XeSkin;
use View;
use Xpressengine\Menu\AbstractModule;

class TaxonomyModule extends AbstractModule
{
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
            // module
            Route::get('config/{instanceId}', ['as' => 'settings.taxo.taxo.config', 'uses' => 'TaxoModuleSettingController@editConfig']);
            Route::post(
                'config/update/{instanceId}',
                ['as' => 'settings.taxo.taxo.config.update', 'uses' => 'TaxoModuleSettingController@updateConfig']
            );
            Route::get('skin/edit/{instanceId}', ['as' => 'settings.taxo.taxo.skin', 'uses' => 'TaxoModuleSettingController@editSkin']);
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
            Route::get('/', ['as' => 'index', 'uses' => 'TaxoModuleController@index']);
            Route::get('/show/{id}', ['as' => 'show', 'uses' => 'TaxoModuleController@showByItemId']);

            Route::get('/{slug}', ['as' => 'slug', 'uses' => 'TaxoModuleController@slug']);
        }, ['namespace' => 'Overcode\XePlugin\DynamicFactory\Controllers']);

        /*DfSlug::setReserved([
            'index', 'create', 'edit', 'destroy', 'show', 'identify', 'revision', 'store', 'preview', 'temporary',
            'trash', 'certify', 'update', 'vote', 'manageMenus', 'comment', 'file', 'suggestion', 'slug', 'hasSlug',
            'favorite'
        ]);*/
    }

    /**
     * Return Create Form View
     * @return mixed
     */
    public function createMenuForm()
    {
        $skins = XeSkin::getList('module/cpt@cpt');

        // 카테고리 리스트
        $taxonomyHandler = app('overcode.df.taxonomyHandler');
        $categoryExtras = $taxonomyHandler->getCategoryExtras();

        return View::make('dynamic_factory::components/Modules/Taxonomy/views/create', [
            'skins' => $skins,
            'categoryExtras' => $categoryExtras
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
        // TODO: Implement storeMenu() method.
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
        // TODO: Implement editMenuForm() method.
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
        // TODO: Implement updateMenu() method.
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
        return route('settings.taxo.taxo.skin', $instanceId);
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
