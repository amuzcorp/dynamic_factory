<?php

namespace Overcode\XePlugin\DynamicFactory\Components\DynamicFields\RelateDocument;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Overcode\XePlugin\DynamicFactory\Models\RelateDocument;
use Xpressengine\Config\ConfigEntity;
use Xpressengine\Database\DynamicQuery;
use Xpressengine\DynamicField\AbstractType;
use Xpressengine\DynamicField\ColumnDataType;
use Xpressengine\DynamicField\ColumnEntity;
use Xpressengine\Menu\Models\MenuItem;

class RelateDocumentField extends AbstractType
{

    /**
     * get field type name
     *
     * @return string
     */
    public function name()
    {
        return 'RelateDocument - 관련 문서';
    }

    /**
     * get field type description
     *
     * @return string
     */
    public function description()
    {
        return '관련 문서 번호를 저장합니다.';
    }

    /**
     * return columns
     *
     * @return ColumnEntity[]
     */
    public function getColumns()
    {
        return [
            'r_id' => new ColumnEntity('r_id', ColumnDataType::STRING),
            'r_group' => new ColumnEntity('r_group', ColumnDataType::STRING),
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
        return view('dynamic_factory::components/DynamicFields/RelateDocument/views/setting',[
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

        $doc_ids = array_get($args, sprintf('hidden_%s', $config->get('id')), []);  // relate doc ids

        $insertParam = [];
        $insertParam['field_id'] = $config->get('id');
        $insertParam['target_id'] = $args[$config->get('joinColumnName')];
        $insertParam['group'] = $config->get('group');
        $insertParam['r_group'] = sprintf('documents_%s', $config->get('r_instance_id'));

        // event fire
        $this->handler->getRegisterHandler()->fireEvent(
            sprintf('dynamicField.%s.%s.before_insert', $config->get('group'), $config->get('id'))
        );

        foreach($doc_ids as $id) {
            $insertParam['r_id'] = $id;
            RelateDocument::updateOrCreate($insertParam);
//            $this->handler->connection()->table($this->getTableName())->firstOrNew($insertParam)->save();
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

        $this->handler->connection()->table($type->getTableName())->where($where)->delete();    // all delete

        $insertParam = $where;
        $insertParam['r_group'] = sprintf('documents_%s', $config->get('r_instance_id'));
        $doc_ids = array_get($args, sprintf('hidden_%s', $config->get('id')), []);  // relate doc ids
        foreach ($doc_ids as $doc_id) {
            $insertParam['r_id'] = $doc_id;
            RelateDocument::updateOrCreate($insertParam);
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
        $config = $this->config;
        $type = $this->handler->getRegisterHandler()->getType($this->handler, $config->get('typeId'));
        $where = $this->getWhere($wheres, $config);

        if (isset($where['target_id']) === false) {
            throw new Exceptions\RequiredDynamicFieldException;
        }

        // event fire
        $this->handler->getRegisterHandler()->fireEvent(
            sprintf('dynamicField.%s.%s.before_delete', $config->get('group'), $config->get('id'))
        );

        $this->handler->connection()->table($type->getTableName())->where($where)->delete();

        // event fire
        $this->handler->getRegisterHandler()->fireEvent(
            sprintf('dynamicField.%s.%s.after_delete', $config->get('group'), $config->get('id'))
        );
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

        $type = $this->handler->getRegisterHandler()->getType($this->handler, $config->get('typeId'));

        // doc 에 filed_id 가 있어야 update 가능
        $rawString = sprintf('*, 1 as hidden_%s', $config->get('id'));

        foreach ($type->getColumns() as $key => $column) {
            $key = $config->get('id') . '_' . $column->name;
            $rawString .= sprintf(' ,1 as %s', $key);
        }

        $query->addSelect(\DB::raw($rawString));

        return $query;
    }
}
