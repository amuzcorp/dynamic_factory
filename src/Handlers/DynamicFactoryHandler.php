<?php

namespace Overcode\XePlugin\DynamicFactory\Handlers;

use App\Http\Sections\DynamicFieldSection;
use Illuminate\Database\Eloquent\Collection;
use Overcode\XePlugin\DynamicFactory\Exceptions\AlreadyExistFavoriteHttpException;
use Overcode\XePlugin\DynamicFactory\Exceptions\NotFoundFavoriteHttpException;
use Overcode\XePlugin\DynamicFactory\Models\Cpt;
use Overcode\XePlugin\DynamicFactory\Models\DfFavorite;
use Overcode\XePlugin\DynamicFactory\Plugin;
use XeConfig;
use XeDB;
use XeLang;
use XeSite;
use Xpressengine\Document\DocumentHandler;

class DynamicFactoryHandler
{
    protected $reserved = [];

    protected $defaultLabels = [
        'title' => '제목',
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
//        'here_title' => '여기에 제목 입력'
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
            'cpt_id' => $inputs['cpt_id'],
            'cpt_name' => $inputs['cpt_name'],
            'menu_name' => $inputs['menu_name'],
            'menu_order' => $inputs['menu_order'],
            'menu_path' => $inputs['menu_path'] ?? '',
            'description' => $inputs['description'] ?? '',
            'labels' => $inputs['labels'] ?? '',
            'use_comment' => $inputs['use_comment'] ?? '',
            'show_admin_comment' => $inputs['show_admin_comment'] ?? ''
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
            'menu_order' => $inputs['menu_order'],
            'menu_path' => $inputs['menu_path'] ?? '',
            'description' => $inputs['description'] ?? '',
            'labels' => $inputs['labels'] ?? '',
            'use_comment' => $inputs['use_comment'] ?? '',
            'show_admin_comment' => $inputs['show_admin_comment'] ?? ''
        ]);

        $cpt->save();

        return $cpt;
    }

    public function getItems()
    {
        $site_key = \XeSite::getCurrentSiteKey();
        return Cpt::where('site_key', $site_key)->get();
    }

    public function getItemsFromPlugin()
    {
        $cptsFromPlugin = \XeRegister::get('dynamic_factory');    // register 에 등록된 cpt 를 가져온다

        $cpts = new Collection();
        if(isset($cptsFromPlugin)) {
            foreach ($cptsFromPlugin as $cpt_fp) {
                $cpt = new Cpt();
                $cpt_fp['from_plugin'] = true;
                $cpt->setRawAttributes($cpt_fp);
                //$cpts[] = $cpt;
                $cpts->push($cpt);
            }
        }
        return $cpts;
    }

    public function getItem($cpt_id)
    {
        return Cpt::find($cpt_id);
    }

    public static function getDynamicFields($cpt_id)
    {
        $group = 'documents_' . $cpt_id;

        $dynamicField = app('xe.dynamicField');

        $list = [];

        $configs = $dynamicField->getConfigHandler()->gets($group);
        foreach ($configs as $config) {
            $info = $config->getPureAll();
            $fieldType = $dynamicField->get($config->get('group'), $config->get('id'));
            $info['typeName'] = $fieldType->name();
            $info['skinName'] = $fieldType->getSkin()->name();
            $info['label'] = xe_trans($info['label']);

            $list[] = $info;
        }

        return $list;
    }

    public function getAdminMenus()
    {
        $menus = \XeRegister::get('settings/menu');

        foreach ($menus as $key => $val){
            if(substr_count($key, '.') > 0) unset($menus[$key]);    // 상위 메뉴만 호출
            else $menus[$key]['menu_path'] = $key . '.';
        }

        return $menus;
    }

    /**
     * plugin boot 단계에서 실행됨
     * 다른 플러그인이 등록한 확장필드를 불러와서 있으면 무시, 없으면 생성해 준다.
     */
    public function createDynamicFieldForOut()
    {
        $df_dfs = \XeRegister::get('df_df');

        \XeDB::beginTransaction();
        try {
            $dynamicField = app('xe.dynamicField');
            //$registerHandler = $dynamicField->getRegisterHandler();
            $configHandler = $dynamicField->getConfigHandler();

            $new_cpt_ids = [];  // DF 추가 감지

            $need_lang_configs = ['label','placeholder'];
            foreach ((array)$df_dfs as $df_key => $dfs) {
                foreach ((array)$dfs as $df) {
                    if(array_get($df, 'group') == null) {
                        $df['group'] = 'documents_' . $df_key;
                    }
                    $configName = sprintf('dynamicField.%s.%s', $df['group'], $df['id']);

                    if (XeConfig::get($configName) === null) {
                        $new_cpt_ids[] = $df_key;

                        $config = $configHandler->getDefault();
                        foreach ($df as $name => $value) {
                            if(in_array($name,$need_lang_configs) && !empty($value)) {
                                $langKey = XeLang::genUserKey();
                                XeLang::save($langKey, 'ko', $value, false);

                                $config->set($name, $langKey);
                            }else{
                                $config->set($name, $value);
                            }
                        }
                        $dynamicField->setConnection(\XeDB::connection());
                        $dynamicField->create($config);
                    }
                }
            }

            //21.04.10 추가 by xiso
            //df_key는 항상 cpt_id가 아닐수 있음(taxonomy 때문).
            //하지만 setCurrentSortFormColumns에서 그것을 체크하기때문에 그냥 보냄
            $new_cpt_ids = array_unique($new_cpt_ids);  // 새로운 DF 가 추가되었으면 DyFac Config 를 Update 한다.
            foreach ($new_cpt_ids as $cpt_id) {
                app('overcode.df.configHandler')->setCurrentSortFormColumns($cpt_id);
            }
        } catch (\Exception $e) {
            \XeDB::rollback();

            throw $e;
        }
        \XeDB::commit();
    }

    /**
     * 해당 document_id 를 가지고 있는 relate_cpt 의 document_id 들을 배열로 반환한다.
     *
     * @param $fieldId      // 검색대상 CPT 에서 사용하는 DF 의 ID
     * @param $group        // 검색대상 CPT 의 group : documents_{targetCptId}
     * @param $documentId   // 검색 키워드 Document ID
     * @return array
     */
    public function getRelateCpts($fieldId, $group, $documentId)
    {
        $targets = XeDB::table('field_dynamic_factory_relate_cpt')->where('field_id', $fieldId)->where('group', $group)->where('ids', 'like', '%"'.$documentId.'"%')->get();

        $target_ids = [];

        foreach($targets as $target){
            $target_ids[] = $target->target_id;
        }

        $target_ids = array_unique($target_ids);    // 중복 제거

        return $target_ids;
    }

    public function destroyCpt($cpt_id)
    {
        $siteKey = XeSite::getCurrentSiteKey();

        XeDB::table('df_cpts')->where('cpt_id', $cpt_id)->where('site_key', $siteKey)->delete();
    }

}
