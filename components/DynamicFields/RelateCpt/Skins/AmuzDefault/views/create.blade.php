{{ XeFrontend::css('plugins/dynamic_factory/assets/multiSelect2/multiSelect2.css')->load() }}
{{ XeFrontend::js('plugins/dynamic_factory/assets/multiSelect2/multiSelect2.min.js')->appendTo('head')->load() }}
<div id="relate_hidden_data"></div>
<div>
    <label>{{xe_trans($config->get('label'))}}</label>
    <div class="autocomplete-select"></div>
</div>
<script>
    var options = [
        @foreach($items as $item)
        {
            label: "{{ $item->getTitle() }}",
            value: "{{ $item->id }}"
        },
        @endforeach
    ];

    var values = [];

    var placeholder = "{{ $config->get('description') ? $config->get('description') : '여기에서 관련 문서를 검색 및 선택하세요.' }}";

    $(document).ready(function() {
        var autocomplete = new MultiSelect2(".autocomplete-select", {
            options: options,
            value: values,
            multiple: true,
            autocomplete: true,
            icon: "xi-close",
            onChange: value => {
                var hidden_div = document.getElementById("relate_hidden_data");
                hidden_div.innerHTML = "";

                if(value.length === 0) {
                    $('.multi-select__label').text(placeholder);
                }else{
                    for(var i = 0; i < value.length ; i++) {
                        var inputTag = document.createElement("input");
                        inputTag.setAttribute("type", "hidden");
                        inputTag.setAttribute("name", "{{ $key['ids'] }}[]");
                        inputTag.setAttribute("value", value[i]);

                        hidden_div.appendChild(inputTag);
                    }
                }
                console.log(value);
            },
        });

        $('.multi-select__label').text(placeholder);
    });
</script>
