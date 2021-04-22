<?php

if (function_exists('getMediaImageUrl') === false) {
    /**
     * 미디어 라이브러리 확장 변수의 output 을 넣으면 url 로 반환.
     * ex : getMediaImageUrl( $fieldType->getSkin()->output('slick_icon_img', $item->getAttributes()) );
     * @return array | string
     */
    function getMediaImageUrl($json_array)
    {
        if(empty($json_array) || $json_array == '"null"') return [];

        $arr = json_decode($json_array);

        $url_arr = [];

        $storage_path = Config::get('filesystems.disks.media.url');

        foreach($arr as $id) {
            $storage = XeStorage::find($id);
            $url_arr[] = $storage_path .'/'. $storage->path .'/'. $storage->filename;
        }
        return $url_arr;
    }
}

if (function_exists('df_category') === false) {
    function df_category(\Overcode\XePlugin\DynamicFactory\Models\DfTaxonomy $dfTaxonomy, $type = null) {
        $arr = [];

        foreach ($dfTaxonomy->item_ids as $item_id) {
            $category_item = app('overcode.df.taxonomyHandler')->getCategoryItem($item_id);
            if($category_item != null) {
                if ($type == 'word') {
                    $arr[] = $category_item->word;
                } else {
                    $arr[] = $category_item;
                }
            }
        }

        return $arr;
    }
}

if (function_exists('get_menu_instance_name') === false) {
    function get_menu_instance_name($instance_id) {

        if($instance_id == null) return '';

        $hasSiteKey = \Schema::hasColumn('menu_item', 'site_key');

        $menus = [];

        if($hasSiteKey) {
            $menu_items = Xpressengine\Menu\Models\MenuItem::where('site_key', \XeSite::getCurrentSiteKey())->orderBy('ordering')->get();
        }else {
            $menu_items = Xpressengine\Menu\Models\MenuItem::orderBy('ordering')->get();
        }
        foreach ($menu_items as $menu_item) {
            $menus[$menu_item->id] = $menu_item->title;
        }
        if(\XeSite::getCurrentSiteKey() == 'default') {
            $menus['admin_dashboard'] = '관리자 대시보드';
        }

        return xe_trans($menus[$instance_id]);
    }
}

if (function_exists('relate_cpt_title') === false) {
    function relate_cpt_title($json_text, $type = 'single')
    {
        if(empty($json_text) || $json_text == '"null"') return '';

        $ids = json_decode($json_text);

        if ($type == 'multi') {
            return $ids;
        }else {
            $item = \Overcode\XePlugin\DynamicFactory\Models\CptDocument::find($ids[0]);
            return $item->title;
        }
    }
}

if (function_exists('get_cpt_title') === false) {
    function get_cpt_title($id)
    {
        $item = \Overcode\XePlugin\DynamicFactory\Models\CptDocument::find($id);
        if($item == null) return '';

        return $item->title;
    }
}

if (function_exists('get_user_login_id') === false) {
    function get_user_login_id($id)
    {
        $user = \Xpressengine\User\Models\User::find($id);

        return $user != null ? $user->login_id : '';
    }
}

/*if (function_exists('get_last_explode') === false) {
    function get_last_explode($delimiter, $array)
    {
        $route_names = explode($delimiter, $array);
        $end_name = end($route_names);

        return $end_name;
    }
}*/
