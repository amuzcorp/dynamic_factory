<h1>{{ $item->id }} : {{ xe_trans($item->word) }}</h1>
<h4>{{ xe_trans($item->description) }}</h4>

@foreach($fieldTypes as $fieldType)
<div>
    {!! $fieldType->getSkin()->show($item->getAttributes()) !!}
</div>
@endforeach

