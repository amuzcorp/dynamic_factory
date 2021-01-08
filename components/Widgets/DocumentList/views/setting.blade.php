<div class="form-group">
    <label>정렬</label>
    <select name="order_type" class="form-control">
        <option value="recentlyCreated" @if(array_get($args, 'order_type') == 'recentlyCreated') selected="selected" @endif >최신순</option>
        <option value="recentlyUpdated" @if(array_get($args, 'order_type') == 'recentlyUpdated') selected="selected" @endif >최근 수정순</option>
        <option value="assent_count" @if(array_get($args, 'order_type') == 'assent_count') selected="selected" @endif >추천순</option>
    </select>
</div>

<div class="form-group">
    <label>최근 몇일</label>
    <input type="number" name="recent_date" class="form-control" value="{{array_get($args, 'recent_date')}}" />
</div>

<div class="form=-group">
    <label>더보기 버튼 여부</label>
    <div class="xe-btn-toggle">
        <label>
            <span class="sr-only">toggle</span>
            <input name="more" type="checkbox" @if(array_get($args, 'more')) checked="checked" @endif />
            <span class="toggle"></span>
        </label>
    </div>
</div>

<br>
<p>글 설정</p>
<hr>

<div class="form-group">
    <label>사용자 정의 문서 선택</label>
    <select name="cpt_id" class="form-control">
        <option value="">선택</option>
        @foreach($cptList as $item)
        <option value="{{ $item->cpt_id }}">{{ $item->cpt_name }}</option>
        @endforeach
    </select>
</div>
<div class="form-group">
    <label>카테고리 선택</label>
    <select name="categories" class="form-control" multiple></select>
</div>

<div class="form-group">
    <label>글 수</label>
    <input type="number" name="take" class="form-control" value="{{array_get($args, 'take', 5)}}" />
</div>

<script>
    $(function(){
        $('select[name=cpt_id]').change(function(e){
            if(!$(this).val())  return false;

            XE.ajax({
                url: '{{ route('dyFac.categories') }}',
                type: 'get',
                dataType: 'json',
                data: {
                    cpt_id: $(this).val()
                },
                success: function (data) {
                    // console.log(data.categories);
                    settingCategories(data.categories);
                },
                error: function (data) {
                    console.log(data);
                }
            });

        }).trigger('change');
    })

    function settingCategories(categories) {
        $('select[name=categories]').empty();
        for(let idx in categories) {
            let option = "<option value='"+ categories[idx].value +"'>"+ categories[idx].text +"</option>";
            $('select[name=categories]').append(option);
        }
    }
</script>
