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
        $attributes['type'] = $cpt_id;

        return parent::add($attributes);
    }

    public function update($doc, $inputs)
    {
        $attributes = $doc->getAttributes();

        foreach ($inputs as $name => $value) {
            if (array_key_exists($name, $attributes)) {
                $doc->{$name} = $value;
            }
        }

        return parent::put($doc);
    }
}
