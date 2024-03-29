<?php

namespace Overcode\XePlugin\DynamicFactory\Handlers;

use Xpressengine\Config\ConfigEntity;
use Xpressengine\Config\ConfigManager;
use Xpressengine\DynamicField\ConfigHandler as DynamicFieldConfigHandler;

class DynamicFactoryConfigHandler
{
    protected $configManager;

    protected $dynamicField;

    const CONFIG_NAME = 'dyFac';

    protected $defaultConfig = [];

    // id 에 따른 text 는 여기에 저장
    const COLUMN_LABELS = [
        'title' => '제목', 'content' => '내용', 'writer' => '작성자', 'read_count' => '읽음', 'created_at' => '작성일', 'updated_at' => '수정일', 'published_at' => '발행일', 'assent_count' => '추천', 'dissent_count' => '비추천',
    ];

    const DEFAULT_LIST_COLUMNS = [
        'title', 'writer', 'read_count', 'created_at', 'updated_at', 'published_at', 'assent_count', 'dissent_count',
    ];

    const DEFAULT_SELECTED_LIST_COLUMNS = [
        'title', 'writer', 'published_at', 'read_count'
    ];

    const DEFAULT_FORM_COLUMNS = [
        'title', 'content',
    ];

    const DEFAULT_SELECTED_FORM_COLUMNS = [
        'title', 'content',
    ];

    public function __construct(
        ConfigManager $configManager,
        DynamicFieldConfigHandler $dynamicField
    )
    {
        $this->configManager = $configManager;
        $this->dynamicField = $dynamicField;
    }

    public function getDefaultListColumns()
    {
        return self::DEFAULT_SELECTED_LIST_COLUMNS;
    }

    public function getDefaultColumnLabels()
    {
        return self::COLUMN_LABELS;
    }

    public function storeDfConfig()
    {
        if($this->get(self::CONFIG_NAME) !== null) {
            //$this->removeConfig($this->get(self::CONFIG_NAME));
        }else {
            $this->configManager->add(self::CONFIG_NAME, $this->defaultConfig);
        }
    }

    public function getConfigName($instanceId)
    {
        return sprintf('%s.%s', self::CONFIG_NAME, $instanceId);
    }

    public function addConfig($attributes, $configName)
    {
        return $this->configManager->add($configName, $attributes);
    }

    public function putConfig($attributes, $configName)
    {
        return $this->configManager->put($configName, $attributes);
    }

    public function modifyConfig(ConfigEntity $config)
    {
        return $this->configManager->modify($config);
    }

    public function removeConfig(ConfigEntity $config)
    {
        $this->configManager->remove($config);
    }

    public function get($configName)
    {
        return $this->configManager->get($configName);
    }

    public function getConfig($cpt_id)
    {
        $configName = $this->getConfigName($cpt_id);
        return $this->get($configName);
    }

    public function getDynamicFields(ConfigEntity $config)
    {
        $configs = $this->dynamicField->gets($config->get('documentGroup'));
        if(count($configs) == 0) {
            return [];
        }
        return $configs;
    }

    public function getSortListColumns(ConfigEntity $config)
    {
        if (empty($config->get('sortListColumns'))) {
            $sortListColumns = self::DEFAULT_LIST_COLUMNS;
        } else {
            $sortListColumns = $config->get('sortListColumns');
        }

        $dynamicFields = $this->getDynamicFields($config);
        $currentDynamicFields = [];

        foreach ($dynamicFields as $dynamicFieldConfig) {
            if($dynamicFieldConfig->get('use') === true) {
                $currentDynamicFields[] = $dynamicFieldConfig->get('id');
            }

            if($dynamicFieldConfig->get('use') === true &&
                in_array($dynamicFieldConfig->get('id'), $sortListColumns) === false) {
                $sortListColumns[] = $dynamicFieldConfig->get('id');
            }
        }

        $usableColumns = array_merge(self::DEFAULT_LIST_COLUMNS, $currentDynamicFields);
        foreach ($sortListColumns as $index => $column) {
            if (in_array($column, $usableColumns) === false) {
                unset($sortListColumns[$index]);
            }
        }

        return $sortListColumns;
    }

