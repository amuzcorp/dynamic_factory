<?php

namespace Overcode\XePlugin\DynamicFactory\Handlers;

use Xpressengine\Config\ConfigEntity;
use Xpressengine\Config\ConfigManager;

class CptModuleConfigHandler
{
    /**
     * config package name
     * 다른 모듈과 충돌을 피하기 위해 설정 이름을 모듈 이름으로 선언
     */
    const CONFIG_NAME = 'module/cpt@cpt';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var array
     */
    protected $defaultConfig = [];

    public function __construct(
        ConfigManager $configManager
    )
    {
        $this->configManager = $configManager;
    }

    /**
     * 기본 설정 반환. 설정이 없을 경우 등록 후 반환
     *
     * @return ConfigEntity
     */
    public function getDefault()
    {
        $parent = $this->configManager->get(static::CONFIG_NAME);

        if ($parent == null) {
            $default = $this->defaultConfig;
            $parent = $this->configManager->add(static::CONFIG_NAME, $default);
        }

        return $parent;
    }

    /**
     * get config Entity
     * $instanceId 가 없을 경우 default config 반환
     *
     * @param string $instanceId instance id
     * @return ConfigEntity
     */
    public function get($instanceId = null)
    {
        if ($instanceId === null) {
            return $this->configManager->get(self::CONFIG_NAME);
        } else {
            return $this->configManager->get(
                sprintf('%s.%s', self::CONFIG_NAME, $instanceId)
            );
        }
    }

    /**
     * 인스턴스 설정 이름 반환
     *
     * @param string $instanceId
     * @return string
     */
    private function name($instanceId)
    {
        return sprintf('%s.%s', static::CONFIG_NAME, $instanceId);
    }

    /**
     * add config
     *
     * @param array $params parameters
     * @return ConfigEntity
     * @throws \Xpressengine\Config\Exceptions\InvalidArgumentException
     */
    public function add(array $params)
    {
        return $this->configManager->add($this->name($params['instanceId']), $params);
    }

    /**
     * modify config
     *
     * @param ConfigEntity $config config entity
     * @return ConfigEntity
     */
    public function modify(ConfigEntity $config)
    {
        return $this->configManager->modify($config);
    }

}
