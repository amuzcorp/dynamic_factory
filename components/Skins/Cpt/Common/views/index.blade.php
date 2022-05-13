{{ XeFrontend::js('assets/core/xe-ui-component/js/xe-page.js')->appendTo('body')->load() }}
{{ XeFrontend::js('assets/vendor/bootstrap/js/bootstrap.min.js')->load() }}
{{ XeFrontend::js('assets/vendor/jqueryui/jquery-ui.min.js')->appendTo("head")->load() }}
{{ XeFrontend::css([
    '/assets/vendor/jqueryui/jquery-ui.min.css',
    '/assets/vendor/bootstrap/css/bootstrap.min.css',
])->load() }}
@php

$orderNames = [
  'assent_count' => '추천순',
  'recently_created' => '최신순',
  'recently_published' => '최근 발행순',
  'recently_updated' => '최근 수정순',
];
$data = Request::except('_token');
$category_items = [];
foreach($data as $id => $value){
    if(strpos($id, 'taxo_', 0) === 0) {
        foreach($value as $val) {
            if(isset($val)) {
                $category_id = explode("_",$id);
                if(is_array($val)){
                    $category_items[$category_id[1]] = $val;
                }else{
                    if(!isset($category_items[$category_id[1]])) $category_items[$category_id[1]] = [];
                    $category_items[$category_id[1]][] = $val;
                }
            }
        }
    }
}
@endphp
@if($isWritable)
<style>
    .bd_paginate{
        margin-top:10px;
    }
    .xe-list-board--button-box {
        margin-top:60px;
    }
</style>
@endif
<style>
    .panel-group .panel-heading .pull-left {
        float: left !important;
        margin: 17px 0;
    }
    .panel-group .panel-heading .pull-right {
        float: right !important;
        margin: 17px 0;
    }
    .pull-right {
        width: 100%;
    }
    .input-group .form-control {
        width:auto;
    }
    form.input-group {
        position: unset;
        display: flex;
        float: right;
    }
    .input-group.search-group .btn-link {
        position: absolute;
        bottom: 0;
        right: 3px;
        z-index: 10;
        height: 34px;
        background-color: transparent;
        outline: none;
        font-size: 14px;
        color: #8F8F91;
    }
