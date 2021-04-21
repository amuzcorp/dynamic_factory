<?php

namespace Overcode\XePlugin\DynamicFactory\Components\UIObjects\UserSelect;

use Xpressengine\UIObject\AbstractUIObject;
use View;
use XeFrontend;

class UserSelectUIObject extends AbstractUIObject
{
    protected static $id = 'uiobject/df@user_select';

    public function render()
    {
        $args = $this->arguments;

        $args['display_name'] = array_get($args, 'display_name', auth()->user()->getDisplayName());
        $args['login_id'] = array_get($args, 'login_id', auth()->user()->login_id);

        array_set($args, 'seq', $this->seq());  // 같은 uio 가 여러개 있을시 seq

        $blade = array_get($args, 'template') == 'multi' ? 'multi' : 'single';

        $temp_users = [];

        $users = app('xe.user')->users()->get();
        foreach ($users as $user) {
            $temp_users[] = [
                'label' => sprintf('%s(%s)', $user->display_name, $user->login_id),
                'value' => sprintf('%s|@|%s', $user->id, $user->display_name)
            ];
        }

        array_set($args, 'options', $temp_users);

        XeFrontend::css('plugins/dynamic_factory/assets/multiSelect2/multiSelect2.css')->load();
        XeFrontend::js('plugins/dynamic_factory/assets/multiSelect2/multiSelect2.min.js')->appendTo('head')->load();

        return View::make('dynamic_factory::components/UIObjects/UserSelect/'. $blade, $args);
    }
}
