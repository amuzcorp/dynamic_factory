<?php

namespace Overcode\XePlugin\DynamicFactory\Components\DynamicFields\SuperRelate;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\Schema\Blueprint;
use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use Overcode\XePlugin\DynamicFactory\Models\SuperRelate;
use Xpressengine\Config\ConfigEntity;
use Xpressengine\Database\DynamicQuery;
use Xpressengine\DynamicField\AbstractType;
use Xpressengine\DynamicField\ColumnDataType;
use Xpressengine\DynamicField\ColumnEntity;
use Xpressengine\Menu\Models\MenuItem;

class SuperRelateField extends AbstractType
{
    const TABLE_NAME = 'df_super_relate';

    /**
     * get field type name
     *
     * @return string
     */
    public function name()
    {
        return 'SuperRelate - 슈퍼 릴레이트';
    }

    /**
     * get field type description
     *
     * @return string
     */
    public function description()
    {
        return '관련 Document, User 의 정보를 저장합니다.';
    }

    /**
     * get dynamic field type table name
     *
     * @return string
     */
    public function getPureTableName()
    {
        return self::TABLE_NAME;
    }

    /**
     * get dynamic field table name
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->getPureTableName();
    }

    /**
     * get dynamic field revision table name
     *
     * @return string
     */
    public function getRevisionTableName()
    {
        return 'revision_' . $this->getPureTableName();
    }

    /**
     * return columns
     *
     * @return ColumnEntity[]
     */
    public function getColumns()
    {
        return [
            's_id' => new ColumnEntity('s_id', ColumnDataType::STRING),
            's_group' => new ColumnEntity('s_group', ColumnDataType::STRING),
            's_type' => new ColumnEntity('s_type', ColumnDataType::STRING),
            't_id' => new ColumnEntity('t_id', ColumnDataType::STRING),
            't_group' => new ColumnEntity('t_group', ColumnDataType::STRING),
            't_type' => new ColumnEntity('t_type', ColumnDataType::STRING),
            'ordering' => new ColumnEntity('ordering', ColumnDataType::INTEGER),
        ];
    }

    /**
     * return rules
     *
     * @return array
     */
    public function getRules()
    {
        $required = $this->config->get('required') === true;

        return [];
    }

    /**
     * 다이나믹필스 생성할 때 타입 설정에 적용될 rule 반환
     *
     * @return array
     */
    public function getSettingsRules()
    {
        return ['r_instance_id' => 'required', 'author' => 'required'];
    }

    /**
     * Dynamic Field 설정 페이지에서 각 fieldType 에 필요한 설정 등록 페이지 반환
     * return html tag string
     *
     * @param ConfigEntity $config config entity
     * @return string
     */
    public function getSettingsView(ConfigEntity $config = null)
    {
        return view('dynamic_factory::components/DynamicFields/SuperRelate/views/setting',[
            'config' => $config,
            'iids' => $this->getInstanceIds(),
            'groups' => app('amuz.usertype.handler')->getUserGroups()
        ]);
    }

    protected function getInstanceIds()
    {
        $cpts = app('overcode.df.service')->getItemsAll();
        $query = MenuItem::Where('type','board@board');
        if(\Schema::hasColumn('menu_item', 'site_key')) $query = $query->where('site_key', \XeSite::getCurrentSiteKey());
        $boards = $query->orderBy('ordering')->get();

        $iids = [];

        foreach ($cpts as $cpt) $iids[$cpt->cpt_id] = ['type' => 'cpt', 'id' => $cpt->cpt_id, 'name' => $cpt->cpt_name];
        foreach ($boards as $brd) $iids[$brd->id] = ['type' => 'board', 'id' => $brd->id, 'name' => xe_trans($brd->title)];

        return $iids;
    }

