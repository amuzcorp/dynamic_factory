@section('page_title')
    <h2>새 유형 추가하기</h2>
@endsection

<div class="row">
    <div class="col-sm-12">
        <form method="post" action="{{ route('d_fac.setting.store_cpt') }}">
            {!! csrf_field() !!}
            <div class="panel">
                <div class="panel-heading">
                    <h4>이름 및 설명</h4>
                </div>
                <div class="panel-body">
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label">메뉴 이름 (필수)</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="menu_name">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label">메뉴 순서 (필수)</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="menu_order" value="{{ $menu_order }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label">유형 이름 (필수)</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="obj_name">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label">슬러그 (필수)</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="slug">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label">설명</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="description">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label">에디터 선택 (필수)</label>
                        <div class="col-sm-10">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="inlineRadioOptions" id="inlineRadio1" value="option1">
                                <label class="form-check-label" for="inlineRadio1">기본</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="inlineRadioOptions" id="inlineRadio2" value="option2" disabled>
                                <label class="form-check-label" for="inlineRadio2">에디터1</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="inlineRadioOptions" id="inlineRadio3" value="option3" disabled>
                                <label class="form-check-label" for="inlineRadio3">에디터2</label>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <div class="panel">
                <div class="panel-heading">
                    <h4>편집 시 표시할 섹션</h4>
                </div>
                <div class="panel-body">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="Y" id="use_title" name="use_title">
                        <label class="form-check-label" for="use_title">제목</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="Y" id="use_editor" name="use_editor">
                        <label class="form-check-label" for="use_editor">에디터</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="Y" id="use_comment" name="use_comment">
                        <label class="form-check-label" for="use_comment">댓글</label>
                    </div>
                </div>
            </div>

            <div class="panel accordion" id="accordion_ex">
                <div class="panel-heading clearfix" id="headingOne" data-toggle="collapse" data-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                    <h4>레이블 <i class="xi-angle-down pull-right"></i></h4>
                </div>
                <div id="collapseOne" class="collapse" style="overflow: hidden;">
                    <div class="panel-body">
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">새로 추가</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="new_add">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">새 항목 추가</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="new_add_obj">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">항목 편집</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="obj_edit">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">새 항목</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="new_obj">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">항목 검색</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="obj_search">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">찾을 수 없음</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="no_search">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">휴지통에서 찾을 수 없음</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="no_trash">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">상위 항목 설명</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="parent_txt">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">모든 항목</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="all_obj">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">여기에 제목 입력</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="here_title_input">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-heading">
                    <h4>옵션</h4>
                </div>
                <div class="panel-body">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="Y" id="has_archive" name="has_archive">
                        <label class="form-check-label" for="has_archive">아카이브 슬러그 사용</label>
                    </div>
                    <input type="text" class="form-control" name="archive_slug">
                </div>
            </div>
            <div class="form-group row">
                <div class="col-sm-10">
                    <button type="submit" class="btn btn-primary">생성하기</button>
                </div>
            </div>
        </form>
    </div>
</div>
