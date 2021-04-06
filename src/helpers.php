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

            if($type == 'word') {
                $arr[] = $category_item->word;
            }else {
                $arr[] = $category_item;
            }
        }

        return $arr;
    }
}
