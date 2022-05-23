<div class="form-group">
    <label class="xe-form__label--requried">문서 종류 선택</label> <small>생성 후 변경 할 수 없습니다.</small>

    <select class="form-control" name="r_instance_id" id="r_instance_id">
        @if($config !== null)
            <option value="{{ $config->get('r_instance_id') }}">
                @if($config->get('r_instance_id') !== 'user')
                    {{ array_get($iids, sprintf('%s.%s', $config->get('r_instance_id'), 'name')) }}
                @else
                    <span>(user)사용자</span>
                @endif
            </option>
        @else
            <option value="">선택하세요</option>
            @foreach($iids as $iid)
                <option value="{{ $iid['id'] }}">({{ $iid['type'] }}) {{ $iid['name'] }}</option>
            @endforeach
            <option value="user">(user)사용자</option>
        @endif
    </select>
</div>

<div class="form-group" id="author_form_group" @if($config !== null && $config->get('r_instance_id') === 'user') style="display: none;" @endif >
    <label class="xe-form__label--requried">문서 조회 조건</label>
    <select name="author" class="form-control">
        <option value="any" @if($config !== null && $config->get('author') === 'any') selected="selected"@endif>모든 글</option>
        <option value="author" @if($config !== null && $config->get('author') === 'author') selected="selected"@endif>자신이 작성한 글만</option>
    </select>
</div>
<div class="form-group" id="user_form_group" @if($config !== null && $config->get('r_instance_id') && $config->get('r_instance_id') !== 'user') style="display: none;" @endif>
    <label class="xe-form__label--requried">사용자 그룹</label>
    @foreach($groups as $group)
        <input class="form-check-input"
               type="checkbox" id="{{$group->id}}"
               name="user_groups[]"
               value="{{$group->id}}"
               @if($config && in_array($group->id, $config->get('user_groups')))
                   checked
               @endif >
        <label class="form-check-label" for="{{$group->id}}" style="font-weight: normal"> {{xe_trans($group->name)}}</label>
    @endforeach
</div>
<script>
    $(document).ready(function() {
        $('#r_instance_id').on('change', function() {
            switch($(this).val()) {
                case '':
                    $('#author_form_group').hide();
                    $('#user_form_group').hide();
                    break;
                case 'user':
                    $('#author_form_group').hide();
                    $('#user_form_group').show();
                    break;
                default:
                    $('#author_form_group').show();
                    $('#user_form_group').hide();
                    break;
            }
        });
    });
</script>
