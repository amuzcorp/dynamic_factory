<?php
namespace Overcode\XePlugin\DynamicFactory\Handlers;

use Amuz\XePlugin\Maemulmoa\Components\Modules\Vendor\VendorModule;
use Overcode\XePlugin\DynamicFactory\Components\Modules\Cpt\CptModule;
use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use Xpressengine\Config\ConfigEntity;
use Xpressengine\Routing\InstanceRoute;

class CptUrlHandler
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

    public function getInstanceId()
    {
        return $this->instanceId;
    }

    public function getModule()
    {
        $instance = InstanceRoute::where('instance_id',$this->instanceId)->first();
        return array_get($instance,'module');
    }

    public function get($name = 'index', array $params = [], $instanceId = null)
    {
        if ($instanceId == null) {
            $instanceId = $this->instanceId;
        }
        return instance_route($name, $params, $instanceId);
    }

    public function getShow(CptDocument $document, $params =[], ConfigEntity $config = null)
    {
        $slug = $document->slug;
        if ($slug != null) {
            return $this->getSlug($slug->slug, $params, $this->instanceId);
        }

        $id = $document->id;
        $params['id'] = $id;
        return $this->get('show', $params, $this->instanceId);
    }

    public function getSlug($slug, array $params, $instanceId)
    {
        unset($params['id']);
        $params['slug'] = $slug;

        // 페이지 정보는 넘기지 않음
        unset($params['page']);

        return $this->get('slug', $params, $instanceId);
    }

    public function managerUrl($name, $params = [], $prefix = null)
    {
        $prefix = $prefix == null ? CptModule::ROUTE_PREFIX : $prefix;
        return route($prefix. '.' . $name, $params);
    }
}
