@section('page_title')
    <h2>상세설정</h2>
@endsection

@section('page_description')@endsection

<!-- Main content -->
<div class="row">
    <div class="col-sm-12">
        <div class="panel-group">
            <div class="panel">
                <div class="panel-heading">
                    <div class="pull-left">
                        <h3 class="panel-title">글로벌 상세설정</h3>
                    </div>
                </div>
                <form method="post" id="board_manage_form" action="{!! $cptUrlHandler->managerUrl('global.config.update') !!}">
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
                                                </div>
                                                <input type="text" id="" name="perPage" class="form-control" value="{{Request::old('perPage', $config->get('perPage'))}}"/>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <div class="clearfix">
                                                    <label>{{xe_trans('xe::recommend')}} </label>
                                                </div>
                                                <select id="" name="assent" class="form-control">
                                                    <option value="true" {!! $config->get('assent') == true ? 'selected="selected"' : '' !!} >{{xe_trans('xe::use')}}</option>
                                                    <option value="false" {!! $config->get('assent') == false ? 'selected="selected"' : '' !!} >{{xe_trans('xe::disuse')}}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <div class="clearfix">
                                                    <label>{{xe_trans('xe::discommend')}} </label>
                                                </div>
                                                <select id="" name="dissent" class="form-control">
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
                                                    <label>{{xe_trans('board::useConsultation')}} <small>{{xe_trans('board::useConsultationDescription')}} </small></label>
                                                </div>
                                                <select id="" name="useConsultation" class="form-control">
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
