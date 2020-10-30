@section('page_title')
    <h2>새 유형 추가하기</h2>
@endsection
<div class="row">
    <div class="col-sm-12">
        <form method="post" action="{{ route('dyFac.setting.store_cpt') }}">
            {!! csrf_field() !!}
            <div class="panel">
                <div class="panel-heading">
                    <h4>이름 및 설명</h4>
                </div>
                <div class="panel-body">
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
                        <label class="col-sm-2 col-form-label">슬러그 (필수)</label>
                        <div class="col-sm-5">
                            <input type="text" class="form-control" name="slug">
                        </div>
                        <div class="col-sm-5">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="has_archive" name="has_archive">
                                <label class="form-check-label" for="has_archive">아카이브 슬러그 사용</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label">설명</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="description">
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
                        <input class="form-check-input" type="checkbox" value="title" id="sec_title" name="sections[]" checked="checked">
                        <label class="form-check-label" for="sec_title">제목</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="content" id="sec_content" name="sections[]" checked="checked">
                        <label class="form-check-label" for="sec_content">내용</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="comment" id="sec_comment" name="sections[]" checked="checked">
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
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">여기에 제목 입력</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="labels[here_title]" value="{{ $labels['here_title'] }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="xi-download"></i>저장</button>
        </form>
    </div>
</div>
