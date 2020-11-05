<?php

namespace Overcode\XePlugin\DynamicFactory\Interfaces;

use Overcode\XePlugin\DynamicFactory\Models\CptDocument;

interface Jsonable
{
    public function getTypeName();

    public function getJsonData(CptDocument $cptDocument);
}
