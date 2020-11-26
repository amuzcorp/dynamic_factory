<?php

namespace Overcode\XePlugin\DynamicFactory\Handlers;

class CptValidatorHandler
{
    public function getRules()
    {
        $rules = $this->getDefaultRules();

        return $rules;
    }

    public function getDefaultRules()
    {
        return [
            'cpt_id' => 'required|max:36|unique:df_cpts|regex:/^[a-zA-Z]+([a-zA-Z0-9_]+)?[a-zA-Z0-9]+$/',
            'cpt_name' => 'required',
            'menu_name' => 'required',
            'menu_order' => 'required|numeric',
        ];
    }

    public function getUpdateRules()
    {
        return [
            'cpt_id' => 'required|max:36|regex:/^[a-zA-Z]+([a-zA-Z0-9_]+)?[a-zA-Z0-9]+$/',
            'cpt_name' => 'required',
            'menu_name' => 'required',
            'menu_order' => 'required|numeric',
        ];
    }
}
