<?php

namespace Overcode\XePlugin\DynamicFactory\Handlers;

use Overcode\XePlugin\DynamicFactory\Plugin;
use Xpressengine\Document\DocumentHandler;

class DynamicFactoryDocumentHandler extends DocumentHandler
{
    public function store($attributes)
    {
        $cpt_id = $attributes['cpt_id'];

        $attributes['instance_id'] = $cpt_id;
//        $attributes['type'] = Plugin::getId();
        $attributes['type'] = $cpt_id;

        return parent::add($attributes);
    }
}
