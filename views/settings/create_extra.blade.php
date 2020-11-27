@section('page_title')
    <h2>{{ $cpt['cpt_name'] }} - 확장 필드</h2>
@endsection

@include('dynamic_factory::views.settings.tab')
<div class="row">
    <div class="col-sm-12">
        <div class="panel-group">
            <div class="panel">
                <div class="panel-body">
                    <div class="clearfix">
                        {!! $dynamicFieldSection !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
