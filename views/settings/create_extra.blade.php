@section('page_title')
    <h2>[{{ $cpt['cpt_name'] }}] 확장 필드 관리</h2>
@endsection
<ul class="nav nav-tabs">
    <li><a href="{{ route('dyFac.setting.edit', [ 'cpt_id' => $cpt['cpt_id']]) }}">기본정보</a></li>
    <li class="active"><a href="#">확장필드</a></li>
    <li><a href="{{ route('dyFac.setting.edit_category', ['cpt_id' => $cpt['cpt_id']]) }}">카테고리</a></li>
</ul>
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
