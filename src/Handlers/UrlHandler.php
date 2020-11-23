<?php
namespace Overcode\XePlugin\DynamicFactory\Handlers;

use Xpressengine\Config\ConfigEntity;

class UrlHandler
{
    protected $instanceId;

    protected $config;

    public function setInstanceId($instanceId)
    {
        $this->instanceId = $instanceId;
        return $this;
    }

    public function setConfig(ConfigEntity $config)
    {
        $this->config = $config;
        return $this;
    }

    public function get($name = 'index', array $params = [], $instanceId = null)
    {
        if ($instanceId == null) {
            $instanceId = $this->instanceId;
        }
        return instance_route($name, $params, $instanceId);
    }

    public function managerUrl($name, $params = [])
    {
        return route('settings.cpt.cpt.'. $name, $params);
    }
}
