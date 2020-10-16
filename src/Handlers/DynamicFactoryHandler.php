<?php

namespace Overcode\XePlugin\DynamicFactory\Handlers;

use Overcode\XePlugin\DynamicFactory\Models\Cpt;

class DynamicFactoryHandler
{
    protected $reserved = [];

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
//        $inputs['instance_id'] = Plugin::getId();

//        dd($inputs);

        $newCpt = new Cpt();
        $newCpt->fill([
            'menu_id' => 'dfz',
            'menu_order' => $inputs['menu_order'] ?? '900',
            'label' => $inputs['label'],
            'description' => $inputs['description'] ?? '',
            'slug' => $inputs['slug'],
            'editor' => $inputs['editor'] ?? '',
            'edit_section' => $inputs['edit_section'] ?? '',
            'archive_slug' => $inputs['archive_slug'] ?? ''
        ]);
        $newCpt->save();

        return $newCpt;
    }

    public function getItems()
    {
        return Cpt::all();
    }
}
