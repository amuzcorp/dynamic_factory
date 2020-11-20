<?php

namespace Overcode\XePlugin\DynamicFactory;

use Overcode\XePlugin\DynamicFactory\Exceptions\AlreadyExistsInstanceException;
use Overcode\XePlugin\DynamicFactory\Exceptions\RequireCptIdException;
use Overcode\XePlugin\DynamicFactory\Handlers\ModuleConfigHandler;
use Xpressengine\Database\VirtualConnection;

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
     * @var ConfigHandler
     */
    protected $configHandler;

    public function __construct(
        VirtualConnection $conn,
        ModuleConfigHandler $configHandler
    )
    {
        $this->conn = $conn;
        $this->configHandler = $configHandler;
    }

    public function createCpt(array $params)
    {
        if (empty($params['cpt_id']) === true) {
            throw new RequireCptIdException;
        }

        $config = $this->configHandler->get($params['instanceId']);
        if ($config !== null) {
            throw new AlreadyExistsInstanceException;
        }

        $this->conn->beginTransaction();

        $config = $this->configHandler->add($params);

        $this->conn->commit();

        return $config;
    }
}
