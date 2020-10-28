<?php

namespace Overcode\XePlugin\DynamicFactory\Handlers;

use Overcode\XePlugin\DynamicFactory\Plugin;
use Xpressengine\Document\DocumentHandler;

class DynamicFactoryDocumentHandler extends DocumentHandler
{
    public function store($attributes)
    {
        $attributes['instance_id'] = $attributes['cpt_id'];
        $attributes['type'] = Plugin::getId();

        return parent::add($attributes);
    }
}
