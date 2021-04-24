@section('page_title')
    <h2>사용자 정의 문서 생성</h2>
@endsection
<div class="row">
    <div class="col-sm-12">
        <form method="post" action="{{ route('dyFac.setting.store_cpt') }}">
            {!! csrf_field() !!}
            <div class="panel-group">
                <div class="panel">
                    <div class="panel-heading">
                        <div class="pull-left"><h4>이름 및 설명</h4></div>
                    </div>
                    <div class="panel-body">
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">ID (필수)</label>
                            <div class="col-sm-5">
                                <input type="text" class="form-control" name="cpt_id" maxlength="36" placeholder="첫글자는 영문으로 시작하며 영문, 숫자, -, _ 외는 입력 할 수 없습니다.">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">유형 이름 (필수)</label>
                            <div class="col-sm-5">
                                <input type="text" class="form-control" name="cpt_name">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">메뉴 이름 (필수)</label>
                            <div class="col-sm-5">
                                <input type="text" class="form-control" name="menu_name">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">메뉴 순서 (필수)</label>
                            <div class="col-sm-5">
                                <input type="text" class="form-control" name="menu_order" value="1000">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">상위 메뉴 (필수)</label>
                            <div class="col-sm-5">
                                <select class="form-control" name="menu_path">
                                    <option value="">- 최상위 -</option>
                                    @foreach($menus as $menu)
                                    <option value="{{ $menu['menu_path'] }}" @if($menu['menu_path'] === 'dynamic_factory.') selected="selected"@endif>{{ xe_trans($menu['title']) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">설명</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="description">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">댓글 사용</label>
                            <div class="col-sm-5">
                                <select class="form-control" name="use_comment">
                                    <option value="N">사용안함</option>
                                    <option value="Y">사용함</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">관리자페이지에 댓글 출력</label>
                            <div class="col-sm-5">
                                <select class="form-control" name="show_admin_comment">
                                    <option value="N">사용안함</option>
                                    <option value="Y">사용함</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel accordion" id="accordion_ex">
                    <div class="panel-heading clearfix" id="headingOne" data-toggle="collapse" data-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                        <div class="pull-left"><h4>레이블</h4></div>
                        <div class="pull-right"><h4><i id="angle_icon" class="xi-angle-down"></i></h4></div>
                    </div>
                    <div id="collapseOne" class="collapse" style="overflow: hidden;">
                        <div class="panel-body">
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">제목명</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="labels[title]" value="{{ $labels['title'] }}">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">새로 추가</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="labels[new_add]" value="{{ $labels['new_add'] }}">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">새 항목 추가</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="labels[new_add_cpt]" value="{{ $labels['new_add_cpt'] }}">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">항목 편집</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="labels[cpt_edit]" value="{{ $labels['cpt_edit'] }}">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">새 항목</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="labels[new_cpt]" value="{{ $labels['new_cpt'] }}">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">항목 보기</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="labels[cpt_view]" value="{{ $labels['cpt_view'] }}">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">항목 검색</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="labels[cpt_search]" value="{{ $labels['cpt_search'] }}">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">찾을 수 없음</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="labels[no_search]" value="{{ $labels['no_search'] }}">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">휴지통에서 찾을 수 없음</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="labels[no_trash]" value="{{ $labels['no_trash'] }}">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">상위 항목 설명</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="labels[parent_txt]" value="{{ $labels['parent_txt'] }}">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label">모든 항목</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="labels[all_cpt]" value="{{ $labels['all_cpt'] }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="xi-download"></i>저장</button>
        </form>
    </div>
</div>
<script type="text/javascript">
$(document).ready(function() {
    $('#collapseOne').on('show.bs.collapse', function () {
        $('#angle_icon').removeClass('xi-angle-down').addClass('xi-angle-up');
    }).on('hide.bs.collapse', function () {
        $('#angle_icon').removeClass('xi-angle-up').addClass('xi-angle-down');
    });
});
</script>
