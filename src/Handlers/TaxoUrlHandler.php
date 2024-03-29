<?php

namespace Overcode\XePlugin\DynamicFactory\Handlers;

use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use Xpressengine\Config\ConfigEntity;

class TaxoUrlHandler
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

    public function getShow($item, $params =[], ConfigEntity $config = null)
    {
        $slug = $item->id;

        return $this->get('slug', [$slug], $this->instanceId);
    }

    public function managerUrl($name, $params = [])
    {
        return route('settings.taxo.taxo.'. $name, $params);
    }
}
