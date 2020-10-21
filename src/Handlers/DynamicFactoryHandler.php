<?php

namespace Overcode\XePlugin\DynamicFactory\Handlers;

use Overcode\XePlugin\DynamicFactory\Models\Cpt;
use XeSite;

class DynamicFactoryHandler
{
    protected $reserved = [];

    protected $defaultLabels = [
        'new_add' => '새로 추가',
        'new_add_cpt' => '새 %s 추가',
        'cpt_edit' => 'Edit %s',
        'new_cpt' => '새 %s',
        'cpt_view' => '%s 보기',
        'cpt_search' => '%s 검색',
        'no_search' => '%s을(를) 찾을 수 없음',
        'no_trash' => '휴지통에서 %s을(를) 찾을 수 없음',
        'parent_txt' => '상위 텍스트',
        'all_cpt' => '모든 항목',
        'here_title' => '여기에 제목 입력'
    ];

    public function getDefaultLabels()
    {
        return $this->defaultLabels;
    }


    public function setReserved($slug)
    {
        if (is_array($slug) === true) {
            $this->reserved = array_merge($this->reserved, $slug);
        } else {
            $this->reserved[] = $slug;
        }
    }

    public function convert($title, $slug = null)
    {
        if ($slug !== null) {
            $title = $slug;
        }

        $title = trim($title);
        $title = str_replace(' ', '-', $title);

        $slug = '';
        $len = mb_strlen($title);
        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($title, $i, 1);
            $code = $this->utf8Ord($ch);

            if (($code <= 47 && $code !== 45) ||
                ($code >= 58 && $code <= 64) ||
                ($code >= 91 && $code <= 96) ||
                ($code >= 123 && $code <= 127)) {
                continue;
            }

            $slug .= $ch;
        }

        $slug = str_replace('--', '-', $slug);

        return $slug;
    }

    public function utf8Ord($ch)
    {
        $len = strlen($ch);
        if ($len <= 0) {
            return false;
        }
        $h = ord($ch[0]);
        if ($h <= 0x7F) {
            return $h;
        }
        if ($h < 0xC2) {
            return false;
        }
        if ($h <= 0xDF && $len>1) {
            return ($h & 0x1F) <<  6 | (ord($ch[1]) & 0x3F);
        }
        if ($h <= 0xEF && $len>2) {
            return ($h & 0x0F) << 12 | (ord($ch[1]) & 0x3F) << 6 | (ord($ch[2]) & 0x3F);
        }
        if ($h <= 0xF4 && $len>3) {
            return ($h & 0x0F) << 18 | (ord($ch[1]) & 0x3F) << 12 | (ord($ch[2]) & 0x3F) << 6 | (ord($ch[3]) & 0x3F);
        }
        return false;
    }

    public function store_cpt($inputs)
    {
        $newCpt = new Cpt();
        $newCpt->fill([
            'site_key' => XeSite::getCurrentSiteKey(),
            'cpt_id' => 'df_' . $newCpt->getNextId(),
            'cpt_name' => $inputs['cpt_name'],
            'menu_name' => $inputs['menu_name'],
            'menu_order' => $inputs['menu_order'] ?? '500',
            'slug' => $inputs['slug'],
            'description' => $inputs['description'] ?? '',
            'editor' => $inputs['editor'] ?? '',
            'sections' => $inputs['sections'] ?? '',
            'options' => $inputs['options'] ?? '',
            'labels' => $inputs['labels'] ?? ''
        ]);

        $newCpt->save();

        return $newCpt;
    }

    public function update_cpt($inputs)
    {
        $cpt = Cpt::find($inputs['cpt_id']);

        $cpt->fill([
            'cpt_name' => $inputs['cpt_name'],
            'menu_name' => $inputs['menu_name'],
            'menu_order' => $inputs['menu_order'] ?? '500',
            'slug' => $inputs['slug'],
            'description' => $inputs['description'] ?? '',
            'editor' => $inputs['editor'] ?? '',
            'sections' => $inputs['sections'] ?? '',
            'options' => $inputs['options'] ?? '',
            'labels' => $inputs['labels'] ?? ''
        ]);

        $cpt->save();

        return $cpt;
    }

    public function getItems()
    {
        return Cpt::all();
    }

    public function getItem($cpt_id)
    {
        return Cpt::find($cpt_id);
    }
}
