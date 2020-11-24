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
                <div class="panel-heading"><h4>입력 필드</h4></div>
                <div class="panel-body">
                    @foreach($cptConfig['formColumns'] as $columnName)
                        @if($columnName === 'title')
                            {{--<div class="form-group">
                                <label for="">제목</label>
                                <input type="text" class="form-control" id="title" name="title" placeholder="{{ sprintf($cpt->labels['here_title'], $cpt->cpt_name) }}">
                            </div>--}}
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
                                ], $item->id) !!}
                            </div>
                        @else
                            @if(isset($dynamicFieldsById[$columnName]) && $dynamicFieldsById[$columnName]->get('use') == true)
                                <div class="__xe_{{$columnName}} __xe_section">
                                    {!! df_edit($cptConfig->get('documentGroup'), $columnName, $item->getAttributes()) !!}
                                </div>
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
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="xi-download"></i>저장</button>
        </div>

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
    </div>
</form>
{{ XeFrontend::js('assets/vendor/jqueryui/jquery-ui.min.js')->load() }}
