<?php

namespace Overcode\XePlugin\DynamicFactory\Components\DynamicFields\SuperRelate;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
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
        ];
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
            'iids' => $this->getInstanceIds()
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

        foreach($doc_ids as $id) {
            $insertParam['t_id'] = $id;
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
        $is_changed = array_get($args, 'srf_chg', 0);
        if($is_changed == false) return;

        $config = $this->config;
        $type = $this->handler->getRegisterHandler()->getType($this->handler, $config->get('typeId'));

        $where = $this->getWhere($wheres, $config);

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

        foreach ($doc_ids as $id) {
            $insertParam['t_id'] = $id;
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

        $rawString = sprintf('%s.*', $tablePrefix . $baseTable);
        // doc 에 filed_id 가 있어야 update 가능
        $rawString .= sprintf(', null as hidden_%s', $config->get('id'));

        foreach ($type->getColumns() as $key => $column) {
            $key = $config->get('id') . '_' . $column->name;
            $rawString .= sprintf(' ,null as %s', $key);
        }

        $query->addSelect(\DB::raw($rawString));

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
}