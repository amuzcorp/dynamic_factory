<?php
namespace Overcode\XePlugin\DynamicFactory\Services;

use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryConfigHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryDocumentHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryTaxonomyHandler;
use Overcode\XePlugin\DynamicFactory\Plugin;
use XeDB;
use XeSite;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryHandler;
use Xpressengine\Category\Models\CategoryItem;
use Xpressengine\Config\ConfigEntity;
use Xpressengine\Http\Request;

class DynamicFactoryService
{
    protected $dfHandler;

    protected $dfConfigHandler;

    protected $dfTaxonomyHandler;

    protected $dfDocumentHandler;

    public function __construct(
        DynamicFactoryHandler $dfHandler,
        DynamicFactoryConfigHandler $dfConfigHandler,
        DynamicFactoryTaxonomyHandler $dfTaxonomyHandler,
        DynamicFactoryDocumentHandler $dfDocumentHandler
    )
    {
        $this->dfHandler = $dfHandler;
        $this->dfConfigHandler = $dfConfigHandler;
        $this->dfTaxonomyHandler = $dfTaxonomyHandler;
        $this->dfDocumentHandler = $dfDocumentHandler;
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

            $configName = $this->dfConfigHandler->getConfigName('df' . $cpt->cpt_id);
            $this->dfConfigHandler->addConfig([
                'documentGroup' => 'documents_df' . $cpt->cpt_id,
                'listColumns' => DynamicFactoryConfigHandler::DEFAULT_SELECTED_LIST_COLUMNS,
                'sortListColumns' => DynamicFactoryConfigHandler::DEFAULT_LIST_COLUMNS,
                'formColumns' => DynamicFactoryConfigHandler::DEFAULT_SELECTED_FORM_COLUMNS,
                'sortFormColumns' => DynamicFactoryConfigHandler::DEFAULT_FORM_COLUMNS
            ], $configName);

        }catch (\Exception $e) {
            XeDB::rollback();

            throw $e;
        }
        XeDB::commit();

        return $cpt;
    }

    public function storeCptTaxonomy(Request $request)
    {
        $inputs = $request->except('_token');

        XeDB::beginTransaction();
        try {
            $category_id = $this->dfTaxonomyHandler->createTaxonomy($inputs);
        }catch (\Exception $e) {
            XeDB::rollback();
            throw $e;
        }
        XeDB::commit();

        return $category_id;
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

        $cpts = \XeRegister::get('dynamic_factory');    // register 에 등록된 cpt 를 가져온다
        if($cpts && array_key_exists($cpt_id, $cpts)){
            $cpt = $cpts[$cpt_id];
        }

        return $cpt;
    }

    public function getCptConfig($cpt_id)
    {
        $configName = $this->dfConfigHandler->getConfigName($cpt_id);
        return $this->dfConfigHandler->get($configName);
    }

    public function getFieldTypes(ConfigEntity $config)
    {
        return (array)$this->dfConfigHandler->getDynamicFields($config);
    }

    public function getCategories($cpt_id)
    {
        $categories = $this->dfTaxonomyHandler->getTaxonomies($cpt_id);

        return $categories;
    }

    public function storeCptDocument(Request $request)
    {
        $inputs = $request->originExcept('_token');

        if (isset($inputs['user_id']) === false) {
            $inputs['user_id'] = auth()->user()->getId();
        }

        if (isset($inputs['writer']) === false) {
            $inputs['writer'] = auth()->user()->getDisplayName();
        }

        XeDB::beginTransaction();
        try {
            $cpt_id = $request->cpt_id;

            /** @var DocumentHandler $documentConfigHandler */
            $documentConfigHandler = app('xe.document');
            $config = $documentConfigHandler->getConfigHandler()->get($cpt_id);
            if(!$config){
                $documentConfigHandler->createInstance($cpt_id, ['instanceId' => $cpt_id, 'group' => Plugin::getId() . '_' . $cpt_id, 'siteKey' => XeSite::getCurrentSiteKey()]);
            }
            $document = $this->dfDocumentHandler->store($inputs);
            $this->dfTaxonomyHandler->storeTaxonomy($document, $inputs);

        }catch (\Exception $e) {
            XeDB::rollback();

            throw $e;
        }
        XeDB::commit();

        return $document;
    }
}