</style>
<div class="board_header">
    <div class="pull-right">
        <form id="search_forms" action="{{ $cptUrlHandler->get('index') }}" class="input-group search-group">
            <input type="hidden" name="taxOr" value="{{Request::get('taxOr')}}">
            <select class="form-control">
                <option @if(Request::get('taxOr') !== 'Y' || !Request::has('taxOr')) selected @endif value="N" >카테고리 일치</option>
                <option @if(Request::get('taxOr') === 'Y') selected @endif value="Y" >카테고리 포함</option>
            </select>

            <div id="click_badge_taxonomy" style="display: none;"></div>
            @foreach($taxonomies as $index => $taxonomy)
                @php
                    $taxo_item = app('overcode.df.taxonomyHandler')->getCategoryItemsTree($taxonomy->id);
                    $taxonomyIds = array_column($taxo_item->toArray(), 'id');
                    $searchIndex = false;
                @endphp
                <div id="{{'taxo_'.$taxonomy->id.'_selected'}}">
                    @if(isset($category_items[(string) $taxonomy->id]))
                        <input type="hidden" name="{{'taxo_'.$taxonomy->id}}[]" id="{{'taxo_'.$taxonomy->id}}" value="{{$category_items[(string) $taxonomy->id][0]}}">
                    @else
                        <input type="hidden" name="{{'taxo_'.$taxonomy->id}}[]" id="{{'taxo_'.$taxonomy->id}}" value="">
                    @endif
                </div>
                @if(isset($category_items[(string) $taxonomy->id]))
                    @php
                        $searchIndex = array_search( (int) $category_items[(string) $taxonomy->id][0], $taxonomyIds);
                    @endphp
                @endif
                <select class="form-control" onchange="searchTaxonomy(this, {{$taxonomy->id}})">
                    <option value="" @if($searchIndex === false) selected @endif>{{xe_trans($taxonomy->name).' 조회'}}</option>
                    @foreach($taxo_item as $key => $val)
                        @if(isset($category_items[(string) $taxonomy->id]))
                            @if(in_array((string) $val['value'], $category_items[(string) $taxonomy->id]))
                                <option value="{{$val['id']}}" selected>{{$val['text']}}</option>
                            @else
                                <option value="{{$val['id']}}">{{$val['text']}}</option>
                            @endif
                        @else
                            <option value="{{$val['id']}}">{{$val['text']}}</option>
                        @endif
                        @php $index++; @endphp
                    @endforeach
                </select>

                @if($taxonomy->extra->template === 'depth')
                    @php
                        $child_index = 0;
                    @endphp
                    @if(isset($category_items[(string) $taxonomy->id]))
                        @foreach($category_items[(string) $taxonomy->id] as $childIndex => $childId)
                            @php
                                $child_index++;
                                $selectedCategory = $category_items[(string) $taxonomy->id];
                                $childTaxonomies = app('overcode.df.taxonomyHandler')->getChildTaxonomies($childId);
                                $childIds = array_column($childTaxonomies->toArray(), 'id');
                                $categoryItem = app('overcode.df.taxonomyHandler')->getCategoryItem($childId);

                            @endphp
                            @if(count($childTaxonomies) > 0)
                                <div class="{{'taxo_'.$taxonomy->id.'_selected_child'}}">
                                    @if(isset($selectedCategory[$childIndex + 1]))
                                        <input type="hidden" name="{{'taxo_'.$taxonomy->id}}[]" id="{{'taxo_'. $taxonomy->id .'_'. $child_index .'_child'}}" value="{{$category_items[(string) $taxonomy->id][$childIndex + 1]}}">
                                    @else
                                        <input type="hidden" name="{{'taxo_'.$taxonomy->id}}[]" id="{{'taxo_'. $taxonomy->id .'_'. $child_index .'_child'}}" value="">
                                    @endif
                                </div>
                                <select class="form-control" onchange="searchChildTaxonomy(this, {{$taxonomy->id}}, {{$child_index}})">
                                    <option value="" @if(isset($selectedCategory[$childIndex + 1])) selected @endif>{{xe_trans($categoryItem->word).' 조회'}}</option>
                                    @foreach($childTaxonomies as $key => $val)
                                        @if(isset($selectedCategory[$childIndex + 1]))
                                            @if((string) $val->id === $selectedCategory[$childIndex + 1])
                                                <option value="{{$val->id}}" selected>{{$val->word}}</option>
                                            @else
                                                <option value="{{$val->id}}">{{$val->word}}</option>
                                            @endif
                                        @else
                                            <option value="{{$val->id}}">{{$val->word}}</option>
                                        @endif
                                    @endforeach
                                </select>
                            @endif
                        @endforeach
                    @endif
                @endif
            @endforeach

            <select class="form-control" name="perPage" onchange="$('#search_forms').submit();">
                <option value="20" @if(Request::get('perPage') == '20' || !Request::has('perPage')) selected @endif>아이템수</option>
                <option value="10"  @if(Request::get('order_type') == '10') selected @endif>10</option>
                <option value="15"  @if(Request::get('order_type') == '15') selected @endif>15</option>
                <option value="30"  @if(Request::get('order_type') == '30') selected @endif>30</option>
                <option value="60"  @if(Request::get('order_type') == '60') selected @endif>60</option>
                <option value="100"  @if(Request::get('order_type') == '100') selected @endif>100</option>
            </select>

            <select class="form-control" name="search_target" onchange="$('#search_forms').submit();">
                <option value="" @if(Request::get('search_target') == '' || !Request::has('search_target')) selected @endif>{{xe_trans('board::select')}}</option>
                <option value="title_pure_content" @if(Request::get('search_target') == 'title_pure_content') selected @endif>{{xe_trans('board::titleAndContent')}}</option>
                <option value="title" @if(Request::get('search_target') == 'title') selected @endif>{{xe_trans('board::title')}}</option>
                <option value="title_start" @if(Request::get('search_target') == 'title_start') selected @endif>{{xe_trans('board::title')}}시작일치</option>
                <option value="title_end" @if(Request::get('search_target') == 'title_end') selected @endif>{{xe_trans('board::title')}}끝일치</option>
                <option value="pure_content" @if(Request::get('search_target') == 'pure_content') selected @endif>{{xe_trans('board::content')}}</option>
                <option value="writer" @if(Request::get('search_target') == 'writer') selected @endif>{{xe_trans('board::writer')}}</option>
                <option value="writeId" @if(Request::get('search_target') == 'writeId') selected @endif>{{xe_trans('board::writerId')}}</option>
            </select>
            <input type="text" name="search_keyword" class="form-control" aria-label="Text input with dropdown button" placeholder="{{xe_trans('xe::enterKeyword')}}" value="{{Request::get('search_keyword')}}">
            <button class="btn-link btn btn-outline-info">
                <i class="xi-search"></i><span class="sr-only">{{xe_trans('xe::search')}}</span>
            </button>
        </form>
    </div>
</div>

