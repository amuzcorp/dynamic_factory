<?php
namespace Overcode\XePlugin\DynamicFactory\Services;

use Overcode\XePlugin\DynamicFactory\Exceptions\InvalidConfigException;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryConfigHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryDocumentHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryTaxonomyHandler;
use Overcode\XePlugin\DynamicFactory\Interfaces\Orderable;
use Overcode\XePlugin\DynamicFactory\Interfaces\Searchable;
use Overcode\XePlugin\DynamicFactory\Models\Cpt;
use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use Overcode\XePlugin\DynamicFactory\Models\DfSlug;
use Overcode\XePlugin\DynamicFactory\Plugin;
use XeDB;
use XeSite;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryHandler;
use Xpressengine\Category\Models\CategoryItem;
use Xpressengine\Config\ConfigEntity;
use Xpressengine\Document\DocumentHandler;
use Xpressengine\Document\Models\Document;
use Xpressengine\DynamicField\DynamicFieldHandler;
use Xpressengine\Http\Request;

class DynamicFactoryService
{
    protected $dfHandler;

    protected $dfConfigHandler;

    protected $dfTaxonomyHandler;

    protected $dfDocumentHandler;

    /**
     * @var DynamicFieldHandler
     */
    protected $dynamicField;

    /**
     * @var DocumentHandler
     */
    protected $document;

    protected $handlers = [];

    public function __construct(
        DynamicFactoryHandler $dfHandler,
        DynamicFactoryConfigHandler $dfConfigHandler,
        DynamicFactoryTaxonomyHandler $dfTaxonomyHandler,
        DynamicFactoryDocumentHandler $dfDocumentHandler,
        DynamicFieldHandler $dynamicField,
        DocumentHandler $document
    )
    {
        $this->dfHandler = $dfHandler;
        $this->dfConfigHandler = $dfConfigHandler;
        $this->dfTaxonomyHandler = $dfTaxonomyHandler;
        $this->dfDocumentHandler = $dfDocumentHandler;
        $this->dynamicField = $dynamicField;
        $this->document = $document;
    }

    public function addHandlers($handler)
    {
        $this->handlers[] = $handler;
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

            $configName = $this->dfConfigHandler->getConfigName($inputs['cpt_id']);
            $this->dfConfigHandler->addConfig([
                'documentGroup' => 'documents_' . $inputs['cpt_id'],
                'listColumns' => DynamicFactoryConfigHandler::DEFAULT_SELECTED_LIST_COLUMNS,
                'sortListColumns' => DynamicFactoryConfigHandler::DEFAULT_LIST_COLUMNS,
                'formColumns' => DynamicFactoryConfigHandler::DEFAULT_SELECTED_FORM_COLUMNS,
                'sortFormColumns' => DynamicFactoryConfigHandler::DEFAULT_FORM_COLUMNS
            ], $configName);

            $this->dfConfigHandler->addEditor($inputs['cpt_id']);   //기본 에디터 ckEditor 로 설정

        }catch (\Exception $e) {
            XeDB::rollback();

            throw $e;
        }
        XeDB::commit();

