@section('page_title')
    <h2>상세설정</h2>
@endsection

@section('page_description')@endsection


<div class="row">
    <div class="col-sm-12">
        <div class="panel-group">
            <div class="panel">
                <div class="panel-heading">
                    <div class="pull-left">
                        <h3 class="panel-title">상세설정</h3>
                        <small><a href="{{$cptUrlHandler->managerUrl('global.config')}}" target="_blank">{{xe_trans('xe::moveToParentSettingPage')}}</a></small>
                    </div>
                </div>
                <form method="post" id="board_manage_form" action="{!! $cptUrlHandler->managerUrl('config.update', ['instanceId' => $instanceId]) !!}">
                    <input type="hidden" name="_token" value="{{{ Session::token() }}}" />
                    <div id="collapseOne" class="panel-collapse collapse in">
                        <div class="panel-body">
                            <div class="panel">

                                <div class="panel-heading">
                                    <div class="pull-left">
                                        <h4 class="panel-title">{{xe_trans('xe::settings')}}</h4>
                                    </div>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <div class="clearfix">
                                                    <label>{{xe_trans('board::useConsultation')}} <small>{{xe_trans('board::useConsultationDescription')}} </small></label>
                                                    <div class="checkbox pull-right">
                                                        <label>
                                                            <input type="checkbox" class="inheritCheck" data-target="useConsultation" @if($config->getPure('useConsultation') === null) checked="checked" @endif />{{ xe_trans('xe::inheritMode') }}
                                                        </label>
                                                    </div>
                                                </div>
                                                <select id="" name="useConsultation" class="form-control" @if($config->getPure('useConsultation') === null) disabled="disabled" @endif>
                                                    <option value="true" {!! $config->get('useConsultation') == true ? 'selected="selected"' : '' !!} >{{xe_trans('xe::use')}}</option>
                                                    <option value="false" {!! $config->get('useConsultation') == false ? 'selected="selected"' : '' !!} >{{xe_trans('xe::disuse')}}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="panel-footer">
                            <div class="pull-right">
                                <button type="submit" class="btn btn-primary"><i class="xi-download"></i>{{xe_trans('xe::save')}}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
