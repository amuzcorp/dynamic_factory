<?php

namespace Overcode\XePlugin\DynamicFactory\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class NotFoundDocumentException extends HttpCptException
{
    protected $statusCode = Response::HTTP_GONE;
    protected $message = 'NotFoundDocumentException 발생';
}
