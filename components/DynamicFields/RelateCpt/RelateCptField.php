<?php

namespace Overcode\XePlugin\DynamicFactory\Components\DynamicFields\RelateCpt;

use Xpressengine\Config\ConfigEntity;
use Xpressengine\DynamicField\AbstractType;
use Xpressengine\DynamicField\ColumnEntity;
use Xpressengine\DynamicField\ColumnDataType;

class RelateCptField extends AbstractType
{

    protected static $path = 'dynamic_factory/components/DynamicFields/RelateCpt';

    /**
     * get field type name
     *
     * @return string
     */
    public function name()
    {
        return 'RelateCpt - 관련 사용자 문서';
    }

    /**
     * get field type description
     *
     * @return string
     */
    public function description()
    {
        return '관련 사용자 문서들을 입력 할 수 있습니다.';
    }

    /**
     * return columns
     *
     * @return ColumnEntity[]
     */
    public function getColumns()
    {
        return [
            'relate' => new ColumnEntity('relate', ColumnDataType::TEXT)
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
        return view('dynamic_factory::components/DynamicFields/RelateCpt/views/setting');
    }
}
