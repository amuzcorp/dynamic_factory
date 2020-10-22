<?php

namespace Overcode\XePlugin\DynamicFactory\Handlers;

class DynamicFactoryTaxonomyHandler
{
    const TAXONOMY_CONFIG_NAME = 'taxonomy';

    const TAXONOMY_ITEM_CONFIG_NAME = 'taxonomyItem';

    const TAXONOMY_ITEM_ID_ATTRIBUTE_NAME_PREFIX = 'taxonomy_item_id_';

    protected $categoryHandler;

    protected $dfConfigHandler;

    public function __construct()
    {
        $this->categoryHandler = app('xe.category');
        $this->dfConfigHandler = app('overcode.df.configHandler');
    }

    public function createTaxonomy($inputs)
    {
        // Todo slug 중복 체크
        // Todo taxonomy 중복 체크

        $taxonomyItem = $this->categoryHandler->createCate($inputs);

        $taxonomyCptIdConfigName = $this->dfConfigHandler->getConfigName($inputs['cpt_id']);

        // TODO 사용할 카테고리 여러개 넣을수 있게
        $newInput = [
            //'useCategories' => [] get 해온 배열에 add
            'categoryIds' => [ $taxonomyItem->id ]
        ];

        $this->dfConfigHandler->addConfig($newInput, $taxonomyCptIdConfigName);

        return $taxonomyItem;
    }

}
