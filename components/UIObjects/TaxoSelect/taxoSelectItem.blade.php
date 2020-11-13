@php
    use Overcode\XePlugin\DynamicFactory\Components\UIObjects\TaxoSelect\TaxoSelectUIObject;
@endphp

@if(isset($items) && is_array($items) && count($items))
    <ul class="xe-dropdown-menu__sub">
        @foreach ($items as $item)
            <li @if($selectedItemValue == (string)$item['value']) class="on" @endif>
                <a href="#" data-value="{{$item['value']}}">{{ xe_trans($item['text']) }}</a>
                @if (TaxoSelectUIObject::hasChildren($item))
                    {!! TaxoSelectUIObject::renderList(TaxoSelectUIObject::getChildren($item), $selectedItemValue) !!}
                @endif
            </li>
        @endforeach
    </ul>
@endif