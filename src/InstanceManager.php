<?php

namespace Overcode\XePlugin\DynamicFactory;

use Overcode\XePlugin\DynamicFactory\Exceptions\AlreadyExistsInstanceException;
use Overcode\XePlugin\DynamicFactory\Exceptions\InvalidConfigException;
use Overcode\XePlugin\DynamicFactory\Exceptions\RequireCptIdException;
use Overcode\XePlugin\DynamicFactory\Exceptions\RequireTaxoIdException;
use Overcode\XePlugin\DynamicFactory\Handlers\CptModuleConfigHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\CptPermissionHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\TaxoModuleConfigHandler;
use Xpressengine\Database\VirtualConnection;
use Xpressengine\Permission\Grant;

/**
 * InstanceManager
 *
 * 메뉴에서 아이템 추가할 때 추가된 아이템 관리
 *
 */
class InstanceManager
{
    /**
     * @var \Xpressengine\Database\VirtualConnectionInterface
     */
    protected $conn;

    /**
     * @var CptModuleConfigHandler
     */
    protected $cptConfigHandler;

    /**
     * @var TaxoModuleConfigHandler
     */
    protected $taxoConfigHandler;

    /**
     * @var CptPermissionHandler
     */
    protected $permissionHandler;

    public function __construct(
        VirtualConnection $conn,
        CptModuleConfigHandler $cptConfigHandler,
        TaxoModuleConfigHandler $taxoConfigHandler,
        CptPermissionHandler $cptPermissionHandler
    )
    {
        $this->conn = $conn;
        $this->cptConfigHandler = $cptConfigHandler;
        $this->taxoConfigHandler = $taxoConfigHandler;
        $this->permissionHandler = $cptPermissionHandler;
    }

    public function createCpt(array $params)
    {
        if (empty($params['cpt_id']) === true) {
            throw new RequireCptIdException;
        }

        $config = $this->cptConfigHandler->get($params['instanceId']);
        if ($config !== null) {
            throw new AlreadyExistsInstanceException;
        }

        $this->conn->beginTransaction();

        $config = $this->cptConfigHandler->add($params);

        $this->permissionHandler->setByInstanceId($params['instanceId'], new Grant());

        $this->conn->commit();

        return $config;
    }

    public function updateCptConfig(array $params)
    {
        if (empty($params['cpt_id']) === true) {
            throw new RequireCptIdException;
        }

        $config = $this->cptConfigHandler->get($params['instanceId']);
        if ($config === null) {
            throw new InvalidConfigException;
        }
        foreach ($params as $key => $val) {
            $config->set($key, $val);
        }

        $this->conn->beginTransaction();

        $config = $this->cptConfigHandler->modify($config);

        $this->conn->commit();

        return $config;
    }

    public function createTaxonomy(array $params)
    {
        if (empty($params['taxo_ids']) === true) {
            throw new RequireTaxoIdException;
        }

        $config = $this->taxoConfigHandler->get($params['instanceId']);
        if ($config !== null) {
            throw new AlreadyExistsInstanceException;
        }

        $this->conn->beginTransaction();

        $config = $this->taxoConfigHandler->add($params);

        $this->conn->commit();

        return $config;
    }

    public function updateTaxoConfig(array $params)
    {
        if (empty($params['taxo_ids']) === true) {
            throw new RequireTaxoIdException;
        }

        $config = $this->taxoConfigHandler->get($params['instanceId']);
        if ($config === null) {
            throw new InvalidConfigException;
        }

        foreach ($params as $key => $val) {
            $config->set($key, $val);
        }

        $this->conn->beginTransaction();

        $config = $this->taxoConfigHandler->modify($config);

        $this->conn->commit();

        return $config;
    }
}