        return $cpt;
    }

    // return category_id
    public function storeCptTaxonomy(Request $request)
    {
        $inputs = $request->except('_token');

        return $this->dfTaxonomyHandler->createTaxonomy($inputs);
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

    public function getItemsFromPlugin()
    {
        $cpt = $this->dfHandler->getItemsFromPlugin();

        return $cpt;
    }

    public function getItemsAll()
    {
        $cpts = $this->getItems();
        $cpts_from_plugin = $this->getItemsFromPlugin();

        foreach ($cpts_from_plugin as $cpt) {
            $cpts->push($cpt);
        }

        return $cpts;
    }

    public function getItem($cpt_id)
    {
        $cpt = $this->dfHandler->getItem($cpt_id);

        if(empty($cpt)) {
            $cpts = \XeRegister::get('dynamic_factory');    // register 에 등록된 cpt 를 가져온다
            $cpt = new Cpt();
            if ($cpts && array_key_exists($cpt_id, $cpts)) {
                $temp_cpt = $cpts[$cpt_id];
                $cpt->setRawAttributes($temp_cpt);
                $cpt->is_made_plugin = true;    // plugin 에서 생성한 cpt 인지 구분
            }
        }
        if(!isset($cpt->blades) || !is_array($cpt->blades)) {
            $cpt->blades = [
                'list' => 'dynamic_factory::views.documents.list',
                'create' => 'dynamic_factory::views.documents.create',
                'edit' => 'dynamic_factory::views.documents.edit'
            ];
        }
        if(empty($cpt->blades['list'])) $cpt->blades['list'] = 'dynamic_factory::views.documents.list';
        if(empty($cpt->blades['create'])) $cpt->blades['create'] = 'dynamic_factory::views.documents.create';
        if(empty($cpt->blades['edit'])) $cpt->blades['edit'] = 'dynamic_factory::views.documents.edit';

        return $cpt;
    }

    public function getCptConfig($cpt_id)
    {
        return $this->dfConfigHandler->getConfig($cpt_id);
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

            $cptDocument = CptDocument::find($document->id);
            $this->saveSlug($cptDocument, $inputs);
        }catch (\Exception $e) {
            XeDB::rollback();

            throw $e;
        }
        XeDB::commit();

        return $document;
    }

    public function updateCptDocument(Request $request)
    {
        $inputs = $request->originExcept('_token');

        XeDB::beginTransaction();
        try {
            $cpt_id = $request->cpt_id;

            $doc = CptDocument::division($cpt_id)->find($request->get('doc_id'));

            $this->dfDocumentHandler->update($doc, $inputs);

            $this->dfTaxonomyHandler->updateTaxonomy($doc, $inputs);

            $this->saveSlug($doc, $inputs);

        }catch (\Exception $e) {
            XeDB::rollback();

            throw $e;
        }
        XeDB::commit();
    }

    public function getItemsWhereQuery(array $attributes)
    {
        $instance_id = $attributes['cpt_id'];

        $query = CptDocument::division($instance_id)->where('instance_id', $instance_id);

        $query->visible(); // trash 가 아닌것만

        foreach ($this->handlers as $handler) {
            if ($handler instanceof Searchable) {
                $query = $handler->getItems($query, $attributes);
            }
        }

        return $query;
    }

    public function getItemsOrderQuery($query, $attributes)
    {
        foreach ($this->handlers as $handler) {
            if ($handler instanceof Orderable) {
                $query = $handler->getOrder($query, $attributes);
            }
        }

        return $query;
    }

    public function getCategoryExtras()
    {
        return $this->dfTaxonomyHandler->getCategoryExtras();
    }

    protected function saveSlug(CptDocument $cptDocument, array $args)
    {
        $slug = $cptDocument->dfSlug;
        if ($slug === null) {
            $args['slug'] = DfSlug::make($args['slug'], $cptDocument->id);
            $slug = new DfSlug([
                'slug' => $args['slug'],
                'title' => $args['title'],
                'instance_id' => $args['cpt_id']
            ]);
        } else {
            $slug->slug = $args['slug'];
            $slug->title = $cptDocument->title;
        }

        $cptDocument->dfSlug()->save($slug);
    }

    public function getSelectCategoryItems($cpt_id, $target_id)
    {
        return $this->dfTaxonomyHandler->getSelectCategoryItems($cpt_id, $target_id);
    }

    public function destroyCpt($cpt_id)
    {
        $config = $this->dfConfigHandler->getConfig($cpt_id);
        if($config === null) {
            throw new InvalidConfigException;
        }

        XeDB::beginTransaction();
        try {
            // documents 삭제
            $documentHandler = $this->document;
            Document::where('instance_id', $cpt_id)->chunk(
                100,
                function ($docs) use ($documentHandler) {
                    foreach ($docs as $doc) {
                        $documentHandler->remove($doc);
                    }
                }
            );

            // cpt 삭제
            $this->dfHandler->destroyCpt($cpt_id);

            // remove dyFac config
            $this->dfConfigHandler->removeConfig($config);

            // 연결된 dynamic field 제거
            foreach($this->dfConfigHandler->getDynamicFields($config) as $config){
                $this->dynamicField->drop($config);
            }

            //relate cpt 와 df_cpt_taxonomy 와 df_taxonomy 에서 삭제

        }catch (\Exception $e) {
            XeDB::rollback();

            throw $e;
        }
        XeDB::commit();
    }
}
