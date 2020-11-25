<div class="board_read">
    <div class="read_header">
        <span class="category">카테고리스</span>
        <h1><a href="#">{!! $item->title !!}</a></h1>

        <div class="more_info">
            <span>
                <a class="mb_autohr">{{ $item->writer }}</a>
            </span>

            <span class="mb_time" title="{{$item->created_at}}"><i class="xi-time"></i> <span data-xe-timeago="{{$item->created_at}}">{{$item->created_at}}</span></span>
            <span class="mb_readnum"><i class="xi-eye"></i> {{$item->read_count}}</span>
        </div>
    </div>
    <div class="read_body">
        <div class="xe_content xe-content xe-content-{{ $item->instance_id }}">
            {!! compile($item->instance_id, $item->content, $item->format === Overcode\XePlugin\DynamicFactory\Models\CptDocument::FORMAT_HTML) !!}
        </div>
    </div>
    @foreach ($fieldTypes as $dynamicFieldConfig)
{{--        @if (in_array($dynamicFieldConfig->get('id'), $skinConfig['formColumns']) === false && ($fieldType = XeDynamicField::getByConfig($dynamicFieldConfig)) != null && $dynamicFieldConfig->get('use') == true)--}}
            <div class="__xe_ __xe_section">
                @php
                    $fieldType = XeDynamicField::getByConfig($dynamicFieldConfig)
                @endphp
                {!! $fieldType->getSkin()->show($item->getAttributes()) !!}
            </div>
{{--        @endif--}}
    @endforeach
    <div class="read_footer">
        <div class="bd_function">
            <div class="bd_function_r">
                <a href="{{ $urlHandler->get('index', array_merge(Request::all())) }}" class="bd_ico bd_list"><i class="xi-list"></i><span class="xe-sr-only">리스트</span></a>
            </div>
        </div>
    </div>
</div>
