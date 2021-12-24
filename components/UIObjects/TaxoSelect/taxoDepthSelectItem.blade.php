@php
    use Overcode\XePlugin\DynamicFactory\Components\UIObjects\TaxoSelect\TaxoSelectUIObject;
@endphp
@if(isset($items) && count($items))
    <ul class="xe-dropdown-menu__sub">
        @foreach ($items as $item)
            @if(is_array($item))
                <li @if($selectedItemValue == (string)$item['value']) class="on" @endif>
                    <a href="#" data-value="{{$item['value']}}" data-child="{{TaxoSelectUIObject::hasChildren($item)}}">{{ xe_trans($item['text']) }}</a>
                </li>
            @endif
        @endforeach
    </ul>
@endif
