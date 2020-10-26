@section('page_title')
    @if(!$category->id)<h2>새로운 분류 유형 추가하기</h2>@endif
    @if($category->id)<h2>분류 유형 수정하기</h2>@endif
@endsection

<div class="row">
    <div class="col-sm-12">
        <div class="panel-group">
            <form method="post" action="{{ route('dyFac.setting.store_cpt_tax') }}">
                {!! csrf_field() !!}
                <input type="hidden" name="category_id" value="{{ $category->id }}">
                <div class="panel">
                    <div class="panel-heading">
                        <h4>이름 및 슬러그</h4>
                    </div>
                    <div class="panel-body">
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">분류 이름 (필수)</label>
                            <div class="col-sm-10">
                                {!! uio('langText', ['name'=>'name', 'value'=> $category->name ]) !!}
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">슬러그 (필수)</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="slug" value="{{ $cpt_cate_extra->slug }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-heading">
                        <h4>분류 유형{{ $cpt_cate_extra->is_hierarchy }}</h4>
                    </div>
                    <div class="panel-body">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="is_hierarchy" id="exampleRadios1" value="1" @if($cpt_cate_extra->is_hierarchy) checked="checked"@endif>
                            <label class="form-check-label" for="exampleRadios1">계층형</label> - Category
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="is_hierarchy" id="exampleRadios2" value="0" @if(!$cpt_cate_extra->is_hierarchy) checked="checked"@endif>
                            <label class="form-check-label" for="exampleRadios2">단일형</label> - Tag
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-heading">
                        <h4>이 분류와 함께 사용할 유형</h4>
                    </div>
                    <div class="panel-body">
                        @foreach($cpts as $cpt)
                        <input class="form-check-input" type="checkbox" id="chk{{ $cpt->cpt_id }}" name="cpts[]" value="{{ $cpt->cpt_id }}" @if(in_array($cpt->cpt_id, $cpt_ids)) checked="checked"@endif>
                        <label class="form-check-label" for="chk{{ $cpt->cpt_id }}">{{ $cpt->cpt_name }}</label>
                        @endforeach
                    </div>
                </div>
                <br>
                <button type="button" class="btn" onclick="history.back();">뒤로 가기</button>
                <button type="submit" class="btn btn-primary">@if(!$category->id)생성@else수정@endif</button>
            </form>
        </div>
    </div>
</div>
