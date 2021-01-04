<?php

namespace Overcode\XePlugin\DynamicFactory\Exceptions;

class RequireTaxoIdException extends DynamicFactoryException
{
    protected $message = '사용할 카테고리를 1개 이상 선택해주세요.';
}
