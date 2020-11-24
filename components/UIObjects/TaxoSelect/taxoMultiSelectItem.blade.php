@php
    use Overcode\XePlugin\DynamicFactory\Components\UIObjects\TaxoSelect\TaxoSelectUIObject;
@endphp

@if(isset($items) && is_array($items) && count($items))
    @foreach($items as $item)
        <option value="{{$item['value']}}" @if(is_array($selectedItemValue) && array_key_exists($item['value'], $selectedItemValue)) selected="selected" @endif>{{ xe_trans($item['text']) }}</option>
        @if (TaxoSelectUIObject::hasChildren($item))
            {!! TaxoSelectUIObject::renderMultiList(TaxoSelectUIObject::getChildren($item), $selectedItemValue) !!}
        @endif
    @endforeach
@endif
