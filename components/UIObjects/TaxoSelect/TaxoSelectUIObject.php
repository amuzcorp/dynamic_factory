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
            $selectedItem = self::getSelectedItem($args['items'], $args['value']);
            if ($selectedItem) {
                $args['text'] = $selectedItem['text'];
            } else {
                $args['value'] = '';
                $args['text'] = '';
            }
        }

        $args['scriptInit'] = false;
        if (self::$loaded === false) {
            self::$loaded = true;

            $args['scriptInit'] = true;
        }

        $blade = 'taxoSelect';

        if($args['template'] === 'multi_select') $blade = 'taxoMultiSelect';
        else if($args['template'] === 'check_list') $blade = 'taxoCheckList';
        else if($args['template'] === 'hierarchy') $blade = 'taxoHierarchy';

        return View::make('dynamic_factory::components/UIObjects/TaxoSelect/'. $blade, $args)->render();
    }

    private static function getSelectedItem ($items, $selectedValue)
    {
        foreach($items as $item) {
            if ($item['value'] == $selectedValue) {
                return [
                    'value' => $item['value'],
                    'text' => $item['text']
                ];
            }

            if (self::hasChildren($item)) {
                $selectedItem = self::getSelectedItem(self::getChildren($item), $selectedValue);
                if ($selectedItem) {
                    return $selectedItem;
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
}