<div class="board_list">
    <table>
        <!-- [D] 모바일뷰에서 숨겨할 요소 클래스 xe-hidden-xs 추가 -->
        <thead class="xe-hidden-xs">
        <!-- LIST HEADER -->
        <tr>
            <th>#</th>
        @foreach($dfConfig['listColumns'] as $columnName)
            @if($columnName === 'title')
            <th scope="col" class="title column-th-title"><span>제목</span></th>
            @else
            <th scope="col" class="column-th-{{$columnName}}"><span>{{ xe_trans($column_labels[$columnName]) }}</span></th>
            @endif
        @endforeach
        </tr>
        <!-- /LIST HEADER -->
        </thead>
        <tbody>
        @if (count($paginate ?: []) == 0)
            <!-- NO ARTICLE -->
            <tr class="no_article">
                <!-- [D] 컬럼수에 따라 colspan 적용 -->
                <td colspan="{{ count($dfConfig['listColumns']) + 1 }}">
                    <img src="{{ asset('plugins/board/assets/img/img_pen.jpg') }}" alt="">
                    <p>{{ xe_trans('xe::noPost') }}</p>
                </td>
            </tr>
            <!-- / NO ARTICLE -->
        @endif

        <!-- LIST -->
        @foreach($paginate ?: [] as $item)
            <tr>
                <td>{{ $item->seq }}</td>
            @foreach($dfConfig['listColumns'] as $columnName)
                @if ($columnName == 'title')
                    <td class="title column-{{$columnName}} xe-hidden-xs">
                        <a href="{{ $cptUrlHandler->getShow($item, Request::all()) }}" id="title_{{$item->id}}" class="title_text">{!! $item->title !!}</a>
                    </td>
                @elseif ($columnName == 'writer')
                    <td class="author xe-hidden-xs">
                        @if ($item->hasAuthor() && $config->get('anonymity') === false)
                            <a href="#"
                               data-toggle="xe-page-toggle-menu"
                               data-url="{{ route('toggleMenuPage') }}"
                               data-data='{!! json_encode(['id'=>$item->getUserId(), 'type'=>'user']) !!}'>{!! $item->writer !!}</a>
                        @else
                            <a>{!! $item->writer !!}</a>
                        @endif
                    </td>
                @elseif ($columnName == 'read_count')
                    <td class="read_num xe-hidden-xs">{{ $item->{$columnName} }}</td>
                @elseif (in_array($columnName, ['created_at', 'updated_at', 'deleted_at']))
                    <td class="time xe-hidden-xs column-{{$columnName}}" title="{{ $item->{$columnName} }}" @if($item->{$columnName}->getTimestamp() > strtotime('-1 month')) data-xe-timeago="{{ $item->{$columnName} }}" @endif >{{ $item->{$columnName}->toDateString() }}</td>
                @elseif (($fieldType = XeDynamicField::get('documents_'.$dfConfig->get('cpt_id'), $columnName)) != null)
                    <td class="xe-hidden-xs column-{{$columnName}}">{!! $fieldType->getSkin()->output($columnName, $item->getAttributes()) !!}</td>
                @else
                    <td class="xe-hidden-xs column-{{$columnName}}">{!! $item->{$columnName} !!}</td>
                @endif
            @endforeach
                {{--모바일 사이즈 게시물 list--}}
                <td class="xe-visible-xs title column-title">
                    <a href="{{ $cptUrlHandler->getShow($item, Request::all()) }}" id="title_{{$item->id}}" class="title_text">{!! $item->title !!}</a>
                </td>
            </tr>
        @endforeach
        <!-- /LIST -->
        </tbody>
    </table>
</div>

<div class="board_footer">
    @if($isWritable)
        <div class="xe-list-board--button-box text-right">
            <a class="btn btn-info" href="{{ $cptUrlHandler->get('create') }}" class="xe-list-board__btn">{{ xe_trans('board::writeItem') }}</a>
        </div>
    @endif
    @if($paginate)
    <!-- PAGINATAION PC-->
    {!! $paginate->render('dynamic_factory::components.Skins.Cpt.Common.views.default-pagination') !!}
    <!-- /PAGINATION PC-->

    <!-- PAGINATAION Mobile -->
    {!! $paginate->render('dynamic_factory::components.Skins.Cpt.Common.views.simple-pagination') !!}
    <!-- /PAGINATION Mobile -->
    @endif
</div>
<div class="bd_dimmed"></div>

<script>
    function searchTaxonomy(e, key) {
        $('[id="taxo_' + key + '"]').val(e.value);
        if(document.getElementById('taxo_' + key + '_selected'))
            document.getElementById('taxo_' + key + '_selected').innerHTML = '';

        for(let i = 0; i < document.getElementsByClassName('taxo_' + key + '_selected_child').length; i++) {
            if(document.getElementsByClassName('taxo_' + key + '_selected_child')[i])
                document.getElementsByClassName('taxo_' + key + '_selected_child')[i].innerHTML = '';
        }

        var str = `<input type="hidden" name="${'taxo_' + key}[]" value="${ e.value }" />`;
        document.getElementById('taxo_' + key + '_selected').innerHTML = str;

        $('#search_forms').submit();
    }

    function searchChildTaxonomy(e, key, child) {
        $('[id="taxo_' + key + '_' + child + '_child"]').val(e.value);
        $('#search_forms').submit();
    }
</script>