    /**
     * Dynamic Factory Config 정렬 리스트와 라벨을 가져온다
     *
     * @param ConfigEntity $config
     * @return array
     */
    public function getOrderListColumns(ConfigEntity $config)
    {
        if (empty($config->get('sortListColumns'))) {
            $sortListColumns = self::DEFAULT_LIST_COLUMNS;
        } else {
            $sortListColumns = $config->get('sortListColumns');
        }

        $columnLabels = self::COLUMN_LABELS;

        $dynamicFields = $this->getDynamicFields($config);
        $currentDynamicFields = [];

        foreach ($dynamicFields as $dynamicFieldConfig) {
            if($dynamicFieldConfig->get('use') === true) {
                $df_id = $dynamicFieldConfig->get('id');

                $type = app('xe.dynamicField')->getType($config->get('documentGroup'), $df_id);
                foreach ($type->getColumns() as $column) {
                    $column_name = sprintf('%s_%s', $df_id, $column->get('name'));
                    $currentDynamicFields[] = $column_name;

                    if (in_array($column_name, $sortListColumns) === false) {
                        $sortListColumns[] = $column_name;
                    }

                    $columnLabels[$column_name] = xe_trans($dynamicFieldConfig->get('label'));
                }
            }
        }

        $usableColumns = array_merge(self::DEFAULT_LIST_COLUMNS, $currentDynamicFields);
        foreach ($sortListColumns as $index => $column) {
            if (in_array($column, $usableColumns) === false) {
                unset($sortListColumns[$index]);
            }
        }

        return compact('sortListColumns', 'columnLabels');
    }

    public function getSortFormColumns(ConfigEntity $config)
    {
        if (empty($config->get('sortFormColumns'))) {
            $sortFormColumns = self::DEFAULT_FORM_COLUMNS;
        } else {
            $sortFormColumns = $config->get('sortFormColumns');
        }

        $dynamicFields = $this->getDynamicFields($config);
        $currentDynamicFields = [];

        foreach ($dynamicFields as $dynamicFieldConfig) {
            if ($dynamicFieldConfig->get('use') === true) {
                $currentDynamicFields[] = $dynamicFieldConfig->get('id');
            }

            if ($dynamicFieldConfig->get('use') === true &&
                in_array($dynamicFieldConfig->get('id'), $sortFormColumns) === false) {
                $sortFormColumns[] = $dynamicFieldConfig->get('id');
            }
        }

        $usableColumns = array_merge(self::DEFAULT_FORM_COLUMNS, $currentDynamicFields);
        foreach ($sortFormColumns as $index => $column) {
            if (in_array($column, $usableColumns) === false) {
                unset($sortFormColumns[$index]);
            }
        }

        return $sortFormColumns;
    }

    // 컬럼의 LABEL 을 구한다
    public function getColumnLabels(ConfigEntity $config)
    {
        $columnLabels = self::COLUMN_LABELS;

        $dynamicFields = $this->getDynamicFields($config);

        foreach ($dynamicFields as $val) {
            $columnLabels[$val->get('id')] = $val->get('label');
        }

        return $columnLabels;
    }

    public function getCategoryConfig($cpt_id)
    {
        $configName = $this->getConfigName($cpt_id);
        $config = $this->get($configName);

        if(empty($config->get('categories'))) {
            $config->set('categories', []);
        }

        return $config->get('categories');
    }

    /**
     * config 삭제
     *
     * @param ConfigEntity $config config
     * @return void
     */
    public function remove(ConfigEntity $config)
    {
        $this->configManager->remove($config);
    }

    /**
     * 추가된 DF에 대한 sort 를 반영하여 config 를 수정한다.
     *
     * @param $cpt_id
     */
    public function setCurrentSortFormColumns($cpt_id)
    {
        $cptConfig = $this->getConfig($cpt_id);
        if ($cptConfig !== null) {
            $cptConfig->set('formColumns', $this->getSortFormColumns($cptConfig));
            $this->configManager->modify($cptConfig);
        }
    }

    /**
     * 해당 CPT 의 에디터를 ckEditor 로 설정한다.
     *
     * @param $instanceId
     * @param $editor
     */
    public function addEditor($instanceId, $editor = 'editor/ckeditor@ckEditor')
    {
        $inputs = [$instanceId => $editor];
        $oldConfig = $this->configManager->get('editors')->getPureAll();
        $inputs = array_merge($oldConfig, $inputs);

        $this->configManager->put('editors', $inputs);
    }
}
