@section('page_title')
    <h2>[{{ $cpt['cpt_name'] }}] 카테고리</h2>
@endsection
<ul class="nav nav-tabs">
    <li><a href="{{ route('dyFac.setting.edit', [ 'cpt_id' => $cpt['cpt_id']]) }}">기본정보</a></li>
    <li><a href="{{ route('dyFac.setting.create_extra', ['cpt_id' => $cpt['cpt_id']]) }}">확장필드</a></li>
    <li class="active"><a href="#">카테고리</a></li>
</ul>

<div class="row">
    <div class="col-sm-12">
        <div class="panel-group">
            <div class="panel">
                <div class="panel-body">
                    <form method="post" action="{{ route('dyFac.setting.store_category', ['cpt_id' => $cpt['cpt_id']]) }}">
                        {!! csrf_field() !!}

                        <span>분류 이름</span>
                        {!! uio('langText', ['name'=>'name']) !!}

                        <button type="submit" class="xe-btn">생성</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
