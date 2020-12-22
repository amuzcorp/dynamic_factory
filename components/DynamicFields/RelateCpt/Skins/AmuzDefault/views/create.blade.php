{{ XeFrontend::css('plugins/dynamic_factory/assets/multiSelect2/multiSelect2.css')->load() }}
{{ XeFrontend::js('plugins/dynamic_factory/assets/multiSelect2/multiSelect2.min.js')->appendTo('head')->load() }}

<div id="hidden_data_{{ $key['ids'] }}"></div>
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

    var values_{{ $key['ids'] }} = [];

    var placeholder_{{ $key['ids'] }} = "{{ $config->get('placeholder') ? xe_trans($config->get('placeholder')) : '여기에서 관련 문서를 검색 및 선택하세요.' }}";

    $(document).ready(function() {
        new MultiSelect2("#autocomplete_{{ $key['ids'] }}", {
            options: options_{{ $key['ids'] }},
            value: values_{{ $key['ids'] }},
            multiple: true,
            autocomplete: true,
            icon: "xi-close",
            onChange: value => {
                var hidden_div = document.getElementById("hidden_data_{{ $key['ids'] }}");
                hidden_div.innerHTML = "";

                if(value.length === 0) {
                    $('#autocomplete_{{ $key['ids'] }} .multi-select__label').text(placeholder_{{ $key['ids'] }});
                }else{
                    for(var i = 0; i < value.length ; i++) {
                        var inputTag = document.createElement("input");
                        inputTag.setAttribute("type", "hidden");
                        inputTag.setAttribute("name", "{{ $key['ids'] }}[]");
                        inputTag.setAttribute("value", value[i]);

                        hidden_div.appendChild(inputTag);
                    }
                }
                //console.log(value);
            },
        });

        $('#autocomplete_{{ $key['ids'] }} .multi-select__label').text(placeholder_{{ $key['ids'] }});
    });
</script>
