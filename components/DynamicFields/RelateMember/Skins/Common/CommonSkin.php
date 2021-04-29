<?php
namespace Overcode\XePlugin\DynamicFactory\Components\DynamicFields\RelateMember\Skins\Common;

use Overcode\XePlugin\DynamicFactory\Models\RelateMember;
use Overcode\XePlugin\DynamicFactory\Plugin;
use XeFrontend;
use Xpressengine\DynamicField\AbstractSkin;

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
        return 'RelateMember Default Skin';
    }

    /**
     * get view file directory path
     *
     * @return string
     */
    public function getPath()
    {
        return 'dynamic_factory::components/DynamicFields/RelateMember/Skins/Common/views';
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
            Plugin::asset('/components/DynamicFields/RelateMember/Skins/Common/assets/style.css')
        ])->load();
        XeFrontend::js([
            Plugin::asset('/components/DynamicFields/RelateMember/Skins/Common/assets/MemberList.js')
        ])->appendTo('head')->load();
    }

    public function create(array $args)
    {
        if (self::$loaded === false) {
            $this->appendScript();
            self::$loaded = true;
        }

        return parent::create($args);
    }

    public function edit(array $args)
    {
        if (self::$loaded === false) {
            $this->appendScript();
            self::$loaded = true;
        }

        $items = $this->getRelateItems($args);

        list($data, $key) = $this->filter($args);

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
        $params = [
            'field_id' => $this->config->get('id'),
            'target_id' => $args['id'],
            'group' => sprintf('documents_%s', $args['instance_id'])
        ];
        $items = RelateMember::Select('user.id as mem_id', 'user.display_name as mem_name')->where($params)
            ->leftJoin('user', 'field_dynamic_factory_relate_member.user_id', '=', 'user.id')->get();
        return $items;
    }
}
