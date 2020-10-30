@section('page_title')
    <h2>[{{ $cpt['cpt_name'] }}] 에디터</h2>
@endsection

@include('dynamic_factory::views.settings.tab')
<div class="row">
    <div class="col-sm-12">
        <div class="panel-group">
            <div class="panel">
                <div class="panel-heading">
                    <div class="pull-left">
                        <h3 class="panel-title">{{xe_trans('xe::editor')}}</h3>
                    </div>
                </div>
                <div id="collapseTwo" class="panel-collapse collapse in">
                    <div class="panel-body">
                        {!! $editorSection !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
