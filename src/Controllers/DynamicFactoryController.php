<?php
namespace Overcode\XePlugin\DynamicFactory\Controllers;

use App\Http\Controllers\Controller;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryTaxonomyHandler;
use XePresenter;
use Xpressengine\Http\Request;

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
}
