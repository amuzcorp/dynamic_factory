<?php
namespace Overcode\XePlugin\DynamicFactory\Services;


use Carbon\Carbon;
use Overcode\XePlugin\DynamicFactory\Components\Modules\Cpt\CptModule;
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
use XeEditor;
use Schema;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryHandler;
use Xpressengine\Category\Models\CategoryItem;
use Xpressengine\Config\ConfigEntity;
use Xpressengine\Document\DocumentHandler;
use Xpressengine\Document\Models\Document;
use Xpressengine\DynamicField\DynamicFieldHandler;
use Xpressengine\Http\Request;
use Xpressengine\Permission\Instance;
use Xpressengine\Support\Exceptions\AccessDeniedHttpException;

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

        $site_key = \XeSite::getCurrentSiteKey();
        $now = Carbon::now()->format('Y-m-d H:i:s');

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

            \DB::table('permissions')->insert([
                'site_key'=> $site_key, 'name' => CptModule::getId().'.'.$inputs['cpt_id'], 'grants' => '[]',
                'created_at' => $now, 'updated_at' => $now,
            ]);

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
        $defaultBlades = [
            'list' => 'dynamic_factory::views.documents.list',
            'create' => 'dynamic_factory::views.documents.create',
            'edit' => 'dynamic_factory::views.documents.edit'
        ];
        if(!isset($cpt->blades) || !is_array($cpt->blades)) {
            $cpt->blades = $defaultBlades;
        }else{
            $cpt->blades = array_merge($defaultBlades,$cpt->blades);
        }

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

        if (\Gate::denies('create', new Instance(app('overcode.df.permission')->name($request->cpt_id)))) {
            throw new AccessDeniedHttpException;
        }

        $inputs = $request->originExcept('_token');
        if (isset($inputs['user_id']) === false || empty($inputs['user_id'])) {
            if(auth()->check()) $inputs['user_id'] = auth()->user()->getId();
            else $inputs['user_id'] = '';
        }

        if (isset($inputs['writer']) === false || empty($inputs['writer'])) {
            if(auth()->check()) $inputs['writer'] = auth()->user()->getDisplayName();
            else $inputs['writer'] = 'Guest';
        }

        XeDB::beginTransaction();
        try {
            $cpt_id = $request->cpt_id;
            if(array_get($inputs, 'cpt_id') == null) {
                $inputs['cpt_id'] = $cpt_id;
            }

            /** @var DocumentHandler $documentConfigHandler */
            $documentConfigHandler = app('xe.document');
            $config = $documentConfigHandler->getConfigHandler()->get($cpt_id);
            if(!$config){
                $documentConfigHandler->createInstance($cpt_id, ['instanceId' => $cpt_id, 'group' => Plugin::getId() . '_' . $cpt_id, 'siteKey' => XeSite::getCurrentSiteKey()]);
            }
            $editor = XeEditor::get($cpt_id);
            $inputs['format'] = $editor->htmlable() ? CptDocument::FORMAT_HTML : CptDocument::FORMAT_NONE;

            // set file, tag
            $inputs['_files'] = array_get($inputs, $editor->getFileInputName(), []);
            $inputs['_hashTags'] = array_get($inputs, $editor->getTagInputName(), []);
            $inputs['_coverId'] = array_get($inputs, $editor->getCoverInputName(), []);

            $document = $this->dfDocumentHandler->store($inputs);

            $this->dfTaxonomyHandler->storeTaxonomy($document, $inputs);

            $cptDocument = CptDocument::find($document->id);
            $this->saveSlug($cptDocument, $inputs);
        }catch (\Exception $e) {
            XeDB::rollback();

            throw $e;
        }
        XeDB::commit();

        return $cptDocument;
    }

    public function updateCptDocument(Request $request)
    {
        $inputs = $request->originExcept('_token');

        XeDB::beginTransaction();
        try {
            $cpt_id = $request->cpt_id;
            if(array_get($inputs, 'cpt_id') == null){
                $inputs['cpt_id'] = $cpt_id;
            }

            if(array_get($inputs, 'writer', '') == ''){
                unset($inputs['writer']);
            }
            if(array_get($inputs, 'user_id', '') == ''){
                unset($inputs['user_id']);
            }

            $doc = CptDocument::division($cpt_id)->find($request->get('doc_id'));

            $editor = XeEditor::get($cpt_id);
            $inputs['format'] = $editor->htmlable() ? CptDocument::FORMAT_HTML : CptDocument::FORMAT_NONE;

            // set file, tag
            $inputs['_files'] = array_get($inputs, $editor->getFileInputName(), []);
            $inputs['_hashTags'] = array_get($inputs, $editor->getTagInputName(), []);
            $inputs['_coverId'] = array_get($inputs, $editor->getCoverInputName(), []);

            $this->dfDocumentHandler->update($doc, $inputs);

            $this->dfTaxonomyHandler->updateTaxonomy($doc, $inputs);

            $this->saveSlug($doc, $inputs);

        }catch (\Exception $e) {
            XeDB::rollback();

            throw $e;
        }
        XeDB::commit();

        $doc = CptDocument::division($cpt_id)->find($request->get('doc_id'));
        return $doc;
    }

    public function getItemsWhereQuery(array $attributes, $site_key = null)  // site_key == '*' 일때는 모든 사이트
    {
        $instance_id = $attributes['cpt_id'];

        $query = CptDocument::division($instance_id)->where('instance_id', $instance_id);

        if($site_key != '*' && Schema::hasColumn('documents', 'site_key')){
            $site_key = $site_key != null ? $site_key : \XeSite::getCurrentSitekey();
            $query->where('site_key', $site_key);
        }

//        $query->visible(); // trash 가 아닌것만

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

    public function setDocumentPermission($cpt_id, $config) {
        $index = 0;
        $grants = '{';
        foreach($config as $key => $val) {
            if($val !== 'super' && $val !== 'manager' && $val !== 'user' && $val !== 'guest') continue;
            if($index !== 0) $grants = $grants.',';
            $grants = $grants.'"'.$key.'":{"rating":"'.$val.'","group":[],"user":[],"except":[],"vgroup":[]}';
            $index += 1;
        }
        $grants = $grants.'}';
        if($index === 0) $grants = '[]';

        \DB::table('permissions')->insert([
            'site_key' => \XeSite::getCurrentSiteKey(), 'name' => CptModule::getId() . '.' . $cpt_id, 'grants' => $grants,
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'), 'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }
}
