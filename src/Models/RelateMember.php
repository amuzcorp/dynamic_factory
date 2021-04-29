<?php
namespace Overcode\XePlugin\DynamicFactory\Models;

use Illuminate\Database\Eloquent\Model;

class RelateMember extends Model
{
    protected $table = 'field_dynamic_factory_relate_member';

    public $timestamps = false;

    protected $fillable = ['field_id', 'target_id', 'group', 'user_id'];
}
