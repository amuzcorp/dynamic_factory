@php
    use Overcode\XePlugin\DynamicFactory\Components\UIObjects\TaxoSelect\TaxoSelectUIObject;
@endphp

@if(isset($items) && count($items))
    @foreach($items as $item)
        @if(is_array($item))
            {label: "{{ xe_trans($item['text']) }}", value: "{{ $item['value'] }}"},
            @if (TaxoSelectUIObject::hasChildren($item))
                {!! TaxoSelectUIObject::renderMultiList(TaxoSelectUIObject::getChildren($item), $selectedItemValue) !!}
            @endif
        @endif
    @endforeach
@endif
