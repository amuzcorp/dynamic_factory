@section('page_title')
    @if(!$category->id)<h2>새로운 카테고리 추가</h2>@endif
    @if($category->id)<h2>[{{ xe_trans($category->name)}}] 카테고리 수정</h2>@endif
@endsection

{{ XeFrontend::css('/assets/core/settings/css/admin_menu.css')->before('/assets/core/settings/css/admin.css')->load() }}

<div class="row">
    <div class="col-sm-12">
        <div class="panel-group">
            <form method="post" action="{{ route('dyFac.setting.store_cpt_tax') }}">
                {!! csrf_field() !!}
                <input type="hidden" name="category_id" value="{{ $category->id }}">
                <div class="panel">
                    <div class="panel-footer">
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
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">템플릿</label>
                            <div class="col-sm-10">
                                <select class="form-control" name="template">
                                    <option value="select" @if($cpt_cate_extra->template === 'select') selected="selected" @endif>Select</option>
                                    <option value="multi_select" @if($cpt_cate_extra->template === 'multi_select') selected="selected" @endif>Multi Select</option>
{{--                                    <option value="hierarchy" @if($cpt_cate_extra->template === 'select') selected="selected" @endif>Hierarchy</option>--}}
                                    <option value="check_list" @if($cpt_cate_extra->template === 'check_list') selected="selected" @endif>Check List</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <h4>이 분류와 함께 사용할 유형</h4>
                    </div>
                    <div class="panel-body">
                        @foreach($cpts as $cpt)
                        <input class="form-check-input" type="checkbox" id="chk{{ $cpt->cpt_id }}" name="cpts[]" value="{{ $cpt->cpt_id }}" @if(in_array($cpt->cpt_id, $cpt_ids)) checked="checked"@endif>
                        <label class="form-check-label" for="chk{{ $cpt->cpt_id }}">{{ $cpt->cpt_name }}</label>
                        @endforeach
                        <div class="clearfix">
                            <button type="submit" class="btn btn-primary pull-right">@if(!$category->id)생성@else수정@endif</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="__xe_category-tree-container" class="panel board-category">
</div>

@if($category->id)
<script type="text/javascript">
    $(function () {
        Category.init({
            load: '{{ route('manage.category.edit.item.children', ['id' => $category->id]) }}',
            add: '{{ route('manage.category.edit.item.store', ['id' => $category->id]) }}',
            modify: '{{ route('manage.category.edit.item.update', ['id' => $category->id]) }}',
            remove: '{{ route('manage.category.edit.item.destroy', ['id' => $category->id, 'force' => false]) }}',
            removeAll: '{{ route('manage.category.edit.item.destroy', ['id' => $category->id, 'force' => true]) }}',
            move: '{{ route('manage.category.edit.item.move', ['id' => $category->id]) }}'
        });
    });
</script>
@endif
