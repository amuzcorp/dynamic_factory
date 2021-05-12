@php
    $is_user = ($config->get('r_instance_id') == 'user');
@endphp
<div class="form-group">
    <label>{{ xe_trans($config->get('label')) }} <small>{{ $config->get('id') }}</small></label>
    <div id="autocomplete_{{ $config->get('id') }}">
        <input type="hidden" name="srf_chg" value="0" />
        <div class="ReactTags__tags">
            <div class="ReactTags__selected"></div>
            <div class="ReactTags__tagInput">
                <input type="text" placeholder="{{ $is_user ? '사용자의 이름' : '문서의 제목' }}으로 검색하세요." class="form-control" />
                <div class="ReactTags__suggestions"></div>
            </div>
            <div class="input_hidden"></div>
        </div>
    </div>
</div>

<script>
@if($is_user)
    var p_{{ $config->get('id') }} = new UserList({
            wrapper_id: 'autocomplete_{{ $config->get('id') }}',
            $wrapper: $('#autocomplete_{{ $config->get('id') }}'),
            searchUrl: '{{ route('dyFac.user.search') }}',
            field_name: 'hidden_{{ $config->get('id') }}',
            config_name: '{{ $config->name }}'
        });
@else
    var p_{{ $config->get('id') }} = new DocList({
        wrapper_id: 'autocomplete_{{ $config->get('id') }}',
        $wrapper: $('#autocomplete_{{ $config->get('id') }}'),
        searchUrl: '{{ route('dyFac.document.search') }}',
        field_name: 'hidden_{{ $config->get('id') }}',
        config_name: '{{ $config->name }}'
    });
@endif
    p_{{ $config->get('id') }}.bindEvents();
</script>
