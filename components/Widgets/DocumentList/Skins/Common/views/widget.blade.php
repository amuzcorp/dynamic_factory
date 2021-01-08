{{ XeFrontend::css('plugins/board/assets/css/widget.list.css')->load() }}
<div class="list-widget">
    <h3 class="article-table-title">
        {{$title}}
    </h3>
    <a href="#"></a>
    <div class="table-wrap">
        <table class="article-table type2">
            <caption class="xe-sr-only">{{$title}}</caption>
            <tbody>
            @foreach ($list as $item)
                <tr>
                    <!-- 카테고리 링크를 제공하지 않는 경우 a를 span으로 교체 <td><span class="xe-badge xe-primary">세미나/이벤트</span></td> -->
                    <td class="title">
                        <a href="#">
                            <strong>{!! $item->title !!}</strong>
                            <p class="xe-ellipsis xe-hidden-sm xe-hidden-xs">{{$item->pure_content}} </p>
                        </a>
                    </td>
                    <td class="xe-hidden-sm xe-hidden-xs">
                        <em data-xe-timeago="{{$item->created_at}}">{{$item->created_at}}</em>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
