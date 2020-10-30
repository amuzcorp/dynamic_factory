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

    protected $defaultConfig = [
        'temp' => '어떤 정보를 저장할까?'
    ];

    const DEFAULT_LIST_COLUMNS = [
        'title', 'writer', 'assent_count', 'read_count', 'created_at', 'updated_at', 'dissent_count',
    ];

    const DEFAULT_SELECTED_LIST_COLUMNS = [
        'title', 'writer',  'assent_count', 'read_count', 'created_at',
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

    public function storeDfConfig()
    {
        if($this->get(self::CONFIG_NAME) !== null) {
            $this->removeConfig($this->get(self::CONFIG_NAME));
        }
        $this->configManager->add(self::CONFIG_NAME, $this->defaultConfig);
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

    public function getSortFormColumns(ConfigEntity $config)
    {

    }
}
