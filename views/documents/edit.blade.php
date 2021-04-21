@section('page_title')
    <div class="clearfix">
        <h2 class="pull-left">{{ $cpt->cpt_name }} 수정</h2>
    </div>
@endsection

<form method="post" action="{{ route('dyFac.setting.update_cpt_document') }}" enctype="multipart/form-data">
    {!! csrf_field() !!}
    <input type="hidden" name="cpt_id" value="{{ $cpt->cpt_id }}" />
    <input type="hidden" name="doc_id" value="{{ $item->id }}" />
    <div class="row">
        <div class="col-sm-8">
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
            <div class="col-12">
                <div class="clearfix">
                    <button type="submit" class="btn btn-primary"><i class="xi-download"></i>저장</button>
                    <button type="button" id="delBtn" class="btn btn-danger pull-right" data-url="{{ route('dyFac.setting.remove_cpt_documents', ['id' => $item->id]) }}"><i class="xi-close-square"></i> 완전 삭제</button>
                    <button type="button" id="trashBtn" class="btn btn-warning pull-right" data-url="{{ route('dyFac.setting.trash_cpt_documents', ['id' => $item->id]) }}"><i class="xi-trash"></i> 휴지통</button>
                </div>
            </div>
        </div>

        <div class="col-sm-4 col-md-3 col-lg-2">
            <div class="panel">
                <div class="panel-heading"><h4>고급설정</h4></div>
                <div class="panel-body">
                    <div class="form-group">
                        <label>공개 속성</label>
                        <select class="form-control">
                            <option value="public">발행</option>
                            <option value="private">비공개</option>
                            <option value="temp">임시글</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>발행시각</label>
                        <input id="datetimepicker" type="text" class="form-control">
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
        </div>

        @if(count($taxonomies) > 0)
        <div class="col-sm-4">
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
        </div>
        @endif
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
</script>
