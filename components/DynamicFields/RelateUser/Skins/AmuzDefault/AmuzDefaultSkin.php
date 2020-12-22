<?php

namespace Overcode\XePlugin\DynamicFactory\Components\DynamicFields\RelateUser\Skins\AmuzDefault;

use Xpressengine\DynamicField\AbstractSkin;

class AmuzDefaultSkin extends AbstractSkin
{

    /**
     * get name of skin
     *
     * @return string
     */
    public function name()
    {
        return 'RelateUser Default Skin';
    }

    /**
     * get view file directory path
     *
     * @return string
     */
    public function getPath()
    {
        return 'dynamic_factory::components/DynamicFields/RelateUser/Skins/AmuzDefault/views';
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

        $users = app('xe.user')->users()->get();

        return $viewFactory->make($this->getViewPath('create'), [
            'args' => $args,
            'config' => $this->config,
            'data' => array_merge($data, $this->mergeData),
            'key' => $key,
            'users' => $users
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

        $users = app('xe.user')->users()->get();

        $values = [];

        if(isset($args[$key['ids']])){
            $values = json_decode($args[$key['ids']]);
        }
        if($values === null){
            $values = [];
        }

        $viewFactory = $this->handler->getViewFactory();
        return $viewFactory->make($this->getViewPath('edit'), [
            'users' => $users,
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

        $ids = json_decode($data['ids']);

        $items = app('xe.user')->users()->whereIn('id', $ids)->get();

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
