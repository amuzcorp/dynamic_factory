<div class="form-group">
    <label>{{ xe_trans($config->get('label')) }} <small>{{ $config->get('id') }}</small></label>
    <div id="selected_{{ $config->get('id') }}">
        <input type="hidden" name="{{$config->get('id')}}_srf_chg" value="1" />

        <div class="Tags__tags">
            <div class="list_ReactTags__selected relate_tags" data-config_id="{{$config->get('id')}}" data-config_name="{{$config->name}}" id="{{ $config->get('id') }}_tags">
            </div>
            <div class="{{ $config->get('id') }}_input_hidden">
            </div>

            <div class="Tags__tagInput">
                <input type="text" name="{{$config->get('id')}}_cpt_search" placeholder="{{ ($config->get('r_instance_id') == 'user') ? '사용자의 이름' : '문서의 제목' }}으로 검색하세요." class="form-control input-left cpt_search_doc_input" data-config_id="{{$config->get('id')}}" data-config_name="{{$config->name}}" />
                <a class="btn btn-primary btn-right cpt_search_doc" data-config_id="{{$config->get('id')}}" data-config_name="{{$config->name}}" style="">검색</a>

                <div class="ReactTags__suggestions"></div>
            </div>
            <div class="input_hidden"></div>
        </div>

    </div>
</div>
<div class="form-group" id="sr_configs_{{$config->get('id')}}" data-r_instance_id="{{$config->get('r_instance_id')}}" data-target_url="{{ ($config->get('r_instance_id') == 'user') ? route('dyFac.user.search') : route('dyFac.document.search') }}">
    <ul class="list-latest-tfc reset-list" id="{{$config->get('id')}}_cptListItem">
    </ul>
</div>
