@section('page_title')
    <div class="clearfix">
        <h2 class="pull-left">{{ $cpt->cpt_name }} 추가</h2>
    </div>
@endsection
<style>
    .modal-lg, .modal-xl {
        max-width: 80%;
    }
    .__xe-dropdown-form {
        margin-bottom: 20px;
    }
    @if($widgetConfig['label_active'] === 'disabled')
        label {
            display: none;
        }
    @endif
</style>
@php

if($widgetConfig['title_active'] && $widgetConfig['title_active'] === 'active') {
    $title = Request::old('title');
} else {
    $title = date('Y-m-d H:i:s');
}

@endphp
<div class="container">
    <form id="createForm" method="post" action="{{ route('dyFac.widget.rending_store_cpt_document') }}" enctype="multipart/form-data" target="createCptDocument">
        {!! csrf_field() !!}
        <input type="hidden" name="cpt_id" value="{{ $cpt->cpt_id }}" />
        <input type="hidden" name="after_work" value="{{ $widgetConfig['after_work'] }}" />
        <div class="row">
            {{--                        <div class="col-sm-8 col-md-8 col-lg-9 col-xl-9">--}}
            <div class="col-12">
                <div class="panel">
                    <div class="panel-body">
                        @foreach($cptConfig['formColumns'] as $columnName)
                            @if($columnName === 'title')
                                <div class="xe-list-board-body--header-item xe-list-board-body--header-title xf-col-md-12"
                                     @if(!$widgetConfig['title_active'] || $widgetConfig['title_active'] === 'disabled') style="display: none;" @endif >
                                    {!! uio('uiobject/df@doc_title', [
                                        'title' => $title,
                                        'slug' => Request::old('slug'),
                                        'titleClassName' => 'bd_input',
                                        'titleName' => $widgetConfig['title_label'],
                                        'cpt_id' => $cpt->cpt_id
                                    ]) !!}
                                </div>
                            @elseif($columnName === 'content')
                                <div class="form-group" @if(!$widgetConfig['content_active'] || $widgetConfig['content_active'] === 'disabled') style="display: none;" @endif >
                                    <label for="xeContentEditor">{{$widgetConfig['content_label']}}</label>
                                    {!! editor($cpt->cpt_id, [
                                        'content' => Request::old('content'),
                                        'cover' => true
                                    ]) !!}
                                </div>
                            @else
                                @if(isset($dynamicFieldsById[$columnName]) && $dynamicFieldsById[$columnName]->get('use') == true)
                                    {!! df_create($cptConfig->get('documentGroup'), $columnName, Request::all()) !!}
                                @endif
                            @endif
                        @endforeach
                        @if(!in_array('content', $cptConfig['formColumns']))
                            <input type="hidden" name="content" value="empty" />
                        @endif

                        @if(count($taxonomies) > 0)
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
                                                'items' => app('overcode.df.taxonomyHandler')->getCategoryItemsTree($taxonomy->id)
                                            ]) !!}
                                        </div>
                                    </div>
                                    <br>
                                @endforeach
                            </div>
                        @endif

                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="xi-download"></i>작성</button>
            </div>

            <div class="col-sm-4 col-md-4 col-lg-3 col-xl-3" style="display: none;">
                <div class="panel">
                    <div class="panel-heading"><h4>고급설정</h4></div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label>공개 속성</label>
                            <select class="form-control" name="cpt_status">
                                <option value="public">공개</option>
                                <option value="private">비공개</option>
                                <option value="temp">임시글</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>발행시각</label>
                            {!! uio('uiobject/df@datetime_picker', []) !!}
                        </div>
                        <div class="form-group">
                            <label>작성자</label>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

<iframe name="createCptDocument" src="{{ route('dyFac.document.rending_store_result', ['status' => 'wait', 'result' => [], 'after_work' => '']) }}" style="width: 0; height:0; border:none;">
</iframe>

<script>
    // enter key 입력시 submit 막기
    document.addEventListener('keydown', function(event) {
        if (event.keyCode === 13) {
            event.preventDefault();
        }
    }, true);
</script>
