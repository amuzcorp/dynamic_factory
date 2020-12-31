<div class="panel-group" id="accordion">
    <div class="panel" id="panel2">
        <div class="panel-heading">
            <div class="pull-left">
                <h4 class="panel-title">사용자 정의 문서 기본 설정</h4>
            </div>
            <div class="pull-right">
                <a data-toggle="collapse" data-parent="#accordion" href="#collapseMenuType" class="btn-link panel-toggle pull-right"><i class="xi-angle-down"></i><i class="xi-angle-up"></i><span class="sr-only">{{xe_trans('xe::fold')}}</span></a>
            </div>
        </div>
        <div id="collapseMenuType" class="panel-collapse">
            <div class="panel-body">
                <div class="form-group">
                    <label>사용할 사용자 정의 문서</label>
                    <select name="cpt_id" class="form-control">
                        <option value="">선택</option>
                        @foreach($cpts as $cpt)
                            <option value="{{ $cpt->cpt_id }}" @if($config->get('cpt_id') === $cpt->cpt_id) selected="selected" @endif>{{ $cpt->cpt_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
