<div class="form-group">
    <label>사이트 선택</label>
    <select name="site_key" class="form-control">
        @foreach($siteList as $site)
            <option value="{{ $site->site_key }}" @if(array_get($args, 'site_key') == $site->site_key) selected="selected"  @endif>{{ $site->host }}</option>
        @endforeach
    </select>
</div>

<br>
<p>글 설정</p>
<hr>

<div class="form-group">
    <label>사용자 정의 문서 선택</label>
    <select name="cpt_id" class="form-control">
        <option value="">선택</option>
        @foreach($cptList as $item)
        <option value="{{ $item->cpt_id }}" @if(array_get($args, 'cpt_id') == $item->cpt_id) selected="selected"  @endif>{{ $item->cpt_name }}</option>
        @endforeach
    </select>
</div>
<div class="form-group">
    <label>입력폼 라벨 사용</label>
    <select class="form-control" name="label_active">
        <option value="active" @if(array_get($args, 'label_active') != 'disabled') selected="selected"  @endif>사용</option>
        <option value="disabled" @if(array_get($args, 'label_active') == 'disabled') selected="selected"  @endif>미사용</option>
    </select>
</div>

<hr>


<div class="form-group">
    <label>타이틀 사용</label>
    <select class="form-control" name="title_active" onchange="activeTitle('title_active')">
        <option value="active" @if(array_get($args, 'title_active') == 'active') selected="selected"  @endif>사용</option>
        <option value="disabled" @if(array_get($args, 'title_active') != 'active') selected="selected"  @endif>미사용</option>
    </select>
</div>
<div id="title_option" @if(!array_get($args, 'title_active') || array_get($args, 'title_active') == 'disabled') style="display: none;" @endif>
    <div class="form-group">
        <label>타이틀 라벨 변경</label>
        <input type="text" class="form-control" name="title_label" value="{{array_get($args, 'title_label')}}">
    </div>
</div>
<hr>


<div class="form-group">
    <label>컨텐츠 사용</label>
    <select class="form-control" name="content_active" onchange="activeTitle('content_active')">
        <option value="active" @if(array_get($args, 'content_active') == 'active') selected="selected"  @endif>사용</option>
        <option value="disabled" @if(array_get($args, 'content_active') != 'active') selected="selected"  @endif>미사용</option>
    </select>
</div>
<div id="content_option" @if(!array_get($args, 'content_active') || array_get($args, 'content_active') == 'disabled') style="display: none;" @endif>
    <div class="form-group">
        <label>내용 라벨 변경</label>
        <input type="text" class="form-control" name="content_label" value="{{array_get($args, 'content_label')}}">
    </div>
</div>
<hr>


<div class="form-group">
    <label>작성이후 작업</label>
    <select name="after_work" class="form-control">
        <option value="reset">문서 작서이후 작업을 선택해주세요</option>
        <option value="reset" @if(array_get($args, 'after_work') == 'reset') selected="selected"  @endif>입력폼 초기화</option>
        <option value="link" @if(array_get($args, 'after_work') == 'link') selected="selected"  @endif>문서 페이지로 이동</option>
    </select>
</div>

<script>
    function activeTitle(target) {
        let options = '';
        if(target === 'title_active') {
            options = 'title_option';
        } else {
            options = 'content_option';
        }
        if($('select[name='+target+']').val() === 'active') {
            document.getElementById(options).style.display = 'block';
        } else {
            document.getElementById(options).style.display = 'none';
        }
    }
</script>
