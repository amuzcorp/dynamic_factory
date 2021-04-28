<?php
namespace Overcode\XePlugin\DynamicFactory\Models;

use Illuminate\Database\Eloquent\Model;

class RelateDocument extends Model
{
    protected $table = 'field_dynamic_factory_relate_document';

    public $timestamps = false;

    protected $fillable = ['field_id', 'target_id', 'group', 'r_id', 'r_group'];
}
