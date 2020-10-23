@section('page_title')
    <h2>[{{ $cpt['cpt_name'] }}] 유형 수정하기</h2>
@endsection
<ul class="nav nav-tabs">
    <li class="active" ><a href="#">기본정보</a></li>
    <li><a href="{{ route('dyFac.setting.create_extra', ['cpt_id' => $cpt['cpt_id']]) }}">확장필드</a></li>
</ul>
<div class="row">
    <div class="col-sm-12">
        <form method="post" action="{{ route('dyFac.setting.update') }}">
            {!! csrf_field() !!}
            <input type="hidden" name="cpt_id" value="{{ $cpt['cpt_id'] }}" />
            <div class="panel">
                <div class="panel-heading">
                    <h4>이름 및 설명</h4>
                </div>
                <div class="panel-body">
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label">유형 이름 (필수)</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="cpt_name" value="{{ $cpt['cpt_name'] }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label">메뉴 이름 (필수)</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="menu_name" value="{{ $cpt['menu_name'] }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label">메뉴 순서 (필수)</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="menu_order" value="{{ $cpt['menu_order'] }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label">슬러그 (필수)</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="slug" value="{{ $cpt['slug'] }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label">설명</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="description" value="{{ $cpt['description'] }}">
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
                        <input class="form-check-input" type="checkbox" value="title" id="sec_title" name="sections[]" @if(in_array('title', $cpt['sections'])) checked="checked" @endif>
                        <label class="form-check-label" for="sec_title">제목</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="content" id="sec_content" name="sections[]" @if(in_array('content', $cpt['sections'])) checked="checked" @endif>
                        <label class="form-check-label" for="sec_content">내용</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="comment" id="sec_comment" name="sections[]" @if(in_array('comment', $cpt['sections'])) checked="checked" @endif>
                        <label class="form-check-label" for="sec_comment">댓글</label>
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
                                <input type="text" class="form-control" name="labels[new_add]" value="{{ $cpt['labels']['new_add'] }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">새 항목 추가</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="labels[new_add_cpt]" value="{{ $cpt['labels']['new_add_cpt'] }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">항목 편집</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="labels[cpt_edit]" value="{{ $cpt['labels']['cpt_edit'] }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">새 항목</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="labels[new_cpt]" value="{{ $cpt['labels']['new_cpt'] }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">항목 보기</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="labels[cpt_view]" value="{{ $cpt['labels']['cpt_view'] }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">항목 검색</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="labels[cpt_search]" value="{{ $cpt['labels']['cpt_search'] }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">찾을 수 없음</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="labels[no_search]" value="{{ $cpt['labels']['no_search'] }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">휴지통에서 찾을 수 없음</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="labels[no_trash]" value="{{ $cpt['labels']['no_trash'] }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">상위 항목 설명</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="labels[parent_txt]" value="{{ $cpt['labels']['parent_txt'] }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">모든 항목</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="labels[all_cpt]" value="{{ $cpt['labels']['all_cpt'] }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">여기에 제목 입력</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="labels[here_title]" value="{{ $cpt['labels']['here_title'] }}">
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
                        <input class="form-check-input" type="checkbox" value="Y" id="opt_has_archive" name="opt_has_archive">
                        <label class="form-check-label" for="opt_has_archive">아카이브 슬러그 사용</label>
                    </div>
                    <input type="text" class="form-control" name="opt_archive_slug">
                </div>
            </div>
            <div class="form-group row">
                <div class="col-sm-10">
                    <button type="button" class="btn" onclick="javascript:history.back();">뒤로가기</button><button type="submit" class="btn btn-primary">수정하기</button>
                </div>
            </div>
        </form>
    </div>
</div>