    /**
     * 생성된 Dynamic Field 테이블에 데이터 입력
     *
     * @param array $args parameters
     * @return void
     */
    public function insert(array $args)
    {
        $config = $this->config;

        if (isset($args[$config->get('joinColumnName')]) === false) {
            throw new Exceptions\RequiredJoinColumnException;
        }

        // event fire
        $this->handler->getRegisterHandler()->fireEvent(
            sprintf('dynamicField.%s.%s.before_insert', $config->get('group'), $config->get('id'))
        );

        $is_user = ($config->get('r_instance_id') == 'user');

        $insertParam = [];
        $insertParam['field_id'] = $config->get('id');
        $insertParam['s_id'] = $args[$config->get('joinColumnName')];
        $insertParam['s_group'] = $config->get('group');
        $insertParam['s_type'] = (strpos($config->get('group'), 'documents_') !== false) ? 'document' : 'user';
        $insertParam['t_group'] = $is_user ? 'user' : sprintf('documents_%s', $config->get('r_instance_id'));
        $insertParam['t_type'] = $is_user ? 'user' : 'document';

        $doc_ids = array_get($args, sprintf('hidden_%s', $config->get('id')), []);  // relate doc ids
        if(!is_array($doc_ids)) $doc_ids = json_dec($doc_ids);
        foreach($doc_ids as $key => $id) {
            $insertParam['t_id'] = $id;
            $insertParam['ordering'] = $key;
            SuperRelate::updateOrCreate($insertParam);
        }

        // event fire
        $this->handler->getRegisterHandler()->fireEvent(
            sprintf('dynamicField.%s.%s.after_insert', $config->get('group'), $config->get('id'))
        );
    }

    /**
     * 생성된 Dynamic Field 테이블에 데이터 수정
     *
     * @param array $args   parameters
     * @param array $wheres Illuminate\Database\Query\Builder's wheres attribute
     * @return void
     */
    public function update(array $args, array $wheres)
    {
        $config = $this->config;
        if(!$config) return null;

        $where = $this->getWhere($wheres, $config);
        if(!$where) return null;
        if(!isset($where['field_id'])) return null;

        $is_changed = array_get($args, $where['field_id'].'_srf_chg', 0);
        if($is_changed == false) return;

        $type = $this->handler->getRegisterHandler()->getType($this->handler, $config->get('typeId'));

        if (isset($where['target_id']) === false) {
            return null;
        }

        foreach ($args as $index => $arg) {
            if ($arg == null) {
                $args[$index] = '';
            }
        }

        // event fire
        $this->handler->getRegisterHandler()->fireEvent(
            sprintf('dynamicField.%s.%s.before_update', $config->get('group'), $config->get('id'))
        );

        $is_user = ($config->get('r_instance_id') == 'user');

        $insertParam = [];
        $insertParam['field_id'] = $where['field_id'];
        $insertParam['s_id'] = $where['target_id'];
        $insertParam['s_group'] = $where['group'];

        $this->handler->connection()->table(self::TABLE_NAME)->where($insertParam)->delete();    // all delete

        $insertParam['s_type'] = (strpos($config->get('group'), 'documents_') !== false) ? 'document' : 'user';
        $insertParam['t_group'] = $is_user ? 'user' : sprintf('documents_%s', $config->get('r_instance_id'));
        $insertParam['t_type'] = $is_user ? 'user' : 'document';

        $doc_ids = array_get($args, sprintf('hidden_%s', $config->get('id')), []);  // relate doc ids
        if(!is_array($doc_ids)) $doc_ids = json_dec($doc_ids);

        foreach ($doc_ids as $key => $id) {
            if(!$id) continue;
            $insertParam['t_id'] = $id;
            $insertParam['ordering'] = $key;
            SuperRelate::updateOrCreate($insertParam);
        }

        // event fire
        $this->handler->getRegisterHandler()->fireEvent(
            sprintf('dynamicField.%s.%s.after_update', $config->get('group'), $config->get('id'))
        );
    }

    /**
     * 생성된 Dynamic Field 테이블에 데이터 삭제
     *
     * @param array $wheres Illuminate\Database\Query\Builder's wheres attribute wheres attribute
     * @return void
     */
    public function delete(array $wheres)
    {
        // update 시 delete 후 insert
    }

    /**
     * 생성된 Dynamic Field revision 테이블에 데이터 입력
     *
     * @param array $args parameters
     * @return void
     */
    public function insertRevision(array $args)
    {
        // revision 생성 하지 않음
    }

