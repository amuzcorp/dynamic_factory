<div class="form-group">
    <label class="xe-form__label--requried">문서 종류 선택</label>
    <div style="border:1px solid #C8C7CC; border-radius: 2px; padding:10px;">
    @foreach($cpts as $cpt)
    <input class="form-check-input" type="checkbox" id="df_{{ $cpt->cpt_id }}" name="cpt_ids[]" value="{{ $cpt->cpt_id }}" @if($config !== null && $config->get('cpt_ids') !== null && in_array($cpt->cpt_id, $config->get('cpt_ids'))) checked="checked" @endif>
    <label class="form-check-label" for="df_{{ $cpt->cpt_id }}" style="font-weight: normal"> {{ $cpt->cpt_name }}</label>
    @endforeach
    </div>
</div>
<div class="form-group">
    <label class="xe-form__label--requried">문서 조회 조건</label>
    <select name="author" class="form-control __xe_skin_id">
        <option value="any" @if($config !== null && $config->get('author') === 'any') selected="selected"@endif>모든 글</option>
        <option value="author" @if($config !== null && $config->get('author') === 'author') selected="selected"@endif>자신이 작성한 글만</option>
    </select>
</div>
