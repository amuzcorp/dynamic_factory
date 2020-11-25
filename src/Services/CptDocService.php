<?php
namespace Overcode\XePlugin\DynamicFactory\Services;

use Overcode\XePlugin\DynamicFactory\Exceptions\NotFoundDocumentException;
use Overcode\XePlugin\DynamicFactory\Models\CptDocument;
use Xpressengine\Config\ConfigEntity;
use Xpressengine\Http\Request;
use Xpressengine\User\UserInterface;

class CptDocService
{
    public function getItems(Request $request, ConfigEntity $config, $id = null)
    {
        $model = CptDocument::division($config->get('cpt_id'));
        $query = $model->where('instance_id', $config->get('cpt_id'));

        $paginate = $query->paginate(10)->appends($request->except('page'));
        return $paginate;
    }

    public function getItem($id, UserInterface $user, ConfigEntity $config, $force = false)
    {
        $item = CptDocument::division($config->get('cpt_id'))->find($id);

        if ($item === null) {
            throw new NotFoundDocumentException;
        }

        return $item;
    }

    public function getFieldTypes(ConfigEntity $config)
    {
        $configHandler = app('overcode.df.configHandler');
        return (array)$configHandler->getDynamicFields($config);
    }
}
