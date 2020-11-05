<?php

namespace Overcode\XePlugin\DynamicFactory\Interfaces;

interface Orderable
{
    public function getOrder($query, $attributes);
}
