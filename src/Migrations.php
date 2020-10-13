<?php

namespace Overcode\XePlugin\DynamicFactory;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Migrations
{
    const META_TABLE_NAME = 'df_meta_data';

    public function checkInstalled()
    {
        if ($this->checkExistMetaTable() === false) {
            return false;
        }
    }

    public function install()
    {
        if ($this->checkExistMetaTable() === false) {
            $this->createMetaTable();
        }
    }

    protected function checkExistMetaTable()
    {
        return Schema::hasTable(self::META_TABLE_NAME);
    }

    protected function createMetaTable()
    {
        Schema::create(self::META_TABLE_NAME, function (Blueprint $table) {
            $table->string('id', 36);

            $table->string('df_id', 36);
            $table->string('type');
            $table->text('meta_data');

            $table->index(['df_id', 'type']);
        });
    }

}
