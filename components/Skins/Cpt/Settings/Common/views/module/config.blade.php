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
                                                    <label>{{xe_trans('board::perPage')}} <small>{{xe_trans('board::perPageDescription')}} </small></label>
                                                    <div class="checkbox pull-right">
                                                        <label>
                                                            <input type="checkbox" class="inheritCheck" data-target="perPage" @if($config->getPure('perPage') === null) checked="checked" @endif />{{ xe_trans('xe::inheritMode') }}
                                                        </label>
                                                    </div>
                                                </div>
                                                <input type="text" id="" name="perPage" class="form-control" value="{{Request::old('perPage', $config->get('perPage'))}}" @if($config->getPure('perPage') === null) disabled="disabled" @endif/>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <div class="clearfix">
                                                    <label>{{xe_trans('xe::recommend')}} </label>
                                                    <div class="checkbox pull-right">
                                                        <label>
                                                            <input type="checkbox" class="inheritCheck" data-target="assent" @if($config->getPure('assent') === null) checked="checked" @endif />{{ xe_trans('xe::inheritMode') }}
                                                        </label>
                                                    </div>
                                                </div>
                                                <select id="" name="assent" class="form-control" @if($config->getPure('assent') === null) disabled="disabled" @endif>
                                                    <option value="true" {!! $config->get('assent') == true ? 'selected="selected"' : '' !!} >{{xe_trans('xe::use')}}</option>
                                                    <option value="false" {!! $config->get('assent') == false ? 'selected="selected"' : '' !!} >{{xe_trans('xe::disuse')}}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <div class="clearfix">
                                                    <label>{{xe_trans('xe::discommend')}} </label>
                                                    <div class="checkbox pull-right">
                                                        <label>
                                                            <input type="checkbox" class="inheritCheck" data-target="dissent" @if($config->getPure('dissent') === null) checked="checked" @endif />{{ xe_trans('xe::inheritMode') }}
                                                        </label>
                                                    </div>
                                                </div>
                                                <select id="" name="dissent" class="form-control" @if($config->getPure('dissent') === null) disabled="disabled" @endif>
                                                    <option value="true" {!! $config->get('dissent') == true ? 'selected="selected"' : '' !!} >{{xe_trans('xe::use')}}</option>
                                                    <option value="false" {!! $config->get('dissent') == false ? 'selected="selected"' : '' !!} >{{xe_trans('xe::disuse')}}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <div class="clearfix">
                                                    <label>{{xe_trans('board::useConsultation')}} <small>관리권한이 없는 회원은 자신이 쓴 글만 보이도록 하는 기능입니다.<br>단 상담기능 사용시 비회원 글쓰기는 자동으로 금지됩니다. <span class="text-danger">그룹상담보다 우선 됩니다.</span></small></label>
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

                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <div class="clearfix">
                                                    <label>그룹상담 <small>관리권한이 없는 회원은 회원이 소속된 그룹이 쓴 글만 보이도록 하는 기능입니다.<br> 상담기능을 사용 안함으로 설정 시 적용됩니다.  </small></label>
                                                    <div class="checkbox pull-right">
                                                        <label>
                                                            <input type="checkbox" class="inheritCheck" data-target="useGroupConsultation" @if($config->getPure('useGroupConsultation') === null) checked="checked" @endif />비활성화
                                                        </label>
                                                    </div>
                                                </div>
                                                <select id="" name="useGroupConsultation" class="form-control" @if($config->getPure('useGroupConsultation') === null) disabled="disabled" @endif>
                                                    <option value="true" {!! $config->get('useGroupConsultation') == true ? 'selected="selected"' : '' !!} >{{xe_trans('xe::use')}}</option>
                                                    <option value="false" {!! $config->get('useGroupConsultation') == false ? 'selected="selected"' : '' !!} >{{xe_trans('xe::disuse')}}</option>
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
