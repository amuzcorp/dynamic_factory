<?php
/**
 * TaxonomySelect
 *
 * PHP version 7
 *
 * @category    DynamicFactory
 * @package     Overcode\XePlugin\DynamicFactory
 * @author      OVERCODE <overcode@amuz.co.kr>
 * @copyright   2020 Copyright Amuz Corp. <http://amuz.co.kr>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        http://amuz.co.kr
 */
namespace Overcode\XePlugin\DynamicFactory\Components\UIObjects\TaxoSelect;

use Xpressengine\Category\Models\CategoryItem;
use Xpressengine\UIObject\AbstractUIObject;
use View;

/**
 * TaxonomySelect
 *
 * DIV 방식 select
 *
 * ## 사용법
 *
 * ```php
 * uio('uiobject/df@taxo_select', [
 *      'name' => 'selectNameAttribute',
 *      'label' => 'label',
 *      'template' => 'template',
 *      'value' => 'value',
 *      'items' => [
 *          ['value' => 'value1', 'text' => 'text1'],
 *          ['value' => 'value2', 'text' => 'text2'],
 *      ],
 * ]);
 * ```
 *
 * @category    DynamicFactory
 * @package     Overcode\XePlugin\DynamicFactory
 * @author      OVERCODE <overcode@amuz.co.kr>
 * @copyright   2020 Copyright Amuz Corp. <http://amuz.co.kr>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        http://amuz.co.kr
 */
class TaxoSelectUIObject extends AbstractUIObject
{
    protected static $loaded = false;

    protected static $id = 'uiobject/df@taxo_select';

    public function render()
    {
        $args = $this->arguments;

        if (empty($args['name'])) {
            throw new \Exception;
        }
        if (empty($args['items'])) {
            $args['items'] = [];
        }
        if (empty($args['label'])) {
            $args['label'] = xe_trans('xe::select');
        }

        if (empty($args['default'])) {
            $args['default'] = '';
        }


        if (!isset($args['value']) || $args['value'] === '') {
            $args['value'] = '';
            $args['text'] = '';
        } else {
            $selectedItem = self::getSelectedItem($args['items'], $args['value'], $args['template']);
            if($args['template'] !== 'depth') {
                $args['selectedItem'] = $selectedItem;
                if ($selectedItem) {
                    $args['text'] = isset($selectedItem['text']) ? $selectedItem['text'] : '';
                } else {
                    $args['value'] = '';
                    $args['text'] = '';
                }
            } else {
                $args['selectedItem'] = $selectedItem;
                if ($selectedItem) {
                    $args['text'] = isset($selectedItem['text']) ? $selectedItem['text'] : '';
                } else {
                    $args['value'] = '';
                    $args['text'] = '';
                }
            }
        }

        $args['scriptInit'] = false;
        if (self::$loaded === false) {
            self::$loaded = true;

            $args['scriptInit'] = true;
        }

        $blade = 'taxoSelect';  // blade file name

        // 멀티 select 일때
        if($args['template'] === 'multi_select') {
            $blade = 'taxoMultiSelect';
        }

        if($args['template'] === 'depth') {
            $blade = 'taxoDepthSelect';

            $args['childItem'] = [];
            if(isset($args['selectedItem'])) {
                if (count($args['selectedItem']) > 0) {
                    $sub_categories = [];
                    $selectedItemCollection = [];
                    foreach ($args['value'] as $value) {
                        $selectedItemCollection[$value] = CategoryItem::where('id', $value)->first();
                    }
                    $args['selectedItemCollection'] = $selectedItemCollection;
                    foreach ($args['selectedItem'] as $key => $item) {
                        if ($item['child'] == true) {
                            $getItems = CategoryItem::where('parent_id', $item['value'])->get();

                            foreach ($getItems as $getItem) {
                                $getItem['child'] = false;
                                if (CategoryItem::where('parent_id', $getItem->id)->count() > 0) {
                                    $getItem['child'] = true;
                                }
                            }

                            $sub_categories[$item['value']] = $getItems;
                        }
                    }

                    $args['childItem'] = $sub_categories;
                }
            } else {
                $args['selectedItem'] = [];
                $args['selectedItemCollection'] = [];
            }
        }
        return View::make('dynamic_factory::components/UIObjects/TaxoSelect/'. $blade, $args)->render();
    }

    /**
     * @param $items
     * @param array|$selectedValue
     * @param $template
     * @return array|bool
     */
    private static function getSelectedItem ($items, $selectedValue, $template)
    {
        if($template === 'multi_select') {
            // 멀티 선택일때
            $selectItems = [];

            foreach ($items as $item) {
                if (in_array($item['value'], $selectedValue)) {
                    // category item id 를 key 로 사용한다.
                    $selectItems[$item['value']] = [
                        'value' => $item['value'],
                        'text' => $item['text']
                    ];
                }

                if (self::hasChildren($item)) {
                    $selectedItem = self::getSelectedItem(self::getChildren($item), $selectedValue, $template);
                    if ($selectedItem) {
                        $selectItems = array_merge($selectItems, $selectedItem);
                    }
                }
            }

            return $selectItems;

        } else if($template === 'depth') {

            // 멀티 선택일때
            $selectItems = [];
            foreach ($items as $item) {
                if (in_array($item['value'], $selectedValue)) {
                    // category item id 를 key 로 사용한다.
                    $selectItems[$item['value']] = [
                        'value' => $item['value'],
                        'text' => $item['text'],
                        'child' => self::hasChildren($item),
                        'parent' => CategoryItem::where('id', $item['value'])->first()->parent_id ?: 0
                    ];
                }
                if (self::hasChildren($item)) {
                    $selectedItem = self::getSelectedItem(self::getChildren($item), $selectedValue, $template);
                    if ($selectedItem) {
                        $selectItems = array_merge($selectItems, $selectedItem);
                    }
                }
            }
            return $selectItems;

        } else {
            // 단일 선택일때
            if(is_array($selectedValue)) {
                $selectedValue = $selectedValue[0];
            }

            foreach ($items as $item) {
                if ($item['value'] == $selectedValue) {
                    return [
                        'value' => $item['value'],
                        'text' => $item['text']
                    ];
                }

                if (self::hasChildren($item)) {
                    $selectedItem = self::getSelectedItem(self::getChildren($item), $selectedValue, $template);
                    if ($selectedItem) {
                        return $selectedItem;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param array $item
     * @return boolean
     */
    public static function hasChildren ($item)
    {
        return array_has($item, 'children');
    }

    /**
     * @param array $item
     * @return array
     */
    public static function getChildren ($item)
    {
        if (array_has($item, 'children')) {
            return array_get($item, 'children');
        }

        return [];
    }

    public static function renderList ($items, $value = null)
    {
        $args = [
            'items' => $items,
            'selectedItemValue' => $value
        ];

        return View::make('dynamic_factory::components/UIObjects/TaxoSelect/taxoSelectItem', $args)->render();
    }

    public static function renderMultiList ($items, $value = null)
    {
        $args = [
            'items' => $items,
            'selectedItemValue' => $value
        ];
        return View::make('dynamic_factory::components/UIObjects/TaxoSelect/taxoMultiSelectItem', $args)->render();
    }

    public static function renderDepthList ($items, $value = null)
    {
        $args = [
            'items' => $items,
            'selectedItemValue' => $value
        ];

        return View::make('dynamic_factory::components/UIObjects/TaxoSelect/taxoDepthSelectItem', $args)->render();
    }

}
