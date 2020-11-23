<?php
namespace Overcode\XePlugin\DynamicFactory\Services;

use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use Xpressengine\Config\ConfigEntity;
use Xpressengine\Http\Request;

class CptDocService
{
    public function getItems(Request $request, ConfigEntity $config, $id = null)
    {
        $model = CptDocument::division($config->get('cpt_id'));
        $query = $model->where('instance_id', $config->get('cpt_id'));

        $paginate = $query->paginate(10)->appends($request->except('page'));
        return $paginate;
    }
}
