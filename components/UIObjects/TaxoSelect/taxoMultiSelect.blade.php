@php
    use Overcode\XePlugin\DynamicFactory\Components\UIObjects\TaxoSelect\TaxoSelectUIObject;
@endphp

<select class="form-control" name="{{ $name }}[]" multiple>
    {!! TaxoSelectUIObject::renderMultiList($items, isset($selectedItem) ? $selectedItem : '') !!}
</select>
