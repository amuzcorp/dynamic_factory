@section('page_title')
    <h2>{{ $cpt['cpt_name'] }} - 기본정보</h2>
@endsection
@php
    $readonly = $cpt['is_made_plugin'];
@endphp

@include('dynamic_factory::views.settings.tab')

            <!-- Main content -->
    <div class="row">
        <div class="col-sm-12">
            <div class="panel-group">
                <div class="panel">
                    <div class="panel-heading">
                        <div class="pull-left">
                            <h3 class="panel-title">{{xe_trans('xe::permission')}}</h3>
                        </div>
                    </div>
                    <form method="post" id="board_manage_form" action="{{route('dyFac.setting.update_permission', ['cpt_id' => $cpt['cpt_id']])}}">
                        <input type="hidden" name="_token" value="{{{ Session::token() }}}" />
                        <div id="collapseOne" class="panel-collapse collapse in">
                            <div class="panel-body">
                                <!-- Permission -->
                                @foreach ($perms as $perm)
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label for="">{{ $perm['title'] }} {{xe_trans('xe::permission')}}</label>
                                                <div class="well">
                                                    {!! uio('permission', $perm) !!}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

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