    /**
     * table join
     *
     * @param DynamicQuery $query  query builder
     * @param ConfigEntity $config config entity
     * @return Builder
     */
    public function join(DynamicQuery $query, ConfigEntity $config = null)
    {

        if ($config === null) {
            $config = $this->config;
        }

        if ($config->get('use') === false) {
            return $query;
        }

        $baseTable = $query->from;

        $type = $this->handler->getRegisterHandler()->getType($this->handler, $config->get('typeId'));
        $tablePrefix = $this->handler->connection()->getTablePrefix();

        $createTableName = $type->getTableName();
        if ($query->hasDynamicTable($config->get('group') . '_' . $config->get('id')) === true) {
            return $query;
        }

        $rawString = sprintf('%s.*', $tablePrefix . $baseTable);
        foreach ($type->getColumns() as $column) {
            $key = $config->get('id') . '_' . $column->name;

            $rawString .= sprintf(', %s.%s as %s', $tablePrefix . $config->get('id'), $column->name, $key);
        }
        $rawString .= sprintf(', null as '.$config->get('id').'_srf_chg, null as hidden_%s', $config->get('id'));

        $query->leftJoin(
            sprintf('%s as %s', $createTableName, $config->get('id')),
            function (JoinClause $join) use ($createTableName, $config, $baseTable) {
                $join->on(
                    sprintf('%s.%s', $baseTable, $config->get('joinColumnName')),
                    '=',
                    sprintf('%s.s_id', $config->get('id'))
                )->where(function($q) use ($config){
                    $q->where($config->get('id') . '.ordering',0);
                    $q->orWhere($config->get('id') . '.ordering',null);
                })->where($config->get('id') . '.field_id', $config->get('id'));
            }
        )->selectRaw($rawString);

        $query->setDynamicTable($config->get('group') . '_' . $config->get('id'));

        return $query;
    }

    /**
     * create dynamic field tables
     *
     * @return void
     */
    public function createTypeTable()
    {
        //일반 테이블 생성
        if ($this->handler->connection()->getSchemaBuilder()->hasTable(self::TABLE_NAME) == false) {
            $this->handler->connection()->getSchemaBuilder()->create(self::TABLE_NAME,
                function (Blueprint $table) {
                    $table->string('field_id', 36)->comment('Dynamic Field Id');

                    $table->string('s_id', 36)->comment('Source Id (doc_id, user_id)');
                    $table->string('s_group')->comment('Source Field Group');
                    $table->string('s_type')->comment('Source Type (doc, user)');

                    $table->string('t_id', 36)->comment('Target Id (doc_id, user_id)');
                    $table->string('t_group')->comment('Target Field Group');
                    $table->string('t_type')->comment('Target Type (doc, user)');
                    $table->integer('ordering')->default(0)->comment('ordering');

                    $table->index(['field_id', 's_id', 't_id'], 'index');
                }
            );
        }

        //revision 테이블 생성은 하지 않음
    }

    /**
     * delete dynamic field all data
     *
     * @return void
     */
    public function dropData()
    {
        $where  = [
            ['field_id', $this->config->get('id', '')],
            ['s_group', $this->config->get('group', '')]
        ];

        $this->handler->connection()->table(self::TABLE_NAME)->where($where)->delete();
    }


    /**
     * 관리자 페이지 목록을 출력하기 위한 함수.
     * CPT 목록에만 해당하며, 필드타입자체에 추가해주어야한다.
     *
     * @param string $id dynamic field name
     * @param CptDocument $doc arguments
     * @return string|null
     */
    public function getSettingListItem($id, CptDocument $doc){
        $data = $doc->hasDocument($id);

        if (count($data) == 0) {
            return null;
        }

        return view('dynamic_factory::components/DynamicFields/SuperRelate/views/list-item',compact('id','data'));
    }

    public function getSettingListUserItem($id, CptDocument $doc){
        $data = $doc->hasUser($id);

        if (count($data) == 0) {
            return null;
        }

        return view('dynamic_field_extend::src/DynamicFields/CategoryInput/views/list-item-user',compact('id','data'));
    }
}
