<?php
$adminRating = \Auth::user()->admin_rating;
$cpt_id = $cpt->cpt_id;
?>
@section('page_title')
    <div class="clearfix">
        <h2 class="pull-left">{{ $cpt->cpt_name }} 수정</h2>
    </div>
@endsection
<style>
    .xe_content p, .cke_editable p {
        margin: 0 0 8px !important;
    }
</style>
<form method="post" action="{{ route('dyFac.setting.update_cpt_document') }}" enctype="multipart/form-data">
    {!! csrf_field() !!}
    <input type="hidden" name="cpt_id" value="{{ $cpt->cpt_id }}" />
    <input type="hidden" name="doc_id" value="{{ $item->id }}" />
    <div class="row">
        <div class="col-sm-8 col-md-8 col-lg-9 col-xl-10">
            <div class="panel">
                <div class="panel-body">
                    @foreach($cptConfig['formColumns'] as $columnName)
                        @if($columnName === 'title')
                            <div class="xe-list-board-body--header-item xe-list-board-body--header-title xf-col-md-12">
                                {!! uio('uiobject/df@doc_title', [
                                    'title' => Request::old('title', $item->title),
                                    'slug' => $item->getSlug(),
                                    'titleClassName' => 'bd_input',
                                    'cpt_id' => $cpt->cpt_id
                                ]) !!}
                            </div>
                        @elseif($columnName === 'content')
                            <div class="form-group">
                                <label for="xeContentEditor">내용</label>
                                {!! editor($cpt->cpt_id, [
                                    'content' => Request::old('content', $item->content),
                                    'cover' => true
                                ], $item->id, $thumb ? $thumb->df_thumbnail_file_id : null) !!}
                            </div>
                        @else
                            @if(isset($dynamicFieldsById[$columnName]) && $dynamicFieldsById[$columnName]->get('use') == true)
{{--                                <div class="__xe_{{$columnName}} __xe_section">--}}
                                    {!! df_edit($cptConfig->get('documentGroup'), $columnName, $item->getAttributes()) !!}
{{--                                </div>--}}
                                {{--
                                @foreach ($dynamicFields as $dynamicField)
                                    @if ($dynamicField->getConfig()->get('use') === true)
                                        {!! df_create($dynamicField->getConfig()->get('group'), $dynamicField->getConfig()->get('id'), Request::all()) !!}
                                    @endif
                                @endforeach
                                --}}
                            @endif
                        @endif
                    @endforeach
                    @if(!in_array('content', $cptConfig['formColumns']))
                        <input type="hidden" name="content" value="empty" />
                    @endif
                </div>
            </div>
        </div>

        <div class="col-sm-4 col-md-4 col-lg-3 col-xl-2">
            <div class="panel">
                <div class="panel-heading"><h4>고급설정</h4></div>
                <div class="panel-body">
                    <div class="form-group">
                        <label>공개 속성</label>
                        <select class="form-control" name="cpt_status">
                            <option value="public" @if ($item->isPublic() === true) selected @endif>공개</option>
                            <option value="private" @if ($item->isPrivate() === true) selected @endif>비공개</option>
                            <option value="temp" @if ($item->isTemp() === true) selected @endif>임시</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>발행시각</label>
                        {!! uio('uiobject/df@datetime_picker', ['published_at' => $item->published_at]) !!}
                    </div>
                    <div class="form-group">
                        <label>작성자</label>
                        {!! uio('uiobject/df@user_select', [
                            'display_name' => $item->writer,
                            'login_id' => get_user_login_id($item->getUserId())
                        ]) !!}
                    </div>
                </div>
            </div>
            @if($adminRating !== 'admin-corp' && $adminRating !== 'normal')
                @if(!strpos($cpt_id, 'log'))
                <div class="text-right" style="margin-bottom:15px;">
                    <div class="pull-left">
                        <button type="button" id="trashBtn" class="xe-btn xe-btn-danger-outline" data-url="{{ route('dyFac.setting.trash_cpt_documents', ['id' => $item->id]) }}"><i class="xi-trash"></i> 휴지통</button>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="xi-redo"></i> 업데이트</button>
                </div>
                @endif
            @endif
            @if(count($taxonomies) > 0)
                <div class="panel">
                    <div class="panel-heading"><h4>카테고리</h4></div>
                    <div class="panel-body">
                        <div class="components-base-control">
                            <div class="components-base-control__field">
                                @foreach ($taxonomies as $taxonomy)
                                    <label class="components-base-control__label">
                                        {{ xe_trans($taxonomy->name) }}
                                    </label>
                                    <div class="__taxonomy-field">
                                        <div class="components-base-control__field" data-required-title="{{ xe_trans($taxonomy->name) }}">
                                            {!! uio('uiobject/df@taxo_select', [
                                                'name' => app('overcode.df.taxonomyHandler')->getTaxonomyItemAttributeName($taxonomy->id),
                                                'label' => xe_trans($taxonomy->name),
                                                'template' => $taxonomy->extra->template,
                                                'items' => app('overcode.df.taxonomyHandler')->getCategoryItemsTree($taxonomy->id),
                                                'value' => isset($category_items[$taxonomy->id]) ? $category_items[$taxonomy->id] : ''
                                            ]) !!}
                                        </div>
                                    </div>
                                    <br>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</form>

@if($cpt->use_comment == "Y" && $cpt->show_admin_comment == "Y")
    <div class="row">
        <div class="col-sm-8">
            <div class="panel board_comment">
                <div class="panel-body">
                    <div class="__xe_comment ">
                        {!! uio('comment', ['target' => $item]) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif


{{ XeFrontend::js('assets/vendor/jqueryui/jquery-ui.min.js')->load() }}

<script>
    $(document).ready(function() {
        // 완전 삭제
        $('#delBtn').click(function() {
            let delete_url = $(this).data('url');
            if(confirm('삭제된 게시물은 복구할 수 없습니다. 계속하시겠습니까?')){
                XE.ajax({
                    type: 'post',
                    dataType: 'json',
                    url: delete_url,
                    success: function(response) {
                        document.location.href = "{{ route('dyFac.setting.'.$cpt->cpt_id) }}";
                    },
                    error: function(response) {
                        XE.toast('error', '삭제에 실패하였습니다.');
                    }
                });
            }
        });

        // 휴지통으로 이동
        $('#trashBtn').click(function() {
            let delete_url = $(this).data('url');
            if(confirm('게시물을 휴지통으로 이동합니다. 계속하시겠습니까?')) {
                XE.ajax({
                    type: 'post',
                    dataType: 'json',
                    url: delete_url,
                    success: function (response) {
                        document.location.href = "{{ route('dyFac.setting.'.$cpt->cpt_id) }}";
                    },
                    error: function (response) {
                        XE.toast('error', '휴지통 이동에 실패하였습니다.');
                    }
                });
            }
        });
    });

    // enter key 입력시 submit 막기
    document.addEventListener('keydown', function(event) {
        if (event.keyCode === 13) {
            event.preventDefault();
        };
    }, true);
</script>
