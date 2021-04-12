<?php
namespace Overcode\XePlugin\DynamicFactory\Controllers;

use App\Http\Controllers\Controller;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryHandler;
use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryTaxonomyHandler;
use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use XePresenter;
use Xpressengine\Http\Request;
use Overcode\XePlugin\DynamicFactory\Exceptions\NotFoundFavoriteHttpException;
use Overcode\XePlugin\DynamicFactory\Models\DfFavorite;
use Xpressengine\Support\Exceptions\AccessDeniedHttpException;
use Overcode\XePlugin\DynamicFactory\Exceptions\AlreadyExistFavoriteHttpException;
use Auth;
class DynamicFactoryController extends Controller
{
    public $taxonomyHandler;

    protected $dfHandler;

    public function __construct(
        DynamicFactoryTaxonomyHandler $taxonomyHandler,
        DynamicFactoryHandler $dfHandler
    )
    {
        $this->taxonomyHandler = $taxonomyHandler;
        $this->dfHandler = $dfHandler;
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

    public function favorite($id)
    {
        if (Auth::check() === false) {
            throw new AccessDeniedHttpException;
        }
        $item = app('overcode.doc.service')->getItemOnlyId($id);

        $userId = Auth::user()->getId();
        $favorite = false;
        if ($this->dfHandler->hasFavorite($item->id, $userId) === false) {
            $this->dfHandler->addFavorite($item->id, $userId);
            $favorite = true;
        } else {
            $this->dfHandler->removeFavorite($item->id, $userId);
        }

        return \XePresenter::makeApi(['favorite' => $favorite]);
    }


}
