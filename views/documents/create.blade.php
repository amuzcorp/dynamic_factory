@section('page_title')
    <div class="clearfix">
        <h2 class="pull-left">{{ $cpt->cpt_name }} 추가</h2>
    </div>
@endsection

<form method="post" action="{{ route('dyFac.setting.store_cpt_document') }}" enctype="multipart/form-data">
    {!! csrf_field() !!}
    <input type="hidden" name="cpt_id" value="{{ $cpt->cpt_id }}" />
    <div class="row">
        <div class="col-sm-8">
            <div class="panel">
                <div class="panel-heading"><h4>기본 정보</h4></div>
                <div class="panel-body">
                    <div class="form-group">
                        <label for="">제목</label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="{{ sprintf($cpt->labels['here_title'], $cpt->cpt_name) }}">
                    </div>
                    <div class="form-group">
                        <label for="xeContentEditor">내용</label>
                        {!! editor($cpt->cpt_id, [
                            'content' => Request::old('content'),
                            'cover' => true
                        ]) !!}
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-heading"><h4>확장 필드</h4></div>
                <div class="panel-body">
                    @foreach ($dynamicFields as $dynamicField)
                        @if ($dynamicField->getConfig()->get('use') === true)
                            {!! df_create($dynamicField->getConfig()->get('group'), $dynamicField->getConfig()->get('id'), Request::all()) !!}
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
                                            'name' => $taxonomy->id,
                                            'label' => xe_trans($taxonomy->name),
                                            'template' => $taxonomy->getAttribute('extra')->template,
                                            'items' => app('overcode.df.taxonomyHandler')->getTaxonomyItems($taxonomy->id),
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
