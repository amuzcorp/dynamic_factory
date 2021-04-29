<div class="form-group">
    <label>{{ xe_trans($config->get('label')) }} <small>{{ $config->get('id') }}</small></label>
    <div id="autocomplete_{{ $config->get('id') }}">
        <div class="ReactTags__tags">
            <div class="ReactTags__selected">
                @foreach($items as $item)
                <span class="ReactTags__tag">{{ $item->title }}<a class="ReactTags__remove btnRemoveTag" data-id="{{ $item->doc_id }}">x</a></span>
                @endforeach
            </div>
            <div class="ReactTags__tagInput">
                <input type="text" placeholder="문서의 제목으로 검색하세요." class="form-control inputUserGroup" />
                <div class="ReactTags__suggestions"></div>
            </div>
            <div class="input_hidden">
                @foreach($items as $item)
                    <input type="hidden" name="hidden_{{ $config->get('id') }}[]" value="{{ $item->doc_id }}" />
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
    var p_{{ $config->get('id') }} = new DocList({
        wrapper_id: 'autocomplete_{{ $config->get('id') }}',
        $wrapper: $('#autocomplete_{{ $config->get('id') }}'),
        searchUrl: '{{ route('dyFac.document.search') }}',
        field_name: 'hidden_{{ $config->get('id') }}',
        config_name: '{{ $config->name }}'
    });
    p_{{ $config->get('id') }}.bindEvents();
</script>
