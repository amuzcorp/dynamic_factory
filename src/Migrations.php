<?php

namespace Overcode\XePlugin\DynamicFactory;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Migrations
{
    // 현재 플러그인에서 사용 되는 SLUG 는 CPT_SLUG, CATEGORY_SLUG, CPT_DOCUMENT_SLUG 이다.
    const CPTS = 'df_cpts';                     // CPT 정보를 저장
    const CATEGORY_EXTRA = 'df_category_extra'; // xe 카테고리의 확장 정보를 저장
    const CPT_TAXONOMY = 'df_cpt_taxonomy';     // CPT 에서 사용하는 CATEGORY_ID 를 저장 n:n
    const TAXONOMY = 'df_taxonomy';             // CPT 에서 생성한 Document 의 Category 를 저장
    const SLUG = 'df_slug';                     // CPT 에성 생성한 Document 의 Slug 를 저장
    const THUMBS = 'df_thumbs';                 // CPT 에서 생성한 Document 의 Thumbnail 를 저장
    const FAVORITES = 'df_favorites';                 // CPT 에서 생성한 Document 의 Thumbnail 를 저장

    public function checkInstalled()
    {
        if (Schema::hasTable(self::CPTS) === false) return false;
        if (Schema::hasTable(self::CATEGORY_EXTRA) === false) return false;
        if (Schema::hasTable(self::CPT_TAXONOMY) === false) return false;
        if (Schema::hasTable(self::TAXONOMY) === false) return false;
        if (Schema::hasTable(self::SLUG) === false) return false;
        if (Schema::hasTable(self::THUMBS) === false) return false;
        if (Schema::hasTable(self::FAVORITES) === false) return false;
    }

    public function install()
    {
        if (Schema::hasTable(self::CPTS) === false) {
            Schema::create(self::CPTS, function (Blueprint $table) {
                $table->engine = "InnoDB";

                $table->bigIncrements('id');
                $table->string('site_key', 50);
                $table->string('cpt_id', 36);   //documents 에서 instance_id 로 사용
                $table->string('cpt_name');
                $table->string('menu_name');
                $table->integer('menu_order');
                $table->string('menu_path');
                $table->string('description')->nullable();
                $table->string('use_comment', 1)->default('N')->nullable();
                $table->string('show_admin_comment', 1)->default('N')->nullable();
                $table->text('labels');

                $table->unique('cpt_id');
            });
        }

        if (Schema::hasTable(self::CATEGORY_EXTRA) === false) {
            Schema::create(self::CATEGORY_EXTRA, function (Blueprint $table) {
                $table->engine = "InnoDB";

                $table->string('site_key', 50);
                $table->integer('category_id');
                $table->string('slug');
                $table->string('template', 50);

                $table->primary('category_id');
            });
        }

        if (Schema::hasTable(self::CPT_TAXONOMY) === false) {
            Schema::create(self::CPT_TAXONOMY, function (Blueprint $table) {
                $table->engine = "InnoDB";

                $table->string('site_key', 50);
                $table->string('cpt_id');
                $table->integer('category_id');
            });
        }

        if (Schema::hasTable(self::TAXONOMY) === false) {
            Schema::create(self::TAXONOMY, function (Blueprint $table) {
                $table->engine = "InnoDB";

                $table->increments('id');

                $table->string('target_id', 36);
                $table->integer('category_id');
                $table->text('item_ids');
            });
        }

        if (Schema::hasTable(self::SLUG) === false) {
            Schema::create(self::SLUG, function (Blueprint $table) {
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

        if (Schema::hasTable(self::THUMBS) === false) {
            Schema::create(self::THUMBS, function (Blueprint $table) {
                $table->engine = "InnoDB";

                $table->string('target_id', 36);
                $table->string('df_thumbnail_file_id', 255);
                $table->string('df_thumbnail_external_path', 255);
                $table->string('df_thumbnail_path', 255);

                $table->primary(array('target_id'));
            });
        }

        if (Schema::hasTable(self::FAVORITES) === false) {
            Schema::create(self::FAVORITES, function (Blueprint $table) {
                $table->engine = "InnoDB";

                $table->bigIncrements('favorite_id')->comment('favorite 아이디');
                $table->string('target_id', 36)->comment('대상 아이디');
                $table->string('user_id', 36)->comment('유저 아이디');

                $table->primary(array('target_id'));
            });
        }
    }

    /**
     * check updated
     *
     * @param null $installedVersion installed version
     *
     * @return bool
     */
    public function checkUpdated($installedVersion = null)
    {
        if(Schema::hasColumn(self::CPTS, 'use_comment') == false) return false;
    }


    /**
     * run update
     *
     * @param null $installedVersion installed version
     *
     * @return void
     */
    public function update($installedVersion = null)
    {
        if(Schema::hasColumn(self::CPTS, 'use_comment') == false){
            Schema::table(self::CPTS, function (Blueprint $table) {
                $table->string('use_comment',1)->nullable()->default('N');
                $table->string('show_admin_comment',1)->nullable()->default('N');
            });
        }
    }

    public function dropTables()
    {
        Schema::dropIfExists(self::CPTS);
        Schema::dropIfExists(self::CATEGORY_EXTRA);
        Schema::dropIfExists(self::CPT_TAXONOMY);
        Schema::dropIfExists(self::TAXONOMY);
        Schema::dropIfExists(self::SLUG);
        Schema::dropIfExists(self::THUMBS);
    }

}
