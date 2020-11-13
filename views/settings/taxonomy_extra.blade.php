@section('page_title')
    <h2>{{ xe_trans($category->name)}} - 확장필드</h2>
@endsection
<ul class="nav nav-tabs">
    <li><a href="{{ route('dyFac.setting.create_taxonomy', ['tax_id' => $cateExtra->category_id]) }}">기본정보</a></li>
    <li class="active"><a href="{{ route('dyFac.setting.taxonomy_extra', ['category_slug' => $cateExtra->slug]) }}">확장필드</a></li>
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
