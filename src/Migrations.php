<?php

namespace Overcode\XePlugin\DynamicFactory;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Migrations
{
    const CPT_TABLE_NAME = 'df_cpt';
    const LABEL_TABLE_NAME = 'df_label';

    public function checkInstalled()
    {
        if ($this->checkExistCptTable() === false) return false;
        if ($this->checkExistLabelTable() === false) return false;
    }

    public function install()
    {
        if ($this->checkExistCptTable() === false) $this->createCptTable();
        if ($this->checkExistLabelTable() === false) $this->createLabelTable();
    }

    protected function checkExistCptTable()
    {
        return Schema::hasTable(self::CPT_TABLE_NAME);
    }

    protected function checkExistLabelTable()
    {
        return Schema::hasTable(self::LABEL_TABLE_NAME);
    }

    protected function createCptTable()
    {
        Schema::create(self::CPT_TABLE_NAME, function (Blueprint $table) {
            $table->string('id', 36);

            $table->increments('menu_id');
            $table->string('obj_name');
            $table->string('menu_name');
            $table->string('menu_order');
            $table->string('description');
            $table->string('slug');
            $table->string('editor');
            $table->text('edit_section');
            $table->string('archive_slug');

            $table->timestamps();

            $table->unique('slug');
        });
    }

    protected function createLabelTable()
    {
        Schema::create(self::LABEL_TABLE_NAME, function (Blueprint $table) {
            $table->string('target_id', 36);

            $table->string('new_add');          // 새로 추가
            $table->string('new_add_obj');      // 새 항목 추가
            $table->string('obj_edit');         // 항목 편집
            $table->string('new_obj');          // 새 항목
            $table->string('obj_search');       // 항목 검색
            $table->string('no_search');        // 찾을 수 없음
            $table->string('no_trash');         // 휴지통에서 찾을 수 없음
            $table->string('parent_txt');       // 상위 항목 설명
            $table->string('all_obj');          // 모든 항목
            $table->string('here_title_input'); // 여기에 제목 입력

            $table->primary('target_id');
        });
    }

}
