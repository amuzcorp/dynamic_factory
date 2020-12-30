@php
    use Overcode\XePlugin\DynamicFactory\Components\UIObjects\TaxoSelect\TaxoSelectUIObject;
@endphp
{{ XeFrontend::css('plugins/dynamic_factory/assets/multiSelect2/multiSelect2.css')->load() }}
{{ XeFrontend::js('plugins/dynamic_factory/assets/multiSelect2/multiSelect2.min.js')->appendTo('head')->load() }}

<div id="hidden_data_{{ $name }}"></div>
<div id="autocomplete_{{ $name }}"></div>

<script>
    var options_{{ $name }} = [
        {!! TaxoSelectUIObject::renderMultiList($items, isset($selectedItem) ? $selectedItem : '') !!}
    ];

    var values_{{ $name }} = [
        @if(isset($selectedItem))
            @foreach($selectedItem as $key => $val)
"{{ $key }}",
            @endforeach
        @endif
    ];

    var placeholder_{{ $name }} = "여기에서 {{ $label }} 을(를) 검색 및 선택하세요.";

    var hidden_div_{{ $name }} = document.getElementById("hidden_data_{{ $name }}");

    var addInputTag_{{ $name }} = function(value) {
        var inputTag = document.createElement("input");
        inputTag.setAttribute("type", "hidden");
        inputTag.setAttribute("name", "{{ $name }}[]");
        inputTag.setAttribute("value", value);

        hidden_div_{{ $name }}.appendChild(inputTag);
    };

    $(document).ready(function() {
        new MultiSelect2("#autocomplete_{{ $name }}", {
            options: options_{{ $name }},
            value: values_{{ $name }},
            multiple: true,
            autocomplete: true,
            icon: "xi-close",
            onChange: value => {
                hidden_div_{{ $name }}.innerHTML = "";

                if(value.length === 0) {
                    $('#autocomplete_{{ $name }} .multi-select__label').text(placeholder_{{ $name }});
                    addInputTag_{{ $name }}("");
                }else{
                    for(var i = 0; i < value.length ; i++) {
                        if(value[i]) {
                            addInputTag_{{ $name }}(value[i]);
                        }
                    }
                }
            },
        });
        @if(!isset($selectedItem))
        $('#autocomplete_{{ $name }} .multi-select__label').text(placeholder_{{ $name }});
        @endif
    });
</script>
