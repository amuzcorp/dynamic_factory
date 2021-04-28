<?php
namespace Overcode\XePlugin\DynamicFactory\Components\DynamicFields\RelateDocument\Skins\Common;

use Overcode\XePlugin\DynamicFactory\Models\RelateDocument;
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
        return 'RelateDocument Default Skin';
    }

    /**
     * get view file directory path
     *
     * @return string
     */
    public function getPath()
    {
        return 'dynamic_factory::components/DynamicFields/RelateDocument/Skins/Common/views';
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
//        XeFrontend::css([
//            Plugin::asset('/assets/multiSelect2/multiSelect2.css'),
//            '/assets/core/permission/permission.css'
//        ])->load();
//        XeFrontend::js(Plugin::asset('/assets/multiSelect2/multiSelect2.min.js'))->appendTo('head')->load();
        XeFrontend::css([
            '/assets/core/permission/permission.css',
        ])->load();
        XeFrontend::js([
            Plugin::asset('/assets/DocList.js'),
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
        $items = RelateDocument::Select('documents.id as doc_id', 'documents.title')->where($params)->leftJoin('documents', 'field_dynamic_factory_relate_document.r_id', '=', 'documents.id')->get();
        return $items;
    }
}
