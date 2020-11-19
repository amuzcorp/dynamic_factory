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
                    <p><span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span> For web master's<br>{{xe_trans('board::msgCannotChangeThisSetting')}}</p>

                    <label>Table Division </label>
                    <select name="division" class="form-control">
                        <option value="true">{{xe_trans('xe::use')}}</option>
                        <option value="false">{{xe_trans('xe::disuse')}}</option>
                    </select>

                </div>
                <div class="form-group">

                    <label>Revision</label>
                    <select name="revision" class="form-control">
                        <option value="true">{{xe_trans('xe::use')}}</option>
                        <option value="false">{{xe_trans('xe::disuse')}}</option>
                    </select>
                </div>

            </div>
        </div>
    </div>
</div>

