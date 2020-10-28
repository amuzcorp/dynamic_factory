<?php

namespace Overcode\XePlugin\DynamicFactory\Handlers;

use Xpressengine\Config\ConfigEntity;
use Xpressengine\Config\ConfigManager;

class DynamicFactoryConfigHandler
{
    protected $configManager;

    const CONFIG_NAME = 'dyFac';

    protected $defaultConfig = [
        'temp' => '어떤 정보를 저장할까?'
    ];

    public function __construct($configManager)
    {
        $this->configManager = $configManager;
    }

    public function storeDfConfig()
    {
        $this->removeConfig($this->get(self::CONFIG_NAME));
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
}
