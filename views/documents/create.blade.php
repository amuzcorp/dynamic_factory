@section('page_title')
    <div class="clearfix">
        <h2 class="pull-left">{{ $cpt->cpt_name }} 추가</h2>
    </div>
@endsection

<form method="post" action="{{ route('dyFac.setting.store_cpt_document') }}" enctype="multipart/form-data">
    {!! csrf_field() !!}
    <input type="hidden" name="cpt_id" value="{{ $cpt->cpt_id }}" />
    <div class="row">
        <div class="{{ (count($taxonomies) > 0) ? 'col-sm-8 col-md-9 col-lg-10' : 'col-sm-12'}}">
            <div class="panel">
                <div class="panel-body">
                @foreach($cptConfig['formColumns'] as $columnName)
                    @if($columnName === 'title')
                    <div class="xe-list-board-body--header-item xe-list-board-body--header-title xf-col-md-12">
                        {!! uio('uiobject/df@doc_title', [
                            'title' => Request::old('title'),
                            'slug' => Request::old('slug'),
                            'titleClassName' => 'bd_input',
                            'titleName' => $cpt->labels['title'],
                            'cpt_id' => $cpt->cpt_id
                        ]) !!}
                    </div>
                    @elseif($columnName === 'content')
                    <div class="form-group">
                        <label for="xeContentEditor">내용</label>
                        {!! editor($cpt->cpt_id, [
                            'content' => Request::old('content'),
                            'cover' => true
                        ]) !!}
                    </div>
                    @else
                        @if(isset($dynamicFieldsById[$columnName]) && $dynamicFieldsById[$columnName]->get('use') == true)
{{--                        <div class="__xe_{{$columnName}} __xe_section">--}}
                            {!! df_create($cptConfig->get('documentGroup'), $columnName, Request::all()) !!}
{{--                        </div>--}}
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
            <button type="submit" class="btn btn-primary"><i class="xi-download"></i>저장</button>
        </div>

        @if(count($taxonomies) > 0)
        <div class="col-sm-4 col-md-3 col-lg-2">
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
{{ XeFrontend::js('assets/vendor/jqueryui/jquery-ui.min.js')->load() }}
