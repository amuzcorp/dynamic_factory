<?php
namespace Overcode\XePlugin\DynamicFactory\Components\DynamicFields\RelateCpt\Skins\AmuzDefault;

use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
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
        list($data, $key) = $this->filter($args);

        $cpt_ids = $this->config->get('cpt_ids');

        $cptDocService = app('overcode.doc.service');

        $items = $cptDocService->getItemsByCptIds($cpt_ids, Auth::user(), $this->config->get('author'));

        $viewFactory = $this->handler->getViewFactory();
        return $viewFactory->make($this->getViewPath('create'), [
            'items' => $items,
            'args' => $args,
            'config' => $this->config,
            'data' => array_merge($data, $this->mergeData),
            'key' => $key,
        ])->render();
    }

    /**
     * 수정 form 에 추가될 html 코드 반환
     * return html tag string
     *
     * @param array $args arguments
     * @return \Illuminate\View\View
     */
    public function edit(array $args)
    {
        list($data, $key) = $this->filter($args);

        $cpt_ids = $this->config->get('cpt_ids');

        $cptDocService = app('overcode.doc.service');

        $items = $cptDocService->getItemsByCptIds($cpt_ids, Auth::user(), $this->config->get('author'));

        $values = [];

        if(isset($args[$key['ids']])){
            $values = json_decode($args[$key['ids']]);
        }
        if($values === null){
            $values = [];
        }

        $viewFactory = $this->handler->getViewFactory();
        return $viewFactory->make($this->getViewPath('edit'), [
            'items' => $items,
            'args' => $args,
            'config' => $this->config,
            'data' => array_merge($data, $this->mergeData),
            'key' => $key,
            'values' => $values
        ])->render();
    }

    /**
     * 조회할 때 사용 될 html 코드 반환
     * return html tag string
     *
     * @param array $args arguments
     * @return \Illuminate\View\View
     */
    public function show(array $args)
    {
        list($data, $key) = $this->filter($args);

        $cpt_ids = $this->config->get('cpt_ids');
        $ids = json_decode($data['ids']);

        $items = []; // CptDocument 가 들어감
        foreach($cpt_ids as $cpt_id) {
            foreach ((array)$ids as $id) {
                $item = CptDocument::division($cpt_id)->find($id);
                if ($item !== null) {
                    $items[] = $item;
                }
            }
        }

        $viewFactory = $this->handler->getViewFactory();
        return $viewFactory->make($this->getViewPath('show'), [
            'args' => $args,
            'config' => $this->config,
            'data' => array_merge($data, $this->mergeData),
            'key' => $key,
            'items' => $items
        ])->render();
    }
}
