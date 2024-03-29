@php

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

@section('page_title')
    <h2>{{ $cpt->cpt_name }}</h2>
@stop

@section('page_description')
    <small>{{ $cpt->description }}</small>
@endsection

<style>
    .badge_a_tag {
        text-decoration: auto !important;
    }
    .badge_a_tag:hover span {
        background-color: #277ed3;
        color: #ffffff;
    }
</style>

<div class="row">
    <div class="col-sm-12">
        <div class="admin-tab-info">
            <ul class="admin-tab-info-list">
                @foreach ($stateTypeCounts as $stateType => $count)
                    <li @if (Request::get('stateType', 'all') === $stateType) class="on" @endif>
                        <a href="{{ route($current_route_name, ['type' => 'list', 'stateType' => $stateType]) }}" class="__plugin-install-link admin-tab-info-list__link">{{ xe_trans('dyFac::' . $stateType) }} <span class="admin-tab-info-list__count">{{ $count }}</span></a>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="panel-group">
            <div class="panel">
                <div class="panel-heading">
                    <div class="pull-left">
                        <h3 class="panel-title">{{ $cpt->cpt_name }} 목록</h3>
                    </div>
                    <form id="uploadCSV" action="{{route('dyFac.setting.uploadCSV')}}" method="post" enctype="multipart/form-data">
                        {!! csrf_field() !!}
                        <div class="pull-right">
                            <input type="hidden" name="cpt_id" value="{{$cpt->cpt_id}}">
                            <label class="xe-btn xe-btn-warning-outline">
                                <i class="xi-icon xi-plus"></i> CSV 등록
                                <input type="file" class="__xe_file xe-hidden" name="csv_file" accept=".csv" onchange="uploadCSV(this)">
                            </label>
                            <a onclick="downloadCSV()" class="xe-btn xe-btn-success-outline"><i class="xi-download"></i>CSV 저장</a>
                            <a href="{{ route('dyFac.setting.edit', ['cpt_id' => $cpt->cpt_id]) }}" class="xe-btn xe-btn-positive-outline"><i class="xi-cog"></i> 설정</a>
                            <a href="{{ route($current_route_name, ['type' => 'create']) }}" class="xe-btn xe-btn-primary" data-toggle="xe-page-modal"><i class="xi-file-text-o"></i> {{ sprintf($cpt->labels['new_add_cpt'], $cpt->cpt_name) }}</a>
                        </div>
                    </form>
                </div>

                <div class="panel-heading">
                    <div class="pull-left">
                        <div class="btn-group __xe_function_buttons" role="group" aria-label="...">
                            <button type="button" class="btn btn-default __xe_button" data-mode="trash">{{xe_trans('xe::trash')}}</button>
                        </div>
                    </div>
                    <div class="pull-right">
                        <form id="__xe_search_form" class="input-group search-group">
                            <div class="input-group-btn __xe_btn_taxo_item">
                                <input type="hidden" name="taxOr" value="{{Request::get('taxOr')}}">
                                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                    <span class="taxOr_xe_text">
                                        @if(Request::get('taxOr') !== 'Y')
                                            카테고리 일치
                                        @else
                                            카테고리 포함
                                        @endif
                                    </span>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu" role="menu">
                                    <li @if(Request::get('taxOr') !== 'Y') class="active" @endif><a value="N" onclick="searchTaxoOr(this)">카테고리 일치</a></li>
                                    <li @if(Request::get('taxOr') === 'Y') class="active" @endif><a value="Y" onclick="searchTaxoOr(this)">카테고리 포함</a></li>
                                </ul>
                            </div>

                            <div id="click_badge_taxonomy" style="display: none;"></div>
                            @foreach($taxonomies as $index => $taxonomy)
                                @php
                                    $taxo_item = app('overcode.df.taxonomyHandler')->getCategoryItemsTree($taxonomy->id);
                                    $taxonomyIds = array_column($taxo_item->toArray(), 'id');
                                @endphp
                                <div class="input-group-btn __xe_btn_taxo_item">
                                    <div id="{{'taxo_'.$taxonomy->id.'_selected'}}">
                                        @if(isset($category_items[(string) $taxonomy->id]))
                                            <input type="hidden" name="{{'taxo_'.$taxonomy->id}}[]" id="{{'taxo_'.$taxonomy->id}}" value="{{$category_items[(string) $taxonomy->id][0]}}">
                                        @else
                                            <input type="hidden" name="{{'taxo_'.$taxonomy->id}}[]" id="{{'taxo_'.$taxonomy->id}}" value="">
                                        @endif
                                    </div>
                                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                        @if(isset($category_items[(string) $taxonomy->id]))
                                            @php
                                                $searchIndex = array_search( (int) $category_items[(string) $taxonomy->id][0], $taxonomyIds);
                                            @endphp

                                            @if($searchIndex !== false)
                                                <span class="taxo_{{($index + 1)}}_xe_text">
                                                    {{$taxo_item[$searchIndex]['text']}}
                                                </span>
                                            @else
                                                <span class="taxo_{{($index + 1)}}_xe_text">{{xe_trans($taxonomy->name).' 조회'}}</span>
                                            @endif
                                        @else
                                            <span class="taxo_{{($index + 1)}}_xe_text">{{xe_trans($taxonomy->name).' 조회'}}</span>
                                        @endif
                                        <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu" role="menu">
                                        <li @if(!isset($category_items[(string) $taxonomy->id])) class="active" @endif><a value="" onclick="searchTaxonomy(this, {{$taxonomy->id}})">전체</a></li>
                                        @foreach($taxo_item as $key => $val)
                                            @if(isset($category_items[(string) $taxonomy->id]))
                                                @if(in_array((string) $val['value'], $category_items[(string) $taxonomy->id]))
                                                    <li class="active"><a value="{{$val['id']}}" onclick="searchTaxonomy(this, {{$taxonomy->id}})">{{$val['text']}}</a></li>
                                                @else
                                                    <li><a value="{{$val['id']}}" onclick="searchTaxonomy(this, {{$taxonomy->id}})">{{$val['text']}}</a></li>
                                                @endif
                                            @else
                                                <li><a value="{{$val['id']}}" onclick="searchTaxonomy(this, {{$taxonomy->id}})">{{$val['text']}}</a></li>
                                            @endif

                                            @php $index++; @endphp
                                        @endforeach
                                    </ul>
                                </div>

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
                                                <div class="input-group-btn __xe_btn_taxo_item">
                                                    <div class="{{'taxo_'.$taxonomy->id.'_selected_child'}}">
                                                        @if(isset($selectedCategory[$childIndex + 1]))
                                                            <input type="hidden" name="{{'taxo_'.$taxonomy->id}}[]" id="{{'taxo_'. $taxonomy->id .'_'. $child_index .'_child'}}" value="{{$category_items[(string) $taxonomy->id][$childIndex + 1]}}">
                                                        @else
                                                            <input type="hidden" name="{{'taxo_'.$taxonomy->id}}[]" id="{{'taxo_'. $taxonomy->id .'_'. $child_index .'_child'}}" value="">
                                                        @endif
                                                    </div>

                                                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                                        @if(isset($selectedCategory[$childIndex + 1]))
                                                            @php
                                                                $childItem = app('overcode.df.taxonomyHandler')->getCategoryItem($selectedCategory[$childIndex + 1]);
                                                            @endphp
                                                            <span class="taxo_{{($index + 1)}}_{{$child_index}}_xe_text">
                                                                {{xe_trans($childItem->word)}}
                                                            </span>
                                                        @else
                                                            <span class="taxo_{{($index + 1)}}_{{$child_index}}_xe_text">{{xe_trans($categoryItem->word).' 조회'}}</span>
                                                        @endif
                                                        <span class="caret"></span>
                                                    </button>
                                                    <ul class="dropdown-menu" role="menu">
                                                        <li @if(!isset($selectedCategory[$childIndex + 1])) class="active" @endif><a value="" onclick="searchChildTaxonomy(this, {{$taxonomy->id}}, {{$child_index}})">전체</a></li>
                                                        @foreach($childTaxonomies as $key => $val)
                                                            @if(isset($selectedCategory[$childIndex + 1]))
                                                                @if((string) $val->id === $selectedCategory[$childIndex + 1])
                                                                    <li class="active"><a value="{{$val->id}}" onclick="searchChildTaxonomy(this, {{$taxonomy->id}}, {{$child_index}})">{{$val->word}}</a></li>
                                                                @else
                                                                    <li><a value="{{$val->id}}" onclick="searchChildTaxonomy(this, {{$taxonomy->id}}, {{$child_index}})">{{$val->word}}</a></li>
                                                                @endif
                                                            @else
                                                                <li><a value="{{$val->id}}" onclick="searchChildTaxonomy(this, {{$taxonomy->id}}, {{$child_index}})">{{$val->word}}</a></li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        @endforeach
                                    @endif
                                @endif
                            @endforeach

                            <div class="input-group-btn __xe_btn_per_page">
                                <input type="hidden" name="perPage" value="{{ Request::get('perPage') }}">
                                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                    <span class="__xe_text">
                                        {{Request::has('perPage') && Request::get('perPage') != '20' && Request::get('perPage') != '' ? Request::get('perPage') : '아이템수'}}
                                    </span>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu" role="menu">
                                    <li @if(Request::get('perPage') == '20' || Request::get('perPage') == '') class="active" @endif><a href="#" value="20">20</a></li>
                                    <li @if(Request::get('perPage') == '30') class="active" @endif><a href="#" value="30">30</a></li>
                                    <li @if(Request::get('perPage') == '40') class="active" @endif><a href="#" value="40">40</a></li>
                                    <li @if(Request::get('perPage') == '60') class="active" @endif><a href="#" value="60">60</a></li>
                                    <li @if(Request::get('perPage') == '80') class="active" @endif><a href="#" value="80">80</a></li>
                                    <li @if(Request::get('perPage') == '100') class="active" @endif><a href="#" value="100">100</a></li>
                                    <li @if(Request::get('perPage') == '200') class="active" @endif><a href="#" value="200">200</a></li>
                                    <li @if(Request::get('perPage') == '300') class="active" @endif><a href="#" value="300">300</a></li>
                                </ul>
                            </div>

                            <div class="input-group-btn __xe_btn_order_type">
                                <input type="hidden" name="order_type" value="{{ Request::get('order_type') }}">
                                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="__xe_text">{{Request::has('order_type') && Request::get('order_type') != '' ? $orderNames[Request::get('order_type')] : '정렬'}}</span> <span class="caret"></span></button>
                                <ul class="dropdown-menu" role="menu">
                                    <li @if(Request::get('order_type') == '') class="active" @endif><a href="#" value="">정렬</a></li>
                                    <li @if(Request::get('order_type') == 'assent_count') class="active" @endif><a href="#" value="assent_count">{{ $orderNames['assent_count'] }}</a></li>
                                    <li @if(Request::get('order_type') == 'recently_created') class="active" @endif><a href="#" value="recently_created">{{ $orderNames['recently_created'] }}</a></li>
                                    <li @if(Request::get('order_type') == 'recently_published') class="active" @endif><a href="#" value="recently_published">{{ $orderNames['recently_published'] }}</a></li>
                                    <li @if(Request::get('order_type') == 'recently_updated') class="active" @endif><a href="#" value="recently_updated">{{ $orderNames['recently_updated'] }}</a></li>
                                </ul>
                            </div>
                            <div class="input-group-btn __xe_btn_search_target">
                                <input type="hidden" name="search_target" value="{{ Request::get('search_target') }}">
                                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="__xe_text">{{Request::has('search_target') && Request::get('search_target') != '' ? xe_trans('board::' . $searchTargetWord) : xe_trans('xe::select')}}</span> <span class="caret"></span></button>
                                <ul class="dropdown-menu" role="menu">
                                    <li @if(Request::get('search_target') == '') class="active" @endif><a href="#" value="">{{xe_trans('board::select')}}</a></li>
                                    <li @if(Request::get('search_target') == 'title_pure_content') class="active" @endif><a href="#" value="title_pure_content">{{xe_trans('board::titleAndContent')}}</a></li>
                                    <li @if(Request::get('search_target') == 'title') class="active" @endif><a href="#" value="title">{{xe_trans('board::title')}}</a></li>
                                    <li @if(Request::get('search_target') == 'pure_content') class="active" @endif><a href="#" value="pure_content">{{xe_trans('board::content')}}</a></li>
                                    <li @if(Request::get('search_target') == 'writer') class="active" @endif><a href="#" value="writer">{{xe_trans('board::writer')}}</a></li>
                                    <li @if(Request::get('search_target') == 'writeId') class="active" @endif><a href="#" value="writerId">{{ xe_trans('board::writerId') }}</a></li>
                                </ul>
                            </div>
                            <div class="search-input-group">
                                <input type="text" name="search_keyword" class="form-control" aria-label="Text input with dropdown button" placeholder="{{xe_trans('xe::enterKeyword')}}" value="{{Request::get('search_keyword')}}">
                                <button class="btn-link">
                                    <i class="xi-search"></i><span class="sr-only">{{xe_trans('xe::search')}}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="panel-body">
                    <form class="__xe_form_list" method="post">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <table class="table">
                            <thead>
                            <tr>
                                <th scope="col"><input type="checkbox" class="__xe_check_all"></th>
                                <th>#</th>
                            @foreach($config['listColumns'] as $columnName)
                                @if($columnName == 'title')
                                    <th>{{ $cpt->labels['title'] }}</th>
                                @elseif($columnName === 'booked')
                                    <th>결제상태</th>
                                    <th>예약금액</th>
                                @elseif(strpos($columnName, 'taxo_') !== false)
                                    <th>
                                        {{xe_trans(app('xe.category')->cates()->find(str_replace('taxo_', '', $columnName))->name)}}
                                    </th>
                                @else
                                    <th>{{ xe_trans($column_labels[$columnName]) }}</th>
                                @endif
                            @endforeach
                                <th>상태</th>
                            </tr>
                            </thead>
                            <tbody>
                            @if ($cptDocs->count() == 0)
                                <tr>
                                    <td colspan="{{ count($config['listColumns']) + 3 }}" style="padding:40px 0; text-align: center;">게시물이 없습니다.</td>
                                </tr>
                            @endif
                            @if ($cptDocs->count() > 0)
                                @foreach($cptDocs as $doc)
                                <tr>
                                    <td><input type="checkbox" name="id[]" class="__xe_checkbox" value="{{ $doc->id }}"></td>
                                    <td>{{ $doc->seq }}</td>
                                    @foreach($config['listColumns'] as $columnName)
                                        @if($columnName === 'booked')
                                            @if (($fieldType = XeDynamicField::get('documents_'.$cpt->cpt_id, $columnName)) !== null)
                                                <td style="padding:8px;">
                                                    {!! $fieldType->getSkin()->status($columnName, $doc->getAttributes()) !!}
                                                </td>
                                                <td style="padding:8px;">
                                                    {!! number_format($fieldType->getSkin()->price($columnName, $doc->getAttributes())) !!}
                                                </td>
                                            @else
                                                <td style="padding:8px;">
                                                    {!! $doc->{$columnName} !!}
                                                </td>
                                            @endif
                                        @elseif(strpos($columnName, 'taxo_') !== false)
                                            <td>
                                                @php
                                                    $finedCategories = app('overcode.df.taxonomyHandler')->getDocumentSelectTaxonomyItems((int) str_replace('taxo_', '', $columnName), $doc->id);
                                                @endphp
                                                @if(count($finedCategories) > 0 && $finedCategories[0] !== null)
                                                    @php
                                                        $target_id = $finedCategories[0]->id;
                                                        $finedCategoryIds = array_column($finedCategories->toArray(), 'id');
                                                        $findedCategoryIndex = 0;
                                                    @endphp
                                                    @foreach($finedCategories as $finedCategory)
                                                        @php
                                                            if(!$finedCategory) continue;
                                                            $finedCategoryIdsText = implode(',',array_slice($finedCategoryIds, 0, $findedCategoryIndex + 1));
                                                        @endphp
                                                        <a class="badge_a_tag" href="#" onclick="return false;">
                                                            <span class="xe-badge xe-primary-outline cursor"
                                                                  onclick="clickTaxonomyBadge({{(int) str_replace('taxo_', '', $columnName)}}, '{{$finedCategoryIdsText}}')">
                                                                {{xe_trans($finedCategory->word)}}
                                                            </span>
                                                        </a>
                                                        @php
                                                            $findedCategoryIndex++;
                                                        @endphp
                                                    @endforeach
                                                @else
                                                    <span class="xe-badge xe-danger-outline">선택없음</span>
                                                @endif
                                            </td>
                                        @else
                                            <td style="padding:8px;">
                                                @if ($columnName === 'title')
                                                    <a href="{{ route('dyFac.setting.'.$cpt->cpt_id, ['type' => 'edit', 'doc_id' => $doc->id]) }}" class="xe-btn xe-btn-positive-outline">
                                                        {!! $doc->title == null ? '<span style="font-style: italic; color:#999;">[제목없음]</span>' : $doc->title !!}
                                                    </a>
                                                @elseif ($columnName === 'writer')
                                                    @if ($doc->user !== null)
                                                        {{ $doc->user->getDisplayName() }}
                                                    @else
                                                        Guest
                                                    @endif
                                                @elseif ($columnName === 'assent_count')
                                                    {{ $doc->assent_count }}
                                                @elseif ($columnName === 'dissent_count')
                                                    {{ $doc->dissent_count }}
                                                @elseif ($columnName === 'read_count')
                                                    {{ $doc->read_count }}
                                                @elseif ($columnName === 'created_at')
                                                    {{ $doc->created_at->format('Y-m-d H:i') }}
                                                @elseif ($columnName === 'updated_at')
                                                    {{ $doc->updated_at->format('Y-m-d H:i') }}
                                                @else
                                                    @if (($fieldType = XeDynamicField::get('documents_'.$cpt->cpt_id, $columnName)) !== null)
                                                        <div class="xe-list-board-list__dynamic-field xe-list-board-list__dynamic-field-{{ $columnName }} xe-list-board-list__mobile-style">
                                                            <span class="sr-only">{{ xe_trans($column_labels[$columnName]) }}</span>
                                                            @if($fieldType->getConfig()->get('r_instance_id') && $fieldType->getConfig()->get('r_instance_id') === 'user')
                                                                <!-- superRelate 타켓이 User일 경우 -->
                                                                @if(method_exists($fieldType,'getSettingListUserItem'))
                                                                    {!! $fieldType->getSettingListUserItem($columnName, $doc) !!}
                                                                @else
                                                                    {!! $fieldType->getSkin()->output($columnName, $doc->getAttributes()) !!}
                                                                @endif

                                                            @else

                                                                @if(method_exists($fieldType,'getSettingListItem'))
                                                                    {!! $fieldType->getSettingListItem($columnName, $doc) !!}
                                                                @else
                                                                    {!! $fieldType->getSkin()->output($columnName, $doc->getAttributes()) !!}
                                                                @endif

                                                            @endif
                                                        </div>
                                                    @else
                                                        {!! $doc->{$columnName} !!}
                                                    @endif
                                                @endif
                                            </td>
                                        @endif
                                    @endforeach
                                    <td>
                                        @if($doc->isTemp() === true)<span class="xe-badge xe-warning">임시</span>
                                        @elseif($doc->isPrivate() === true)<span class="xe-badge xe-black">비공개</span>
                                        @elseif($doc->isPublic() === true && $doc->isPublished() === true)<span class="xe-badge xe-success">발행</span>
                                        @elseif($doc->isPublic() === true && $doc->isPublishReserved() === true)<span class="xe-badge xe-primary">예약</span>
                                        @endif

                                    </td>
                                </tr>
                                @endforeach
                            @endif
                            </tbody>
                        </table>
                    </form>
                </div>

                @if ($cptDocs->count() > 0)
                    <div class="panel-footer">
                        <div class="text-center" style="padding: 24px 0;">
                            <nav>
                                {!! $cptDocs->render() !!}
                            </nav>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function ($) {
        $('.__xe_check_all').click(function () {
            if ($(this).is(':checked')) {
                $('input.__xe_checkbox').prop('checked', true);
            } else {
                $('input.__xe_checkbox').prop('checked', false);
            }
        });

        $('.__xe_function_buttons .__xe_button').click(function (e) {
            e.preventDefault();

            var mode = $(this).attr('data-mode'), flag = false;

            $('input.__xe_checkbox').each(function () {
                if ($(this).is(':checked')) {
                    flag = true;
                }
            });

            if (flag !== true) {
                alert('select document');
                return;
            }

            var $f = $('.__xe_form_list');
            $('<input>').attr('type', 'hidden').attr('name', 'redirect').val(location.href).appendTo($f);

            eval('actions.' + mode + '($f)');
        });

        $('.__xe_btn_search_target .dropdown-menu a').click(function (e) {
            e.preventDefault();

            $('[name="search_target"]').val($(this).attr('value'));
            $('.__xe_btn_search_target .__xe_text').text($(this).text());

            $(this).closest('.dropdown-menu').find('li').removeClass('active');
            $(this).closest('li').addClass('active');
        });

        $('.__xe_btn_per_page .dropdown-menu a').click(function (e) {
            e.preventDefault();

            $('[name="perPage"]').val($(this).attr('value'));
            $('.__xe_btn_per_page .__xe_text').text($(this).text());

            $(this).closest('.dropdown-menu').find('li').removeClass('active');
            $(this).closest('li').addClass('active');

            $('#__xe_search_form').submit();
        });

        $('.__xe_btn_order_type .dropdown-menu a').click(function (e) {
            e.preventDefault();

            $('[name="order_type"]').val($(this).attr('value'));
            $('.__xe_btn_order_type .__xe_text').text($(this).text());

            $(this).closest('.dropdown-menu').find('li').removeClass('active');
            $(this).closest('li').addClass('active');

            $('#__xe_search_form').submit();
        });
    });

    var actions = {
        trash: function ($f) {
            $f.attr('action', '{{ route('dyFac.setting.trash_cpt_documents') }}');
            send($f);
        }
    };

    var send = function($f) {
        if(confirm('선택한 글들을 휴지통으로 이동하시겠습니까?')) {
            var url = $f.attr('action'),
                params = $f.serialize();

            XE.ajax({
                type: 'post',
                dataType: 'json',
                data: params,
                url: url,
                success: function (response) {
                    document.location.reload();
                }
            });
        }
    }

    function searchTaxoOr(e) {
        $('[name="taxOr"]').val($(e).attr('value'));
        $('#__xe_search_form').submit();
    }

    function clickTaxonomyBadge(category_id, id) {
        var ids = JSON.parse("[" + id + "]");
        var str = '';
        for(let i = 0; i < document.getElementsByClassName('taxo_' + category_id + '_selected_child').length; i++) {
            if(document.getElementsByClassName('taxo_' + category_id + '_selected_child')[i])
                document.getElementsByClassName('taxo_' + category_id + '_selected_child')[i].innerHTML = '';
        }
        for(let i = 0; i < ids.length; i++) {
            if(i === 0) {
                if(document.getElementById('taxo_' + category_id + '_selected'))
                    document.getElementById('taxo_' + category_id + '_selected').innerHTML = '';
                str += `<input type="hidden" name="${'taxo_' + category_id}[]" value="${ids[i]}" />`;
            } else {
                str += `<input type="hidden" name="${'taxo_' + category_id}[]" value="${ids[i]}" />`;
            }
        }
        document.getElementById('click_badge_taxonomy').innerHTML = str;
        $('#__xe_search_form').submit();
    }

    function searchTaxonomy(e, key) {
        $('[id="taxo_' + key + '"]').val($(e).attr('value'));
        $('.taxo_'+key+'_xe_text').text($(e).text());
        $(e).closest('.dropdown-menu').find('li').removeClass('active');
        $(e).closest('li').addClass('active');

        if(document.getElementById('taxo_' + key + '_selected'))
            document.getElementById('taxo_' + key + '_selected').innerHTML = '';

        for(let i = 0; i < document.getElementsByClassName('taxo_' + key + '_selected_child').length; i++) {
            if(document.getElementsByClassName('taxo_' + key + '_selected_child')[i])
                document.getElementsByClassName('taxo_' + key + '_selected_child')[i].innerHTML = '';
        }

        var str = `<input type="hidden" name="${'taxo_' + key}[]" value="${ $(e).attr('value') }" />`;
        document.getElementById('taxo_' + key + '_selected').innerHTML = str;

        $('#__xe_search_form').submit();
    }

    function searchChildTaxonomy(e, key, child) {
        $('[id="taxo_' + key + '_' + child + '_child"]').val($(e).attr('value'));
        $('.taxo_'+key+'_'+child+'_xe_text').text($(e).text());
        $(e).closest('.dropdown-menu').find('li').removeClass('active');
        $(e).closest('li').addClass('active');
        $('#__xe_search_form').submit();
    }

    function uploadCSV(item) {
        if($('input[name=csv_file]').val()) {
            $('#uploadCSV').attr('action', "{{route('dyFac.setting.uploadCSV')}}");
            $('#uploadCSV').submit();
        }
    }

    function downloadCSV() {
        var downloadUrl = '{{route('dyFac.setting.downloadCSV', ['cpt_id' => $cpt->cpt_id])}}';
        var defaultUrl = "{{route('dyFac.setting.'.$cpt->cpt_id)}}";
        $('#__xe_search_form').attr('action', downloadUrl);
        $('#__xe_search_form').submit();
        $('#__xe_search_form').attr('action', defaultUrl);
    }
</script>

