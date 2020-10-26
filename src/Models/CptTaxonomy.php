<?php
namespace Overcode\XePlugin\DynamicFactory\Models;

use Xpressengine\Database\Eloquent\DynamicModel;

class CptTaxonomy extends DynamicModel
{
    protected $table = 'df_cpt_taxonomy';

    protected $fillable = ['cpt_id', 'category_id'];

    public $timestamps = false;
}
