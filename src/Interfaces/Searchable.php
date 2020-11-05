<?php

namespace Overcode\XePlugin\DynamicFactory\Interfaces;

interface Searchable
{
    public function getItems($query, array $attributes);
}
