<?php

namespace Overcode\XePlugin\DynamicFactory\Components\Modules\Cpt;

use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use Overcode\XePlugin\DynamicFactory\Models\DfSlug;
use Route;
use XeSkin;
use View;
use Xpressengine\Plugins\Comment\Handler as CommentHandler;
use Xpressengine\Menu\AbstractModule;
use Xpressengine\Plugins\Comment\Models\Comment;

class CptModule extends AbstractModule
{
    const THUMBNAIL_TYPE = 'spill';
    const ROUTE_PREFIX =  'settings.cpt';

    /**
     * boot
     *
     * @return void
     */
    public static function boot()
    {
        self::registerSettingsRoute(self::getId(),self::ROUTE_PREFIX);
        self::registerInstanceRoute();
        self::registerCommentCountIntercept();
    }

    /**
     * Register Plugin Manage Route
     *
     * @return void
     */
    protected static function registerSettingsRoute($target_id,$prefix)
    {
        Route::settings($target_id, function () use ($prefix){
            // global
            Route::get(
                '/global/config',
                ['as' => $prefix . '.global.config', 'uses' => 'CptModuleSettingController@editGlobalConfig']
            );
            Route::post(
                '/global/config/update',
                ['as' => $prefix . '.global.config.update', 'uses' => 'CptModuleSettingController@updateGlobalConfig']
            );
            Route::get(
                '/global/permission',
                ['as' => $prefix . '.global.permission', 'uses' => 'CptModuleSettingController@editGlobalPermission']
            );
            Route::post(
                '/global/permission/update',
                ['as' => $prefix . '.global.permission.update', 'uses' => 'CptModuleSettingController@updateGlobalPermission']
            );

            // module
            Route::get('config/{instanceId}', ['as' => $prefix . '.config', 'uses' => 'CptModuleSettingController@editConfig']);
            Route::post(
                'config/update/{instanceId}',
                ['as' => $prefix . '.config.update', 'uses' => 'CptModuleSettingController@updateConfig']
            );
            Route::get('permission/{instanceId}', ['as' => $prefix . '.permission', 'uses' => 'CptModuleSettingController@editPermission']);
            Route::post(
                'permission/update/{instanceId}',
                ['as' => $prefix . '.permission.update', 'uses' => 'CptModuleSettingController@updatePermission']
            );
            Route::get('skin/edit/{instanceId}', ['as' => $prefix . '.skin', 'uses' => 'CptModuleSettingController@editSkin']);
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
            Route::match(['get','post'],'/', ['as' => 'index', 'uses' => 'CptModuleController@index']);
            Route::match(['get','post'],'/show/{id}', ['as' => 'show', 'uses' => 'CptModuleController@showByItemId']);
            Route::match(['get','post'],'/create', ['as' => 'create', 'uses' => 'CptModuleController@create']);
            Route::match(['get','post'],'/edit/{id}', ['as' => 'edit', 'uses' => 'CptModuleController@edit']);
            Route::match(['get','post'],'/favorite/{id}', ['as' => 'favorite', 'uses' => 'CptModuleController@favorite']);

            Route::post('/store', ['as' => 'store', 'uses' => 'CptModuleController@store']);
            Route::post('/update', ['as' => 'update', 'uses' => 'CptModuleController@update']);
            Route::delete('/destroy/{id}', ['as' => 'destroy', 'uses' => 'CptModuleController@destroy']);
            Route::post('/vote/{option}/{id}', ['as' => 'vote', 'uses' => 'CptModuleController@vote']);
            Route::get('/vote/show/{id}', ['as' => 'showVote', 'uses' => 'CptModuleController@showVote']);
            Route::get('/vote/users/{option}/{id}', ['as' => 'votedUsers', 'uses' => 'CptModuleController@votedUsers']);
            Route::get('/vote/modal/{option}/{id}', ['as' => 'votedModal', 'uses' => 'CptModuleController@votedModal']);
            Route::get('/vote/userList/{option}/{id}', ['as' => 'votedUserList', 'uses' => 'CptModuleController@votedUserList']);

            Route::match(['get','post'],'/{slug}', ['as' => 'slug', 'uses' => 'CptModuleController@slug']);
        }, ['namespace' => 'Overcode\XePlugin\DynamicFactory\Controllers']);

        DfSlug::setReserved([
            //아래 추가
            'index', 'create', 'edit', 'destroy', 'trash','show', 'identify', 'revision', 'store', 'preview', 'temporary',
            'trash', 'certify', 'update', 'vote', 'manageMenus', 'comment', 'file', 'suggestion', 'slug', 'hasSlug',
            'favorite'
        ]);
    }

    /**
     * register intercept for comment count
     *
     * @return void
     */
    public static function registerCommentCountIntercept()
    {
        intercept(
            sprintf('%s@create', CommentHandler::class),
            static::class.'-comment-create',
            function ($func, array $inputs, $user = null) {
                $comment = $func($inputs, $user);

                self::setCptCommentCount($comment->target->target_id);

                return $comment;
            }
        );

        intercept(
            sprintf('%s@trash', CommentHandler::class),
            static::class.'-comment-trash',
            function ($func, Comment $comment) {
                $result = $func($comment);

                self::setCptCommentCount($comment->target->target_id);

                return $result;
            }
        );

        intercept(
            sprintf('%s@remove', CommentHandler::class),
            static::class.'-comment-remove',
            function ($func, Comment $comment) {
                $result = $func($comment);

                self::setCptCommentCount($comment->target->target_id);

                return $result;
            }
        );

        intercept(
            sprintf('%s@restore', CommentHandler::class),
            static::class.'-comment-restore',
            function ($func, Comment $comment) {
                $result = $func($comment);

                self::setCptCommentCount($comment->target->target_id);

                return $result;
            }
        );

        intercept(
            sprintf('%s@approve', CommentHandler::class),
            static::class.'-comment-approve',
            function ($func, Comment $comment) {
                $result = $func($comment);

                self::setCptCommentCount($comment->target->target_id);

                return $result;
            }
        );

        intercept(
            sprintf('%s@reject', CommentHandler::class),
            static::class.'-comment-reject',
            function ($func, Comment $comment) {
                $result = $func($comment);

                self::setCptCommentCount($comment->target->target_id);

                return $result;
            }
        );
    }

    protected static function setCptCommentCount($id)
    {
        if ($document = CptDocument::find($id)) {
            if ($document == null) {
                return;
            }
//            if ($document->type != static::getId()) {
//                return;
//            }

            $commentCount = $document->comments()
                ->where('approved', Comment::APPROVED_APPROVED)
                ->where('status', '<>', Comment::STATUS_TRASH)
                ->where('display', '<>', Comment::DISPLAY_HIDDEN)
                ->count();

            $document->comment_count = $commentCount;
            $document->save();
        }
    }

    /**
     * Return Create Form View
     * @return mixed
     */
    public function createMenuForm()
    {
        $skins = XeSkin::getList(self::getId());

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
        return route(self::ROUTE_PREFIX . '.skin', $instanceId);
    }

    /**
     * Get menu type's item object
     *
     * @param string $id item id of menu type
     * @return mixed
     */
    public function getTypeItem($id)
    {
        static $items = [];

        if (!isset($items[$id])) {
            $items[$id] = \Overcode\XePlugin\DynamicFactory\Models\CptDocument::find($id);
        }

        return $items[$id];
    }
}
