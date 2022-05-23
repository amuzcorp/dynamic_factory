<?php

namespace Overcode\XePlugin\DynamicFactory\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Overcode\XePlugin\DynamicFactory\Models\User as XeUser;
use Symfony\Component\HttpKernel\Exception\HttpException;
use XeDB;
use XePresenter;
use Xpressengine\Http\Request;
use Xpressengine\Support\Exceptions\InvalidArgumentHttpException;
use Xpressengine\User\Exceptions\EmailAlreadyExistsException;
use Xpressengine\User\Exceptions\EmailNotFoundException;
use Xpressengine\User\Models\User;
use Xpressengine\User\Rating;
use Xpressengine\User\Repositories\UserRepository;
use Xpressengine\User\UserException;
use Xpressengine\User\UserHandler;
use Xpressengine\User\UserInterface;

/**
 * Class UserController
 *
 * @category    Controllers
 * @package     App\Http\Controllers\User\Settings
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2020 Copyright XEHub Corp. <https://www.xehub.io>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        https://xpressengine.io
 */
class UserController extends Controller
{
    /**
     * @var UserHandler
     */
    protected $handler;

    /**
     * UserController constructor.
     *
     * @param UserHandler $handler user handler
     */
    public function __construct(UserHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Search user.
     *
     * @param Request $request request
     * @param string|null $keyword keyword
     * @return \Xpressengine\Presenter\Presentable
     */
    public function search(Request $request, $keyword = null)
    {

        $user_groups = app('xe.config')->get($request->get('cn'))->get('user_groups') ?: [];

        /** @var UserRepository $users */
        $users = $this->handler->users();

        if ($keyword === null) {
            return XePresenter::makeApi([]);
        }

        $userList = XeUser::where('display_name', 'like', '%'.$keyword.'%')->pluck('id');
        $query = $users->query()->where('display_name', 'like', '%'.$keyword.'%');

        if(is_array($user_groups) || count($user_groups) !== 0) {
            $groupInUsers = [];
            if(is_array($userList) || count($userList) !== 0)
                $groupInUsers = \XeDB::table('user_group_user')->whereIn('group_id', $user_groups)->whereIn('user_id', $userList)->groupBy('user_id')->pluck('user_id');
            $query->whereIn('id', $groupInUsers);
        }
        $matchedUserList = $query->paginate(null, ['id', 'display_name', 'email'])->items();

        return XePresenter::makeApi($matchedUserList);
    }

}
