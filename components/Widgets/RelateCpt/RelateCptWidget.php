<?php

namespace Overcode\XePlugin\DynamicFactory\Components\Widgets\RelateCpt;

use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use View;
use Xpressengine\Widget\AbstractWidget;

class RelateCptWidget extends AbstractWidget
{
    protected static $path = 'dynamic_factory/components/Widgets/RelateCpt';

    /**
     * Get the evaluated contents of the object.
     *
     * @return string
     */
    public function render()
    {
        $widgetConfig = $this->setting();

        $title = $widgetConfig['@attributes']['title'];

        $relate_cpts = $widgetConfig['relate_cpts'];

        $cpts = CptDocument::division('program')->whereIn('id', $relate_cpts['item'])->get();

        return $this->renderSkin([
            'widgetConfig' => $widgetConfig,
            'title' => $title,
            'cpts' => $cpts
        ]);

    }

    public function renderSetting(array $args = [])
    {
        $view = View::make(sprintf('%s/views/setting', static::$path), [
            'args' => $args
        ]);

        return $view;
    }
}
