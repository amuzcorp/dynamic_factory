@foreach($taxonomy_items as $key => $taxonomy_item)
    <ul>
    @foreach($taxonomy_item as $item)
        <li>
            <a href="{{ $taxoUrlHandler->getShow($item, Request::all()) }}">{{ xe_trans($item->word) }}</a>
            @foreach($taxo_field_types[$key] as $fieldType)
                {!! $fieldType->getSkin()->show($item->getAttributes()) !!}
            @endforeach
        </li>
    @endforeach
    </ul>
@endforeach
