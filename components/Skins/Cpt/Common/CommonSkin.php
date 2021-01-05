<?php

namespace Overcode\XePlugin\DynamicFactory\Components\Skins\Cpt\Common;

use Overcode\XePlugin\DynamicFactory\GenericCptSkin;
use View;
use Gate;
use XeFrontend;
use XeRegister;
use XePresenter;
Use XeSkin;
use Xpressengine\Presenter\Presenter;

class CommonSkin extends GenericCptSkin
{
    protected static $path = 'dynamic_factory/components/Skins/Cpt/Common';
    /**
     * render
     *
     * @return \Illuminate\Contracts\Support\Renderable|string
     */
    public function render()
    {
        $this->setSkinConfig();
        $this->setPaginationPresenter();
        $this->setTerms();

        // set skin path
        $this->data['_skinPath'] = static::$path;
        $this->data['isManager'] = $this->isManager();

        /**
         * If view file is not exists to extended skin component then change view path to CommonSkin's path.
         * CommonSkin extends by other Skins. Extended Skin can make own blade files.
         * If not make blade file then use to CommonSkin's blade files.
         */
        if (View::exists(sprintf('%s/views/%s', static::$path, $this->view)) == false) {
            static::$path = self::$path;
        }

        $contentView = parent::render();

        /**
         * If render type is not for Presenter::RENDER_CONTENT
         * then use CommonSkin's '_frame.blade.php' for layout.
         * '_frame.blade.php' has assets load script like js, css.
         */
        if (XePresenter::getRenderType() == Presenter::RENDER_CONTENT) {
            $view = $contentView;
        } else {
            // wrapped by _frame.blade.php
            if (View::exists(sprintf('%s/views/_frame', static::$path)) === false) {
                static::$path = self::$path;
            }
            $view = View::make(sprintf('%s/views/_frame', static::$path), $this->data);
            $view->content = $contentView;
        }

        return $view;
    }

    /**
     * set skin config to data
     *
     * @return void
     */
    protected function setSkinConfig()
    {
        $this->data['skinConfig'] = $this->config;
    }

    /**
     * set pagination presenter
     *
     * @return void
     * @see views/defaultSkin/index.blade.php
     */
    protected function setPaginationPresenter()
    {
        if (isset($this->data['paginate'])) {
            $this->data['paginate']->setPath($this->data['cptUrlHandler']->get('index'));
        }
    }

    /**
     * set terms for search select box list
     *
     * @return array
     */
    protected function setTerms()
    {
        $this->data['terms'] = [
            ['value' => '1week', 'text' => 'board::1week'],
            ['value' => '2week', 'text' => 'board::2week'],
            ['value' => '1month', 'text' => 'board::1month'],
            ['value' => '3month', 'text' => 'board::3month'],
            ['value' => '6month', 'text' => 'board::6month'],
            ['value' => '1year', 'text' => 'board::1year'],
        ];
    }

    /**
     * is manager
     *
     * @return bool
     */
    protected function isManager()
    {
        return false;
    }
}
