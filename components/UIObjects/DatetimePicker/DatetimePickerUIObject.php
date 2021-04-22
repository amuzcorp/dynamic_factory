<?php

namespace Overcode\XePlugin\DynamicFactory\Components\UIObjects\DatetimePicker;

use Overcode\XePlugin\DynamicFactory\Plugin;
use Xpressengine\UIObject\AbstractUIObject;
use View;
use XeFrontend;

class DatetimePickerUIObject extends AbstractUIObject
{
    protected static $id = 'uiobject/df@datetime_picker';

    public function render()
    {
        $args = $this->arguments;

        $published_at = array_get($args, 'published_at');

        array_set($args, 'published_at', $published_at != null ? $published_at->toDateTimeString() : null);

        array_set($args, 'seq', $this->seq());  // 같은 uio 가 여러개 있을시 seq

        XeFrontend::js(Plugin::asset('/assets/datetimepicker/jquery.datetimepicker.full.min.js'))->load();
        XeFrontend::css(Plugin::asset('/assets/datetimepicker/jquery.datetimepicker.min.css'))->load();

        return View::make('dynamic_factory::components/UIObjects/DatetimePicker/datetime', $args);
    }
}
