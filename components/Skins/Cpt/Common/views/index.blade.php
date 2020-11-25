{{ XeFrontend::js('assets/core/xe-ui-component/js/xe-page.js')->appendTo('body')->load() }}
<div class="board_list">
    <table>
        <!-- [D] 모바일뷰에서 숨겨할 요소 클래스 xe-hidden-xs 추가 -->
        <thead class="xe-hidden-xs">
        <!-- LIST HEADER -->
        <tr>
            <th scope="col" class="title column-th-title"><span>{{ xe_trans('board::title') }}</span></th>
        </tr>
        <!-- /LIST HEADER -->
        </thead>
        <tbody>
        @if (count($paginate) == 0)
            <!-- NO ARTICLE -->
            <tr class="no_article">
                <!-- [D] 컬럼수에 따라 colspan 적용 -->
                <td>
                    <img src="{{ asset('plugins/board/assets/img/img_pen.jpg') }}" alt="">
                    <p>{{ xe_trans('xe::noPost') }}</p>
                </td>
            </tr>
            <!-- / NO ARTICLE -->
        @endif

        <!-- LIST -->
        @foreach($paginate as $item)
            <tr>
                <td class="title column-title xe-hidden-xs">
                    <a href="{{ $urlHandler->getShow($item, Request::all()) }}" id="title_{{$item->id}}" class="title_text">{!! $item->title !!}</a>
                </td>

                {{--모바일 사이즈 게시물 list--}}
                <td class="xe-visible-xs title column-title">
                    <a href="{{ $urlHandler->getShow($item, Request::all()) }}" id="title_{{$item->id}}" class="title_text">{!! $item->title !!}</a>
                </td>
            </tr>
        @endforeach
        <!-- /LIST -->
        </tbody>
    </table>
</div>

<div class="board_footer">
    <!-- PAGINATAION PC-->
    {!! $paginate->render('dynamic_factory::components.Skins.Cpt.Common.views.default-pagination') !!}
    <!-- /PAGINATION PC-->

    <!-- PAGINATAION Mobile -->
    {!! $paginate->render('dynamic_factory::components.Skins.Cpt.Common.views.simple-pagination') !!}
    <!-- /PAGINATION Mobile -->
</div>
<div class="bd_dimmed"></div>
