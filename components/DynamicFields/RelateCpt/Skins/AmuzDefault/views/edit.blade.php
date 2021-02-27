{{ XeFrontend::css('plugins/dynamic_factory/assets/multiSelect2/multiSelect2.css')->load() }}
{{ XeFrontend::js('plugins/dynamic_factory/assets/multiSelect2/multiSelect2.min.js')->appendTo('head')->load() }}

<div id="hidden_data_{{ $key['ids'] }}">
    @foreach($values as $value)
        <input type="hidden" name="{{ $key['ids'] }}[]" value="{{ $value }}" />
    @endforeach
</div>
<div>
    <label>{{ xe_trans($config->get('label')) }}</label>
    <div id="autocomplete_{{ $key['ids'] }}"></div>
</div>
<script>
    var options_{{ $key['ids'] }} = [
        @foreach($items as $item)
        {
            label: "{{ $item->getTitle() }}",
            value: "{{ $item->id }}"
        },
        @endforeach
    ];

    var values_{{ $key['ids'] }} = [
        @foreach($values as $value)
            @foreach($items as $item)
                @if(!empty($value) && $item->id == $value) "{{ $value }}", @endif
            @endforeach
        @endforeach
    ];

    var placeholder_{{ $key['ids'] }} = "{{ $config->get('placeholder') ? xe_trans($config->get('placeholder')) : '여기에서 관련 문서를 검색 및 선택하세요.' }}";

    var hidden_div_{{ $key['ids'] }} = document.getElementById("hidden_data_{{ $key['ids'] }}");

    var addInputTag_{{ $key['ids'] }} = function(value) {
        var inputTag = document.createElement("input");
        inputTag.setAttribute("type", "hidden");
        inputTag.setAttribute("name", "{{ $key['ids'] }}[]");
        inputTag.setAttribute("value", value);

        hidden_div_{{ $key['ids'] }}.appendChild(inputTag);
    };

    $(document).ready(function() {
        new MultiSelect2("#autocomplete_{{ $key['ids'] }}", {
            options: options_{{ $key['ids'] }},
            value: values_{{ $key['ids'] }},
            multiple: true,
            autocomplete: true,
            icon: "xi-close",
            onChange: value => {
                hidden_div_{{ $key['ids'] }}.innerHTML = "";

                if(value.length === 0) {
                    $('#autocomplete_{{ $key['ids'] }} .multi-select__label').text(placeholder_{{ $key['ids'] }});
                    addInputTag_{{ $key['ids'] }}("");
                }else{
                    for(var i = 0; i < value.length ; i++) {
                        if(value[i]) {
                            addInputTag_{{ $key['ids'] }}(value[i]);
                        }
                    }
                }
                //console.log(value);
            },
        });
        @if(count($values) == 0)
        $('#autocomplete_{{ $key['ids'] }} .multi-select__label').text(placeholder_{{ $key['ids'] }});
        @endif
    });
</script>
