<?php
namespace Overcode\XePlugin\DynamicFactory\Components\DynamicFields\SuperRelate\Skins\Common;

use Overcode\XePlugin\DynamicFactory\Components\DynamicFields\SuperRelate\SuperRelateField;
use Overcode\XePlugin\DynamicFactory\Models\RelateDocument;
use Overcode\XePlugin\DynamicFactory\Models\SuperRelate;
use Overcode\XePlugin\DynamicFactory\Plugin;
use Xpressengine\DynamicField\AbstractSkin;
use XeFrontend;

class CommonSkin extends AbstractSkin
{
    protected static $loaded = false;

    /**
     * get name of skin
     *
     * @return string
     */
    public function name()
    {
        return 'SuperRelate Default Skin';
    }

    /**
     * get view file directory path
     *
     * @return string
     */
    public function getPath()
    {
        return 'dynamic_factory::components/DynamicFields/SuperRelate/Skins/Common/views';
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

    protected function appendScript()
    {
        XeFrontend::css([
            Plugin::asset('/components/DynamicFields/SuperRelate/Skins/Common/assets/style.css'),
        ])->load();
        XeFrontend::js([
            Plugin::asset('/components/DynamicFields/SuperRelate/Skins/Common/assets/DocList.js'),
            Plugin::asset('/components/DynamicFields/SuperRelate/Skins/Common/assets/UserList.js'),
        ])->appendTo('head')->load();
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
        if (self::$loaded === false) {
            $this->appendScript();
            self::$loaded = true;
        }

        return parent::create($args);
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
        if (self::$loaded === false) {
            $this->appendScript();
            self::$loaded = true;
        }

        $items = $this->getRelateItems($args);

        list($data, $key) = $this->filter($args);
//        dd(list($data, $key) = $this->filter($args));
//        dd(array_merge($data, $this->mergeData), $items);
        $viewFactory = $this->handler->getViewFactory();
        return $viewFactory->make($this->getViewPath('edit'), [
            'args' => $args,
            'config' => $this->config,
            'data' => array_merge($data, $this->mergeData),
            'key' => $key,
            'items' => $items,
        ])->render();
    }

    protected function getRelateItems($args) {
        $tableName = SuperRelateField::TABLE_NAME;

        $target_is_user = ($this->config->get('r_instance_id') == 'user');

        if(array_get($args, 'instance_id') == null){
            $s_group = 'user';
            if(array_get($args, 'group_id') != null) {
                $s_group = array_get($args, 'group_id');
            }
        }else{
            $s_group = sprintf('documents_%s', array_get($args, 'instance_id'));
        }

        $params = [
            'field_id' => $this->config->get('id'),
            's_id' => $args['id'],
            's_group' => $s_group
        ];

        if($target_is_user) {
            $items = SuperRelate::Select('user.id as r_id', 'user.display_name as r_name')->where($params)
                ->leftJoin('user', sprintf('%s.t_id',$tableName), '=', 'user.id')->get();
        }else {
            $items = SuperRelate::Select('documents.id as r_id', 'documents.title as r_name')->where($params)
                ->leftJoin('documents', sprintf('%s.t_id',$tableName), '=', 'documents.id')->get();
        }

        return $items;
    }
}
