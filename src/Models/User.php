<?php
namespace Overcode\XePlugin\DynamicFactory\Models;

use Overcode\XePlugin\DynamicFactory\Components\DynamicFields\SuperRelate\SuperRelateField;
use Xpressengine\User\Models\User as XeUser;

class User  extends XeUser {

    public function hasDocument($field_id, $use_dynamic = true)
    {
        $tableName = SuperRelateField::TABLE_NAME;

        $query = $this->belongsToMany(CptDocument::class, $tableName, 's_id', 't_id')->where($tableName.'.field_id', $field_id);
        if($use_dynamic){
            $target_group = SuperRelate::Where('field_id', $field_id)->where('s_id', $this->id)->where('s_group', 'user')->pluck('t_group')->first();

            $query->setProxyOption(['group' => $target_group, 'table' => 'documents'], false);
        }

        return $query->get();
    }

}
