<?php

namespace Overcode\XePlugin\DynamicFactory\Exceptions;

use Xpressengine\Support\Exceptions\HttpXpressengineException;

class HttpCptException extends HttpXpressengineException
{
    protected $message = 'HttpCptException 발생';
}
