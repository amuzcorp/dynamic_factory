<?php

namespace Overcode\XePlugin\DynamicFactory\Components\DynamicFields\RelateMember;

use Illuminate\Database\Query\Builder;
use Overcode\XePlugin\DynamicFactory\Models\RelateMember;
use Xpressengine\Config\ConfigEntity;
use Xpressengine\Database\DynamicQuery;
use Xpressengine\DynamicField\AbstractType;
use Xpressengine\DynamicField\ColumnDataType;
use Xpressengine\DynamicField\ColumnEntity;

class RelateMemberField extends AbstractType
{

    /**
     * get field type name
     *
     * @return string
     */
    public function name()
    {
        return 'RelateMember - 관련 사용자';
    }

    /**
     * get field type description
     *
     * @return string
     */
    public function description()
    {
        return '관련 사용자 아이디를 저장합니다.';
    }

    /**
     * return columns
     *
     * @return ColumnEntity[]
     */
    public function getColumns()
    {
        return [
            'user_id' => new ColumnEntity('user_id', ColumnDataType::STRING)
        ];
    }

    /**
     * 다이나믹필스 생성할 때 타입 설정에 적용될 rule 반환
     *
     * @return array
     */
    public function getSettingsRules()
    {
        return [];
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
        return '';
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

        $user_ids = array_get($args, sprintf('hidden_%s', $config->get('id')), []);  // relate doc ids

        $insertParam = [];
        $insertParam['field_id'] = $config->get('id');
        $insertParam['target_id'] = $args[$config->get('joinColumnName')];
        $insertParam['group'] = $config->get('group');

        // event fire
        $this->handler->getRegisterHandler()->fireEvent(
            sprintf('dynamicField.%s.%s.before_insert', $config->get('group'), $config->get('id'))
        );

        foreach($user_ids as $id) {
            $insertParam['user_id'] = $id;
            RelateMember::updateOrCreate($insertParam);
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

        $user_ids = array_get($args, sprintf('hidden_%s', $config->get('id')), []);  // relate user ids
        foreach ($user_ids as $id) {
            $insertParam['user_id'] = $id;
            RelateMember::updateOrCreate($insertParam);
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
        $rawString .= sprintf(',1 as hidden_%s', $config->get('id'));

        foreach ($type->getColumns() as $key => $column) {
            $key = $config->get('id') . '_' . $column->name;
            $rawString .= sprintf(' ,1 as %s', $key);
        }

        $query->addSelect(\DB::raw($rawString));

        return $query;
    }
}
