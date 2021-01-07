<?php

namespace Overcode\XePlugin\DynamicFactory\Components\Widgets\DocumentList;

use Xpressengine\Category\Models\CategoryItem;
use Xpressengine\Widget\AbstractWidget;

class DocumentListWidget extends AbstractWidget
{
    protected static $path = 'dynamic_factory/components/Widgets/DocumentList';

    /**
     * Get the evaluated contents of the object.
     *
     * @return string
     */
    public function render()
    {
        $widgetConfig = $this->setting();

        return $this->renderSkin([
            'widgetConfig' => $widgetConfig
        ]);
    }

    /**
     * 위젯 설정 페이지에 출력할 폼을 출력한다.
     *
     * @param array $args 설정값
     *
     * @return string
     */
    public function renderSetting(array $args = [])
    {
        return $view = View::make(sprintf('%s/views/setting', static::$path), [
            'documentList' => $this->getCptList()
        ]);
    }

    /**
     * get Cpt List
     *
     * @return array
     */
    protected function getCptList()
    {
        return [];
    }

    /**
     * get CategoryList
     *
     * @param CategoryItem $categoryItem
     * @return array
     */
    private function getCategoryList(CategoryItem $categoryItem)
    {
        return [];
    }
}
