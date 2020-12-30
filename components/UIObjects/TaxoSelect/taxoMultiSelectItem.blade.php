@php
    use Overcode\XePlugin\DynamicFactory\Components\UIObjects\TaxoSelect\TaxoSelectUIObject;
@endphp

@if(isset($items) && is_array($items) && count($items))
    @foreach($items as $item)
{label: "{{ xe_trans($item['text']) }}", value: "{{ $item['value'] }}"},
        @if (TaxoSelectUIObject::hasChildren($item))
{!! TaxoSelectUIObject::renderMultiList(TaxoSelectUIObject::getChildren($item), $selectedItemValue) !!}
        @endif
    @endforeach
@endif
