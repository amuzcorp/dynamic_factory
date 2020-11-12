<ul class="nav nav-tabs">
    <li><a href="{{ route('dyFac.setting.create_taxonomy', ['tax_id' => 1]) }}">기본정보</a></li>
    <li class="active"><a href="{{ route('dyFac.setting.taxonomy_extra', ['category_slug' => 1]) }}">확장필드</a></li>
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
