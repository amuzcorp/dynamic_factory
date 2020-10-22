<?php
namespace Overcode\XePlugin\DynamicFactory\Services;

use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryConfigHandler;
use XeDB;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryHandler;
use Xpressengine\Http\Request;

class DynamicFactoryService
{
    protected $dfHandler;

    protected $dfConfigHandler;

    public function __construct(
        DynamicFactoryHandler $dfHandler,
        DynamicFactoryConfigHandler $dfConfigHandler
    )
    {
        $this->dfHandler = $dfHandler;
        $this->dfConfigHandler = $dfConfigHandler;
    }

    public function getItemsJson(array $attr)
    {
        $json = $attr;

        return $json;
    }

    public function storeCpt(Request $request)
    {
        $inputs = $request->originExcept('_token');

        XeDB::beginTransaction();
        try {
            $cpt = $this->dfHandler->store_cpt($inputs);
        }catch (\Exception $e) {
            XeDB::rollback();

            throw $e;
        }
        XeDB::commit();

        return $cpt;
    }

    public function updateCpt(Request $request)
    {
        $inputs = $request->originExcept('_token');
        $cpt = $this->dfHandler->update_cpt($inputs);

        return $cpt;
    }

    public function getItems()
    {
        $cpt = $this->dfHandler->getItems();

        return $cpt;
    }

    public function getItem($cpt_id)
    {
        $cpt = $this->dfHandler->getItem($cpt_id);

        return $cpt;
    }
}
