<?php

namespace Overcode\XePlugin\DynamicFactory;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Migrations
{
    const META_TABLE_NAME = 'df_meta_data';
    const CPT_TABLE_NAME = 'df_cpt';

    public function checkInstalled()
    {
        if ($this->checkExistMetaTable() === false) return false;
        if ($this->checkExistCptTable() === false) return false;

    }

    public function install()
    {
        if ($this->checkExistMetaTable() === false)  $this->createMetaTable();
        if ($this->checkExistCptTable() === false)  $this->createCptTable();
    }

    protected function checkExistMetaTable()
    {
        return true;
        //return Schema::hasTable(self::META_TABLE_NAME);
    }
    protected function checkExistCptTable()
    {
        return Schema::hasTable(self::CPT_TABLE_NAME);
    }

    protected function createMetaTable()
    {
        /*Schema::create(self::META_TABLE_NAME, function (Blueprint $table) {
            $table->string('id', 36);

            $table->string('df_id', 36);
            $table->string('type');
            $table->text('meta_data');

            $table->index(['df_id', 'type']);
        });*/
    }

    protected function createCptTable()
    {
        Schema::create(self::CPT_TABLE_NAME, function (Blueprint $table) {
            $table->string('id', 36);

            $table->increments('menu_id');
            $table->string('menu_order');
            $table->string('label');
            $table->string('description');
            $table->string('slug');
            $table->string('editor');
            $table->text('edit_section');
            $table->string('archive_slug');

            $table->timestamps();

            $table->unique('slug');
        });
    }

}
