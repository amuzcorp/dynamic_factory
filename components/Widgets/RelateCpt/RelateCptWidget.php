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

        $cpt_id = $widgetConfig['cpt_id'];
        $field_id = $widgetConfig['field_id'];
        $document_id = $widgetConfig['document_id'];

        $handler = app('overcode.df.handler');
        $document_ids = $handler->getRelateCpts($field_id, 'documents_'.$cpt_id, $document_id);

        $cpts = CptDocument::division($cpt_id)->whereIn('id', $document_ids)->get();

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
