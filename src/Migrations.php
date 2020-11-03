<?php

namespace Overcode\XePlugin\DynamicFactory;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Migrations
{
    const CPT_TABLE_NAME = 'df_cpts';
    const CATEGORY_EXTRA_TABLE_NAME = 'df_category_extra';
    const CPT_TAXONOMY_TABLE_NAME = 'df_cpt_taxonomy';
    const TAXONOMY_TABLE_NAME = 'df_taxonomy';

    public function checkInstalled()
    {
        if ($this->checkExistCptTable() === false) return false;
        if ($this->checkExistCategoryExtraTable() === false) return false;
        if ($this->checkExistCptTaxTable() === false) return false;
        if ($this->checkExistTaxonomyTable() === false) return false;
    }

    public function install()
    {
        if ($this->checkExistCptTable() === false) $this->createCptTable();
        if ($this->checkExistCategoryExtraTable() === false) $this->createCategoryExtraTable();
        if ($this->checkExistCptTaxTable() === false) $this->createCptTaxTable();
        if ($this->checkExistTaxonomyTable() === false) $this->createTaxonomyTable();
    }

    protected function checkExistCptTable()
    {
        return Schema::hasTable(self::CPT_TABLE_NAME);
    }

    protected function checkExistCategoryExtraTable()
    {
        return Schema::hasTable(self::CATEGORY_EXTRA_TABLE_NAME);
    }

    protected function checkExistCptTaxTable()
    {
        return Schema::hasTable(self::CPT_TAXONOMY_TABLE_NAME);
    }

    protected function checkExistTaxonomyTable()
    {
        return Schema::hasTable(self::TAXONOMY_TABLE_NAME);
    }

    protected function createCptTable()
    {
        Schema::create(self::CPT_TABLE_NAME, function (Blueprint $table) {
            $table->engine = "InnoDB";

            $table->bigIncrements('id');
            $table->string('site_key', 50);
            $table->string('cpt_id', 36);   //documents 에서 instance_id 로 사용
            $table->string('cpt_name');
            $table->string('menu_name');
            $table->integer('menu_order');
            $table->string('menu_path');
            $table->string('slug');
            $table->boolean('has_archive');
            $table->string('description');
//            $table->text('sections');
            $table->text('labels');

            $table->unique('slug');
        });
    }
    protected function createCategoryExtraTable()
    {
        Schema::create(self::CATEGORY_EXTRA_TABLE_NAME, function (Blueprint $table) {
            $table->engine = "InnoDB";

            $table->integer('category_id');
            $table->string('slug');
            $table->string('template', 50);
        });
    }

    protected function createCptTaxTable()
    {
        Schema::create(self::CPT_TAXONOMY_TABLE_NAME, function (Blueprint $table) {
            $table->engine = "InnoDB";

            $table->string('cpt_id');
            $table->integer('category_id');
        });
    }

    protected function createTaxonomyTable()
    {
        Schema::create(self::TAXONOMY_TABLE_NAME, function (Blueprint $table) {
            $table->engine = "InnoDB";

            $table->string('target_id');
            $table->integer('category_id');
            $table->integer('item_id');
        });
    }

}
