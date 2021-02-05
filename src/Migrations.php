<?php

namespace Overcode\XePlugin\DynamicFactory;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Migrations
{
    // 현재 플러그인에서 사용 되는 SLUG 는 CPT_SLUG, CATEGORY_SLUG, CPT_DOCUMENT_SLUG 이다.

    // CPT 정보를 저장
    const CPT_TABLE_NAME = 'df_cpts';

    // xe 카테고리의 확장 정보를 저장
    const CATEGORY_EXTRA_TABLE_NAME = 'df_category_extra';

    // CPT 에서 사용하는 CATEGORY_ID 를 저장 n:n
    const CPT_TAXONOMY_TABLE_NAME = 'df_cpt_taxonomy';

    // CPT 에서 생성한 Document 의 Category 를 저장
    const CPT_DOCUMENT_TAXONOMY_TABLE_NAME = 'df_taxonomy';

    // CPT 에성 생성한 Document 의 Slug 를 저장
    const CPT_DOCUMENT_SLUG_TABLE_NAME = 'df_slug';

    public function checkInstalled()
    {
        if ($this->checkExistCptTable() === false) return false;
        if ($this->checkExistCategoryExtraTable() === false) return false;
        if ($this->checkExistCptTaxTable() === false) return false;
        if ($this->checkExistCptDocumentTaxTable() === false) return false;
        if ($this->checkExistCptDocumentSlugTable() === false) return false;
    }

    public function install()
    {
        if ($this->checkExistCptTable() === false) $this->createCptTable();
        if ($this->checkExistCategoryExtraTable() === false) $this->createCategoryExtraTable();
        if ($this->checkExistCptTaxTable() === false) $this->createCptTaxTable();
        if ($this->checkExistCptDocumentTaxTable() === false) $this->createCptDocumentTaxTable();
        if ($this->checkExistCptDocumentSlugTable() === false) $this->createCptDocumentSlugTable();
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

    protected function checkExistCptDocumentTaxTable()
    {
        return Schema::hasTable(self::CPT_DOCUMENT_TAXONOMY_TABLE_NAME);
    }

    protected function checkExistCptDocumentSlugTable()
    {
        return Schema::hasTable(self::CPT_DOCUMENT_SLUG_TABLE_NAME);
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
            $table->string('description')->nullable();
            $table->text('labels');

            $table->unique('cpt_id');
        });
    }
    protected function createCategoryExtraTable()
    {
        Schema::create(self::CATEGORY_EXTRA_TABLE_NAME, function (Blueprint $table) {
            $table->engine = "InnoDB";

            $table->integer('category_id');
            $table->string('slug');
            $table->string('template', 50);

            $table->primary('category_id');
        });
    }

    protected function createCptTaxTable()
    {
        Schema::create(self::CPT_TAXONOMY_TABLE_NAME, function (Blueprint $table) {
            $table->engine = "InnoDB";

            $table->string('site_key', 50);
            $table->string('cpt_id');
            $table->integer('category_id');
        });
    }

    protected function createCptDocumentTaxTable()
    {
        Schema::create(self::CPT_DOCUMENT_TAXONOMY_TABLE_NAME, function (Blueprint $table) {
            $table->engine = "InnoDB";

            $table->increments('id');

            $table->string('target_id', 36);
            $table->integer('category_id');
            $table->text('item_ids');
        });
    }

    protected function createCptDocumentSlugTable()
    {
        Schema::create(self::CPT_DOCUMENT_SLUG_TABLE_NAME, function (Blueprint $table) {
            $table->engine = "InnoDB";

            $table->increments('id');

            $table->string('target_id', 36);
            $table->string('instance_id', 36);
            $table->string('slug');
            $table->string('title');

            $table->unique('slug');
            $table->index('title');
            $table->index('target_id');
        });
    }

    public function dropTables()
    {
        Schema::drop(self::CPT_TABLE_NAME);
        Schema::drop(self::CATEGORY_EXTRA_TABLE_NAME);
        Schema::drop(self::CPT_TAXONOMY_TABLE_NAME);
        Schema::drop(self::CPT_DOCUMENT_TAXONOMY_TABLE_NAME);
        Schema::drop(self::CPT_DOCUMENT_SLUG_TABLE_NAME);
    }

}
