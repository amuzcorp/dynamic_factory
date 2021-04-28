<div class="form-group">
    <label class="xe-form__label--requried">문서 종류 선택</label> <small>생성 후 변경 할 수 없습니다.</small>
    {{--
    <div style="border:1px solid #C8C7CC; border-radius: 2px; padding:10px;">
        @foreach($cpts as $cpt)
            <input class="form-check-input" type="checkbox" id="df_{{ $cpt->cpt_id }}" name="cpt_ids[]" value="{{ $cpt->cpt_id }}" @if($config !== null && $config->get('cpt_ids') !== null && in_array($cpt->cpt_id, $config->get('cpt_ids'))) checked="checked" @endif>
            <label class="form-check-label" for="df_{{ $cpt->cpt_id }}" style="font-weight: normal"> {{ $cpt->cpt_name }}</label>
        @endforeach
        <input class="form-check-input" type="checkbox" id="df_board" name="cpt_ids[]" value="module/board@board" @if($config !== null && $config->get('cpt_ids') !== null && in_array('module/board@board', $config->get('cpt_ids'))) checked="checked" @endif />
        <label class="form-check-label" for="df_board" style="font-weight: normal"> 게시판</label>
        <input class="form-check-input" type="checkbox" id="df_comment" name="cpt_ids[]" value="comment" @if($config !== null && $config->get('cpt_ids') !== null && in_array('comment', $config->get('cpt_ids'))) checked="checked" @endif />
        <label class="form-check-label" for="df_comment" style="font-weight: normal"> 댓글</label>
    </div>--}}
    <select class="form-control" name="r_instance_id">
        @if($config !== null)
            <option value="{{ $config->get('r_instance_id') }}">{{ array_get($iids, sprintf('%s.%s', $config->get('r_instance_id'), 'name')) }}</option>
        @else
            @foreach($iids as $iid)
                <option value="{{ $iid['id'] }}">({{ $iid['type'] }}) {{ $iid['name'] }}</option>
            @endforeach
        @endif
    </select>
</div>
<div class="form-group">
    <label class="xe-form__label--requried">문서 조회 조건</label>
    <select name="author" class="form-control __xe_skin_id">
        <option value="any" @if($config !== null && $config->get('author') === 'any') selected="selected"@endif>모든 글</option>
        <option value="author" @if($config !== null && $config->get('author') === 'author') selected="selected"@endif>자신이 작성한 글만</option>
    </select>
</div>
