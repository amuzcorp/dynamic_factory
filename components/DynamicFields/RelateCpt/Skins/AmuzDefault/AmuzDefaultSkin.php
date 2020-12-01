<?php
namespace Overcode\XePlugin\DynamicFactory\Components\DynamicFields\RelateCpt\Skins\AmuzDefault;

use Xpressengine\DynamicField\AbstractSkin;
use Auth;

class AmuzDefaultSkin extends AbstractSkin
{

    /**
     * get name of skin
     *
     * @return string
     */
    public function name()
    {
        return 'RelateCpt Default Skin';
    }

    /**
     * get view file directory path
     *
     * @return string
     */
    public function getPath()
    {
        return 'dynamic_factory::components/DynamicFields/RelateCpt/Skins/AmuzDefault/views';
    }

    /**
     * 다이나믹필스 생성할 때 스킨 설정에 적용될 rule 반환
     *
     * @return array
     */
    public function getSettingsRules()
    {
        return [];
    }

    /**
     * 등록 form 에 추가될 html 코드 반환
     * return html tag string
     *
     * @param array $args arguments
     * @return \Illuminate\View\View
     */
    public function create(array $args)
    {
        $viewFactory = $this->handler->getViewFactory();

        list($data, $key) = $this->filter($args);

        $cpt_ids = $this->config->get('cpt_ids');

        $cptDocService = app('overcode.doc.service');

        $items = $cptDocService->getItemsByCptIds($cpt_ids, Auth::user(), $this->config->get('author'));

        return $viewFactory->make($this->getViewPath('create'), [
            'items' => $items,
            'args' => $args,
            'config' => $this->config,
            'data' => array_merge($data, $this->mergeData),
            'key' => $key,
        ])->render();
    }
}
