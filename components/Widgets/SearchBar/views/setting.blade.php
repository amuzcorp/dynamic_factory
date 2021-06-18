<p>검색 옵션</p>
<hr>

<div class="form-group">
    <label>인스턴스 선택</label>
    <select name="instance_id" class="form-control" onchange="">
        <option value="">출력할 인스턴스를 선택해주세요</option>
        @foreach($menu_items as $key => $val)
            <option value="{{ $key }}" @if(array_get($args, 'instance_id') === $key) selected @endif>{{ xe_trans($val) }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label>검색어 Parameter Key</label>
    <input type="text" class="form-control" name="parameter_key" value="{{array_get($args, 'parameter_key')}}" >
</div>

<div class="form-group">
    <label>검색어 placeholder</label>
    <input type="text" class="form-control" name="text_placeholder" value="{{array_get($args, 'text_placeholder')}}" >
</div>

<div class="form-group">
    <label>검색 버튼 텍스트</label>
    <input type="text" class="form-control" name="button_text" value="{{array_get($args, 'button_text')}}" >
</div>

<p>카테고리 옵션</p>
<hr>

<div class="form-group">
    <label>카테고리1 Name</label>
    <input type="text" class="form-control" name="cate_1_name" placeholder="" value="{{array_get($args, 'cate_1_name')}}">
</div>

<div class="form-group">
    <label>카테고리1 placeholder</label>
    <input type="text" class="form-control" name="cate_1_placeholder" placeholder="" value="{{array_get($args, 'cate_1_placeholder')}}">
</div>

<div class="form-group">
    <label>카테고리1 선택</label>
    <select name="category_1" class="form-control">
        <option value="">출력할 카테고리를 선택해주세요</option>
        @foreach($categoryExtras as $extra)
            <option value="{{ $extra->slug }}" @if( array_get($args, 'category_1') === (string) $extra->slug) selected @endif>{{ xe_trans($extra->category->name) }}</option>
        @endforeach
    </select>
</div>


<div class="form-group">
    <label>카테고리2 Name</label>
    <input type="text" class="form-control" name="cate_2_name" placeholder="" value="{{array_get($args, 'cate_2_name')}}">
</div>

<div class="form-group">
    <label>카테고리2 placeholder</label>
    <input type="text" class="form-control" name="cate_2_placeholder" placeholder="" value="{{array_get($args, 'cate_2_placeholder')}}">
</div>

<div class="form-group">
    <label>카테고리2 선택</label>
    <select name="category_2" class="form-control">
        <option value="">출력할 카테고리를 선택해주세요</option>
        @foreach($categoryExtras as $extra)
            <option value="{{ $extra->slug }}" @if( array_get($args, 'category_2') === (string) $extra->slug) selected @endif>{{ xe_trans($extra->category->name) }}</option>
        @endforeach
    </select>
</div>
