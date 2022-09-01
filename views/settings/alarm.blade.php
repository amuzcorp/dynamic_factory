@section('page_title')
    <h2>{{ $cpt['cpt_name'] }} - 알림 메일 전송</h2>
@endsection
@php
    $readonly = $cpt['is_made_plugin'];
@endphp

<style>
    /* The switch - the box around the slider */
    .switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
    }

    /* Hide default HTML checkbox */
    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    /* The slider */
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        -webkit-transition: .4s;
        transition: .4s;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        -webkit-transition: .4s;
        transition: .4s;
    }

    input:checked + .slider {
        background-color: #2196F3;
    }

    input:focus + .slider {
        box-shadow: 0 0 1px #2196F3;
    }

    input:checked + .slider:before {
        -webkit-transform: translateX(26px);
        -ms-transform: translateX(26px);
        transform: translateX(26px);
    }

    /* Rounded sliders */
    .slider.round {
        border-radius: 34px;
    }

    .slider.round:before {
        border-radius: 50%;
    }
</style>

@include('dynamic_factory::views.settings.tab')

            <!-- Main content -->
    <div class="row">
        <div class="col-sm-12">
            <div class="panel-group">
                <div class="panel">
                    <div class="panel-heading">
                        <div class="pull-left">
                            <h3 class="panel-title">알림</h3>
                        </div>
                    </div>
                    <form method="post" id="board_manage_form" action="{{route('dyFac.setting.update_alarm', ['cpt_id' => $cpt['cpt_id']])}}">
                        <input type="hidden" name="_token" value="{{{ Session::token() }}}" />
                        <div id="collapseOne" class="panel-collapse collapse in">
                            <div class="panel-body">
                                <!-- Permission -->
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <div class="clearfix">
                                                <label>알림 메일 사용<small> 문서가 작성될 때 알림 메일이 발송됩니다. </small></label>
                                            </div>
                                            <div class="well">
                                                <!-- Rounded switch -->
                                                <label class="switch">
                                                    <input type="checkbox" id="alarm_active"  @if($alarm_config['active'] == 'Y') checked @endif />
                                                    <span class="slider round"></span>
                                                    <input type="hidden" name="active" value="{{$alarm_config['active']}}" />
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <div class="clearfix">
                                                <label>제목<small> 메일에 표시될 제목을 입력해주세요. </small></label>
                                            </div>
                                            <div class="well">
                                                <input type="text" class="form-control" name="title" value="{{$alarm_config['title']}}"/>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <div class="clearfix">
                                                <label>내용<small> 메일에 작성될 내용을 입력해주세요. </small></label>
                                            </div>
                                            <div class="well">
                                                <textarea class="form-control" name="content">{{$alarm_config['content']}}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <div class="clearfix">
                                                <label>이메일 주소<small> 메일에 받을 이메일 주소를 입력해주세요. </small></label>
                                            </div>
                                            <div class="well">
                                                <input type="text" class="form-control" name="email" value="{{$alarm_config['email']}}"/>
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

<script>
    $(document).ready(function () {
        $("#alarm_active").change(function(){
            if($("#alarm_active").is(":checked")){
                console.log('Y');
                $('input[name=active]').val('Y');
            }else{
                console.log('N');
                $('input[name=active]').val('N');
            }
        });
    });
</script>
