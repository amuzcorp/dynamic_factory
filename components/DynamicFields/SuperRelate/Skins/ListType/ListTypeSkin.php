<?php
namespace Overcode\XePlugin\DynamicFactory\Components\DynamicFields\SuperRelate\Skins\ListType;

use Overcode\XePlugin\DynamicFactory\Components\DynamicFields\SuperRelate\SuperRelateField;
use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use Overcode\XePlugin\DynamicFactory\Models\RelateDocument;
use Overcode\XePlugin\DynamicFactory\Models\SuperRelate;
use Overcode\XePlugin\DynamicFactory\Plugin;
use Xpressengine\DynamicField\AbstractSkin;
use XeFrontend;

class ListTypeSkin extends AbstractSkin
{
    protected static $loaded = false;

    /**
     * get name of skin
     *
     * @return string
     */
    public function name()
    {
        return 'SuperRelate 복수 선택 (리스트형)';
    }

    /**
     * get view file directory path
     *
     * @return string
     */
    public function getPath()
    {
        return 'dynamic_factory::components/DynamicFields/SuperRelate/Skins/ListType/views';
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
            Plugin::asset('/components/DynamicFields/SuperRelate/Skins/ListType/assets/style.css'),
        ])->load();
        XeFrontend::js([
            Plugin::asset('/components/DynamicFields/SuperRelate/Skins/ListType/assets/DocList.js'),
            Plugin::asset('/components/DynamicFields/SuperRelate/Skins/ListType/assets/UserList.js'),
            Plugin::asset('/components/DynamicFields/SuperRelate/Skins/ListType/assets/findDoc.js'),
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

        $query = CptDocument::where('id', '!=', null);

        if($this->config->get('r_instance_id') == 'user') {

        } else {
            $field_config = app('xe.config')->get( $this->config->name );
            if($field_config !== null) {
                // 선택한 타입들의 글만 표시
                if($field_config->get('r_instance_id')) $query->where('instance_id', $field_config->get('r_instance_id'));

                // 자신이 작성한 글만 옵션 선택시
                $user_id = auth()->user()->getId();
                if($field_config->get('author') == 'author' && $user_id != null) $query->where('user_id',$user_id);
            }
            $matchedDocumentList = $query->paginate(15, ['id','title'], 'page', 1);
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
                ->leftJoin('user', sprintf('%s.t_id',$tableName), '=', 'user.id')->orderBy( sprintf('%s.ordering',$tableName), 'ASC')->get();
        }else {
            $items = SuperRelate::Select('documents.id as r_id', 'documents.title as r_name')->where($params)
                ->leftJoin('documents', sprintf('%s.t_id',$tableName), '=', 'documents.id')->orderBy(sprintf('%s.ordering',$tableName), 'ASC')->get();
        }

        return $items;
    }
}
