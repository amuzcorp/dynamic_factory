@section('page_title')
    @if(!$category->id)<h2>카테고리 생성</h2>@endif
    @if($category->id)<h2>{{ xe_trans($category->name)}} - 카테고리 수정</h2>@endif
@endsection

{{ XeFrontend::css('/assets/core/settings/css/admin_menu.css')->before('/assets/core/settings/css/admin.css')->load() }}

@if(isset($category->id))
<ul class="nav nav-tabs">
    <li class="active"><a href="{{ route('dyFac.setting.create_taxonomy', ['tax_id' => $category->id]) }}">기본정보</a></li>
    <li><a href="{{ route('dyFac.setting.taxonomy_extra', ['category_slug' => $cpt_cate_extra->slug]) }}">확장필드</a></li>
</ul>
@endif

<div class="row">
    <div class="col-sm-12">
        <div class="panel-group">
            <form method="post" action="{{ route('dyFac.setting.store_cpt_tax') }}">
                {!! csrf_field() !!}
                <input type="hidden" name="category_id" value="{{ $category->id }}">
                <div class="panel">
                    <div class="panel-heading">
                        <div class="pull-left"><h4>기본 정보</h4></div>
                    </div>
                    <div class="panel-body">
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">ID (필수)</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="slug" value="{{ $cpt_cate_extra->slug }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">이름 (필수)</label>
                            <div class="col-sm-10">
                                {!! uio('langText', ['name'=>'category_name', 'value'=> $category->name ]) !!}
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">템플릿</label>
                            <div class="col-sm-10">
                                <select class="form-control" name="template">
                                    <option value="select" @if($cpt_cate_extra->template === 'select') selected="selected" @endif>Single Select</option>
                                    <option value="multi_select" @if($cpt_cate_extra->template === 'multi_select') selected="selected" @endif>Multi Select</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="panel-heading" style="border-top: 1px solid #E5E5E5">
                        <div class="pull-left"><h4>이 분류와 함께 사용할 유형</h4></div>
                    </div>
                    <div class="panel-body">
                        @foreach($cpts as $cpt)
                        <input class="form-check-input" type="checkbox" id="chk{{ $cpt->cpt_id }}" name="cpts[]" value="{{ $cpt->cpt_id }}" @if(in_array($cpt->cpt_id, $cpt_ids)) checked="checked"@endif>
                        <label class="form-check-label" for="chk{{ $cpt->cpt_id }}">{{ $cpt->cpt_name }}</label>
                        @endforeach
                        @foreach($cpts_fp as $fp)
                        <input class="form-check-input" type="checkbox" id="chk{{ $fp->cpt_id }}" name="cpts[]" value="{{ $fp->cpt_id }}" @if(in_array($fp->cpt_id, $cpt_ids)) checked="checked"@endif>
                        <label class="form-check-label" for="chk{{ $fp->cpt_id }}">{{ $fp->cpt_name }}</label>
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

<div id="dynamic_field_hidden" style="display:none;">
@foreach($category_items as $item)
    @foreach((array)$item->dfs as $df)
        <div class="cate_df_{{ $item->id }}">{!! $df !!}</div>
    @endforeach
@endforeach
</div>

@if($category->id)
<script type="text/javascript">
    $(function () {
        Category.init({
            load: '{{ route('df.category.edit.item.children', ['id' => $category->id]) }}',
            add: '{{ route('df.category.edit.item.store', ['id' => $category->id, 'slug' => $cpt_cate_extra->slug]) }}',
            modify: '{{ route('df.category.edit.item.update', ['id' => $category->id, 'slug' => $cpt_cate_extra->slug]) }}',
            remove: '{{ route('df.category.edit.item.destroy', ['id' => $category->id, 'force' => false]) }}',
            removeAll: '{{ route('df.category.edit.item.destroy', ['id' => $category->id, 'force' => true]) }}',
            move: '{{ route('df.category.edit.item.move', ['id' => $category->id]) }}'
        });
    });
</script>
@endif
