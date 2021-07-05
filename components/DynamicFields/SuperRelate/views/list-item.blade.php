@foreach($data as $item)
    <a href="{{ route('dyFac.setting.'.$item->instance_id, ['type' => 'edit', 'doc_id' => $item->id]) }}" target="_blank">
    {{ $item->getTitle() }}
    </a>
@endforeach
