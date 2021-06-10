<div class="form-group">
    <label>카테고리 선택</label>
    <select name="category_slug" class="form-control">
        <option value="">출력할 카테고리를 선택해주세요</option>
        @foreach($categoryExtras as $extra)
            <option value="{{ $extra->slug }}" @if( +array_get($args, 'category_slug') === $extra->slug) selected @endif>{{ xe_trans($extra->category->name) }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label>인스턴스 선택</label>
    <select name="instance_id" class="form-control">
        <option value="">출력할 인스턴스를 선택해주세요</option>
        @foreach($menu_items as $key => $val)
            <option value="{{ $key }}" @if(array_get($args, 'instance_id') === $key) selected @endif>{{ xe_trans($val) }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label>인스턴스 Parameter Key</label>
    <input type="text" class="form-control" name="parameter_key" value="{{array_get($args, 'parameter_key')}}" >
</div>
