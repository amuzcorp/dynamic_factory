<?php
namespace Overcode\XePlugin\DynamicFactory\Controllers;

use App\Http\Controllers\Controller;
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

    public function favorite($id)
    {
        if (Auth::check() === false) {
            throw new AccessDeniedHttpException;
        }
        $item = CptDocument::division('tour')->find($id);

        $userId = Auth::user()->getId();
        $favorite = false;
        if ($this->hasFavorite($item->id, $userId) === false) {
            $this->addFavorite($item->id, $userId);
            $favorite = true;
        } else {
            $this->removeFavorite($item->id, $userId);
        }

        return \XePresenter::makeApi(['favorite' => $favorite]);
    }

    /**
     * check has favorite
     *
     * @param string $boardId board id
     * @param string $userId  user id
     * @return bool
     */
    public function hasFavorite($DocId, $userId)
    {
        return DfFavorite::where('target_id', $DocId)->where('user_id', $userId)->exists();
    }

    /**
     * add favorite
     * @param string $df Id board id
     * @param string $userId  user id
     */
    public function addFavorite($DocId, $userId)
    {
        if ($this->hasFavorite($DocId, $userId) === true) {
            throw new AlreadyExistFavoriteHttpException;
        }

        $favorite = new DfFavorite;
        $favorite->target_id = $DocId;
        $favorite->user_id = $userId;
        $favorite->save();

        return $favorite;
    }

    /**
     * remove favorite
     *
     * @param string $boardId board id
     * @param string $userId  user id
     * @return void
     */
    public function removeFavorite($DocId, $userId)
    {
        if ($this->hasFavorite($DocId, $userId) === false) {
            throw new NotFoundFavoriteHttpException;
        }

        DfFavorite::where('target_id', $DocId)->where('user_id', $userId)->delete();
    }

}
