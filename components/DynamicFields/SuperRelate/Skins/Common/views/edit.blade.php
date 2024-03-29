@php
    $is_user = ($config->get('r_instance_id') == 'user');


if(count($items) !== 0) {
    if($items[0]->r_name) $r_name = $items[0]->r_name;
    else $r_name = '';
    if($items[0]->r_id) $r_id = $items[0]->r_id;
    else $r_id = '';
} else {
    $r_name = '';
    $r_id = '';
}
@endphp
<div class="form-group">
    <label>{{ xe_trans($config->get('label')) }} <small>{{ $config->get('id') }}</small></label>
    <div id="autocomplete_{{ $config->get('id') }}">
        <input type="hidden" name="{{$config->get('id')}}_srf_chg" value="1" />
        <div class="ReactTags__tags">
            <div class="ReactTags__selected">
                @if($r_id !== '' && $r_name !== '')
                    @if(count($items) !== 0)
                        <span class="ReactTags__tag">{{ $items[0]->r_name }}<a class="ReactTags__remove btnRemoveTag" data-id="{{ $items[0]->r_id }}">x</a></span>
                    @endif
                @endif
            </div>
            <div class="ReactTags__tagInput">
                <input type="text" placeholder="{{ $is_user ? '사용자의 이름' : '문서의 제목' }}으로 검색하세요." class="form-control" />
                <div class="ReactTags__suggestions"></div>
            </div>
            <div class="input_hidden">
                <input type="hidden" name="hidden_{{ $config->get('id') }}[]" value="{{ $r_id }}" />
            </div>
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
        config_name: '{{ $config->name }}',
        config_id : '{{$config->get('id')}}'
    });
@else
    var p_{{ $config->get('id') }} = new DocList({
        wrapper_id: 'autocomplete_{{ $config->get('id') }}',
        $wrapper: $('#autocomplete_{{ $config->get('id') }}'),
        searchUrl: '{{ route('dyFac.document.search') }}',
        field_name: 'hidden_{{ $config->get('id') }}',
        config_name: '{{ $config->name }}',
        config_id : '{{$config->get('id')}}'
    });
@endif
    p_{{ $config->get('id') }}.bindEvents();
</script>

