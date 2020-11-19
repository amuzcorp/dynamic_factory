<?php
namespace Overcode\XePlugin\DynamicFactory\Models;

use Xpressengine\Database\Eloquent\DynamicModel;
use XeDB;
use Xpressengine\Document\Exceptions\ValueRequiredException;

class Cpt extends DynamicModel
{
    protected $table = 'df_cpts';

    protected $fillable = ['site_key', 'cpt_id', 'cpt_name', 'menu_name', 'menu_order', 'menu_path', 'description', 'labels'];

    protected $casts = ['labels' => 'array'];

    protected $primaryKey = 'cpt_id';

    protected $keyType = 'string';

    public $timestamps = false;

    public function getNextId() {
        $statement = XeDB::select("show table status like 'xe_".$this->table."'");
        return $statement[0]->Auto_increment ?? 1;
    }
}
