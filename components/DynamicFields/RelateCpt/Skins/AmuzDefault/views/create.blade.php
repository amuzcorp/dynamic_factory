{{ XeFrontend::css('plugins/dynamic_factory/assets/multiSelect2/multiSelect2.css')->load() }}
{{ XeFrontend::js('plugins/dynamic_factory/assets/multiSelect2/multiSelect2.min.js')->appendTo('head')->load() }}
<div>
    <label>관련 문서 선택</label>
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

    var values = [

    ];

    var placeholder = "여기에서 관련 문서를 검색 및 선택하세요.";

    var autocomplete = new MultiSelect2(".autocomplete-select", {
        options: options,
        value: values,
        multiple: true,
        autocomplete: true,
        icon: "xi-close",
        onChange: value => {
            if(value.length === 0) {
                $('.multi-select__label').text(placeholder);
            }
            // console.log(value);
        },
    });

$(document).ready(function() {
    $('.multi-select__label').text(placeholder);
});
</script>
