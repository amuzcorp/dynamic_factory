<div class="panel-group" id="accordion">
    <div class="panel" id="panel2">
        <div class="panel-heading">
            <div class="pull-left">
                <h4 class="panel-title">Taxonomy Archive 기본 설정</h4>
            </div>
            <div class="pull-right">
                <a data-toggle="collapse" data-parent="#accordion" href="#collapseMenuType" class="btn-link panel-toggle pull-right"><i class="xi-angle-down"></i><i class="xi-angle-up"></i><span class="sr-only">{{xe_trans('xe::fold')}}</span></a>
            </div>
        </div>
        <div id="collapseMenuType" class="panel-collapse">
            <div class="panel-body">
                <div class="form-group">
                    <label>사용할 카테고리</label>
                    <br>
                    @foreach($categoryExtras as $extra)
                    <label class="form-check-label" for="extra_{{ $extra->category_id }}">
                        <input class="form-check-input" type="checkbox" id="extra_{{ $extra->category_id }}" value="{{ $extra->category_id }}"> {{ xe_trans($extra->category->name) }}
                    </label>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

