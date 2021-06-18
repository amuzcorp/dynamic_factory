
<div class="list-widget">
    <h3 class="article-table-title">
        {{$title}}
    </h3>
    <form action="/{{$instanceUrl}}">
        <div class="row">
            <div class="col">
                <div class="form-group">
                    <input type="text"
                           name="{{$widgetConfig['parameter_key']}}"
                           value=""
                           @if(array_get($widgetConfig, 'text_placeholder'))
                           placeholder="{{array_get($widgetConfig, 'text_placeholder')}}"
                           @else
                           placeHolder="검색어를 입력해주세요"
                           @endif
                           class="form-control">
                </div>
            </div>
            @foreach($datas as $key => $data)
                <div class="col">
                    <div class="form-group">
                        <select class="form-control" name="cate_{{($key + 1)}}_name">
                            <option value="">
                                @if($widgetConfig['cate_'.($key + 1).'_placeholder'] !== "")
                                    {{$widgetConfig['cate_'.($key + 1).'_placeholder']}}
                                @else
                                    카테고리를 선택해주세요
                                @endif
                            </option>
                            @foreach($data['categoryItems'] as $item)
                                <option value="{{$item->id}}">{{xe_trans($item->word)}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endforeach
            <div class="col-2">
                <button type="submit" class="btn btn-primary">
                    @if(array_get($widgetConfig, 'button_text'))
                        {{$widgetConfig['button_text']}}
                    @else
                        검색
                    @endif
                </button>
            </div>

        </div>
    </form>
</div>
