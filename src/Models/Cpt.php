<?php
namespace Overcode\XePlugin\DynamicFactory\Models;

use Xpressengine\Database\Eloquent\DynamicModel;
use XeDB;
class Cpt extends DynamicModel
{
    protected $table = 'df_cpts';

    protected $fillable = ['site_key', 'cpt_id', 'cpt_name', 'menu_name', 'menu_order', 'slug', 'description', 'sections', 'options', 'labels'];

    protected $casts = ['sections' => 'array', 'options' => 'array', 'labels' => 'array'];

    protected $primaryKey = 'cpt_id';

    protected $keyType = 'string';

    public $timestamps = false;

    public function getNextId() {
        $statement = XeDB::select("show table status like 'xe_".$this->table."'");
        return $statement[0]->Auto_increment ?? 1;
    }

    public function taxonomy() {
        return $this->hasMany(CptTaxonomy::class, 'cpt_id', 'cpt_id');
    }
}
