<div class="xe-form-group xe-dynamicField">
    <label class="__xe_df __xe_df_text __xe_df_text_{{ xe_trans($config->get('label')) }}">{{ xe_trans($config->get('label')) }}</label>
    <span class="__xe_df __xe_df_text __xe_df_text_{{ xe_trans($config->get('label')) }}">
        @foreach($items as $item)
            <a href="{{ '/@'.$item->id }}" class="xe-btn" target="_blank">{{ $item->display_name }}({{ $item->login_id }})</a>
        @endforeach
    </span>
</div>
