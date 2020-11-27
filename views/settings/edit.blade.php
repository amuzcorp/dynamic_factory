@section('page_title')
    <h2>{{ $cpt['cpt_name'] }} - 기본정보</h2>
@endsection
@php
    $readonly = $cpt['is_made_plugin'];
@endphp

@include('dynamic_factory::views.settings.tab')
<div class="row">
    <div class="col-sm-12">
        <form method="post" action="{{ route('dyFac.setting.update') }}">
            {!! csrf_field() !!}
            <input type="hidden" name="cpt_id" value="{{ $cpt['cpt_id'] }}" readonly />
            <div class="panel-group">
                <div class="panel">
                    <div class="panel-heading">
                        <div class="pull-left"><h4>이름 및 설명</h4></div>
                    </div>
                    <div class="panel-body">
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">ID (필수)</label>
                            <div class="col-sm-5">
                                <input type="text" class="form-control" value="{{ $cpt['cpt_id'] }}" disabled="disabled">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">유형 이름 (필수)</label>
                            <div class="col-sm-5">
                                <input type="text" class="form-control" name="cpt_name" value="{{ $cpt['cpt_name'] }}" @if($readonly) disabled="disabled" @endif>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">메뉴 이름 (필수)</label>
                            <div class="col-sm-5">
                                <input type="text" class="form-control" name="menu_name" value="{{ $cpt['menu_name'] }}" @if($readonly) disabled="disabled" @endif>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">메뉴 순서 (필수)</label>
                            <div class="col-sm-5">
                                <input type="text" class="form-control" name="menu_order" value="{{ $cpt['menu_order'] }}" @if($readonly) disabled="disabled" @endif>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">상위 메뉴 (필수)</label>
                            <div class="col-sm-5">
                                <select class="form-control" name="menu_path" @if($readonly) disabled="disabled" @endif>
                                    <option value="">- 최상위 -</option>
                                    @foreach($menus as $menu)
                                        <option value="{{ $menu['menu_path'] }}" @if($cpt['menu_path'] == $menu['menu_path']) selected="selected"@endif>{{ xe_trans($menu['title']) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">설명</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="description" value="{{ $cpt['description'] }}" @if($readonly) disabled="disabled" @endif>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel accordion" id="accordion_ex">
                    <div class="panel-heading clearfix" id="headingOne" data-toggle="collapse" data-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                        <div class="pull-left"><h4>레이블</h4></div>
                        <div class="pull-right"><h4><i class="xi-angle-down"></i></h4></div>
                    </div>
                    <div id="collapseOne" class="collapse" style="overflow: hidden;">
                        <div class="panel-body">
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">새로 추가</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="labels[new_add]" value="{{ $cpt['labels']['new_add'] }}" @if($readonly) disabled="disabled" @endif>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">새 항목 추가</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="labels[new_add_cpt]" value="{{ $cpt['labels']['new_add_cpt'] }}" @if($readonly) disabled="disabled" @endif>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">항목 편집</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="labels[cpt_edit]" value="{{ $cpt['labels']['cpt_edit'] }}" @if($readonly) disabled="disabled" @endif>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">새 항목</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="labels[new_cpt]" value="{{ $cpt['labels']['new_cpt'] }}" @if($readonly) disabled="disabled" @endif>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">항목 보기</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="labels[cpt_view]" value="{{ $cpt['labels']['cpt_view'] }}" @if($readonly) disabled="disabled" @endif>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">항목 검색</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="labels[cpt_search]" value="{{ $cpt['labels']['cpt_search'] }}" @if($readonly) disabled="disabled" @endif>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">찾을 수 없음</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="labels[no_search]" value="{{ $cpt['labels']['no_search'] }}" @if($readonly) disabled="disabled" @endif>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">휴지통에서 찾을 수 없음</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="labels[no_trash]" value="{{ $cpt['labels']['no_trash'] }}" @if($readonly) disabled="disabled" @endif>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">상위 항목 설명</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="labels[parent_txt]" value="{{ $cpt['labels']['parent_txt'] }}" @if($readonly) disabled="disabled" @endif>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">모든 항목</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="labels[all_cpt]" value="{{ $cpt['labels']['all_cpt'] }}" @if($readonly) disabled="disabled" @endif>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">여기에 제목 입력</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="labels[here_title]" value="{{ $cpt['labels']['here_title'] }}" @if($readonly) disabled="disabled" @endif>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @if(!$readonly)
            <button type="submit" class="btn btn-primary"><i class="xi-download"></i>저장</button>
            @endif
        </form>
    </div>
</div>
