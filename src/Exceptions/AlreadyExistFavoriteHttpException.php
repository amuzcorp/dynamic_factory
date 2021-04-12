<?php
/**
 * AlreadyExistFavoriteHttpException
 *
 * PHP version 7
 *
 * @category    Board
 * @package     Xpressengine\Plugins\Board
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2019 Copyright XEHub Corp. <https://www.xehub.io>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        https://xpressengine.io
 */
namespace Overcode\XePlugin\DynamicFactory\Exceptions;

use Overcode\XePlugin\DynamicFactory\Exceptions\HttpCptException;

/**
 * AlreadyExistFavoriteHttpException
 *
 * @category    Board
 * @package     Xpressengine\Plugins\Board
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2019 Copyright XEHub Corp. <https://www.xehub.io>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        https://xpressengine.io
 */
class AlreadyExistFavoriteHttpException extends HttpCptException
{
    protected $message = '이미 즐겨찾기 되었습니다.';
}
