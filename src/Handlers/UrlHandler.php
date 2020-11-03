<?php
namespace Overcode\XePlugin\DynamicFactory\Handlers;

use Xpressengine\Config\ConfigEntity;

class UrlHandler
{
    protected $cptId;

    protected $config;

    public function setCptId($cptId)
    {
        $this->cptId = $cptId;
        return $this;
    }

    public function setConfig(ConfigEntity $config)
    {
        $this->config = $config;
        return $this;
    }

    public function managerUrl($name, $params = [])
    {
        return route('dyFac.setting.'. $name, $params);
    }
}
