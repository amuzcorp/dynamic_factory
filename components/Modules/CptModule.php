<?php

namespace Overcode\XePlugin\DynamicFactory\Components\Modules;

use Route;
use XeSkin;
use View;
use Xpressengine\Menu\AbstractModule;

class CptModule extends AbstractModule
{
    /**
     * boot
     *
     * @return void
     */
    public static function boot()
    {
        self::registerArchiveRoute();
        self::registerInstanceRoute();
    }

    /**
     * register plugin archive route
     *
     * @return void
     */
    protected static function registerArchiveRoute()
    {
        // set routing
        config(['xe.routing' => array_merge(
            config('xe.routing'),
            ['cpt_archives' => 'cpts']
        )]);

        Route::group([
            'prefix' => 'cpts',
            'namespace' => 'Overcode\XePlugin\DynamicFactory\Controllers'
        ], function () {
            Route::get('/{slug}', ['as' => 'cpts', 'ArchivesController@index']);
        });
    }

    /**
     * Register Plugin Instance Route
     *
     * @return void
     */
    protected static function registerInstanceRoute()
    {

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

        return View::make('dynamic_factory::components/Modules/views/create', [
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

        return View::make('dynamic_factory::components/Modules/views/edit', [
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
