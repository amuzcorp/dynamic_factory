<?php
namespace Overcode\XePlugin\DynamicFactory\Models;

use Illuminate\Database\Eloquent\Model;

class SuperRelate extends Model
{
    protected $table = 'df_super_relate';

    public $timestamps = false;

    protected $fillable = ['field_id', 's_id', 's_group', 's_type', 't_id', 't_group', 't_type'];
}
