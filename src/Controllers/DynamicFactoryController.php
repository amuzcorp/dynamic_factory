<?php
namespace Overcode\XePlugin\DynamicFactory\Controllers;

use App\Http\Controllers\Controller;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryTaxonomyHandler;
use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use Overcode\XePlugin\DynamicFactory\Models\DfSlug;
use XePresenter;
use Xpressengine\Http\Request;
use Auth;

class DynamicFactoryController extends Controller
{
    public $taxonomyHandler;

    public function __construct(
        DynamicFactoryTaxonomyHandler $taxonomyHandler
    )
    {
        $this->taxonomyHandler = $taxonomyHandler;
    }

    /**
     * CPT_ID 에 해당하는 카테고리를 정리하여 json 으로 반환
     * route('dyFac.categories')
     *
     * @param Request $request
     * @return mixed
     */
    public function getCategories(Request $request)
    {
        $cpt_id = $request->get('cpt_id');

        $taxo_ids = $this->taxonomyHandler->getTaxonomyIds($cpt_id);

        $categories = [];

        foreach($taxo_ids as $taxo_id){
            $categories = array_merge($categories, $this->taxonomyHandler->getCategoryItemsTree($taxo_id));
        }

        return XePresenter::makeApi([
            'categories' => $categories
        ]);
    }

    /**
     * 문자열을 넘겨 slug 반환
     *
     * @param Request $request request
     * @return mixed
     */
    public function hasSlug(Request $request)
    {
        $slugText = DfSlug::convert('', $request->get('slug'));
        $slug = DfSlug::make($slugText, $request->get('id'));

        return XePresenter::makeApi([
            'slug' => $slug,
        ]);
    }

    /**
     * Search Document
     *
     * @param string\null $keyword keyword
     * @return \Xpressengine\Presenter\Presentable
     */
    public function docSearch($keyword = null, Request $request)
    {
        if ($keyword === null) {
            return XePresenter::makeApi([]);
        }

        $query = CptDocument::where('title', 'like', '%'.$keyword.'%');
        if($request->get('cn') !== null) {
            $field_config = app('xe.config')->get($request->get('cn'));
            if($field_config !== null) {
                // 선택한 타입들의 글만 표시
                if($field_config->get('r_instance_id')) $query->where('instance_id', $field_config->get('r_instance_id'));

                // 자신이 작성한 글만 옵션 선택시
                $user_id = auth()->user()->getId();
                if($field_config->get('author') == 'author' && $user_id != null) $query->where('user_id',$user_id);
            }
        }

        $matchedDocumentList = $query->paginate(null, ['id','title'])->items();

        return XePresenter::makeApi($matchedDocumentList);
    }

    public function rendingCptDocument(Request $request) {

        \XeTheme::selectBlankTheme();

        $status = $request->status;
        $after_work = $request->after_work;
        $result = [];
        if($request->result) {
            $result = $request->result;
        }

        return \XePresenter::make('dynamic_factory::views.documents.document_write_widget_result', compact('status', 'after_work', 'result'));
    }

}
