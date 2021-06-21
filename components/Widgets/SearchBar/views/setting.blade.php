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
    <label>카테고리 갯수</label>
    <select class="form-control" name="category_count" onchange="setCategoryCount()">
        <option value="">카테고리 갯수를 선택해주세요</option>
        <option value="1" @if(array_get($args, 'category_count') === '1') selected @endif>카테고리 1개</option>
        <option value="2" @if(array_get($args, 'category_count') === '2') selected @endif>카테고리 2개</option>
        <option value="3" @if(array_get($args, 'category_count') === '3') selected @endif>카테고리 3개</option>
    </select>
</div>
<br>

<div id="categoryOptions">
    @if(array_get($args, 'category_count'))
        @for($i = 0; $i < +array_get($args, 'category_count'); $i++)
            <div id="cate_{{$i+1}}">
                <div class="form-group">
                    <label>카테고리{{$i+1}} Name</label>
                    <input type="text" class="form-control" name="cate_{{$i+1}}_name" placeholder="" value="{{array_get($args, 'cate_'.($i+1).'_name')}}">
                </div>

                <div class="form-group">
                    <label>카테고리{{$i+1}} placeholder</label>
                    <input type="text" class="form-control" name="cate_{{$i+1}}_placeholder" placeholder="" value="{{array_get($args, 'cate_'.($i+1).'_placeholder')}}">
                </div>

                <div class="form-group">
                    <label>카테고리{{$i+1}} 선택</label>
                    <select name="category_{{$i+1}}" class="form-control">
                        <option value="">출력할 카테고리를 선택해주세요</option>
                        @foreach($categoryExtras as $extra)
                            <option value="{{ $extra->slug }}" @if( array_get($args, 'category_'.($i+1)) === (string) $extra->slug) selected @endif>{{ xe_trans($extra->category->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <hr>
            </div>
        @endfor
    @endif

</div>

<script>
    function setCategoryCount() {
        var categories = @json($categoryExtras);
        var category_count = +$('select[name=category_count]').val();

        var str = '';
        for(let i = 0; i < category_count; i++) {
            if(document.getElementById('cate_' + (i+1))) {
                str+= '<div id="cate_'+(i+1)+'">';
                str+= document.getElementById('cate_' + (i+1)).innerHTML;
                str+= '</div>';
            }
            else {
                str += '' +
                    '<div id="cate_' + (i + 1) + '">' +
                    '   <div class="form-group">' +
                    '       <label>카테고리' + (i + 1) + ' Name</label>' +
                    '       <input type="text" class="form-control" name="cate_' + (i + 1) + '_name" placeholder="" value="">' +
                    '   </div>' +
                    '   <div class="form-group">' +
                    '       <label>카테고리' + (i + 1) + ' placeholder</label>' +
                    '       <input type="text" class="form-control" name="cate_' + (i + 1) + '_placeholder" placeholder="" value="">' +
                    '   </div>' +
                    '   <div class="form-group">' +
                    '       <label>카테고리' + (i + 1) + ' 선택</label>' +
                    '       <select name="category_' + (i + 1) + '" class="form-control">' +
                    '           <option value="">출력할 카테고리를 선택해주세요</option>';
                    for (var key in categories) {
                        str += '<option value="' + categories[key].slug + '">' + categories[key].category_name + '</option>';
                    }
                    str += '' +
                    '       </select>' +
                    '   </div>' +
                    '   <hr>' +
                    '</div>';
            }
        }

        document.getElementById('categoryOptions').innerHTML = '';
        document.getElementById('categoryOptions').innerHTML = str;
    }
</script>
