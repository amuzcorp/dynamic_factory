<a href="#" id="user_chg_btn_{{ $seq }}"><i class="xi-refresh xi-border"></i></a>
<input id="us_user_id_{{ $seq }}" type="hidden" name="user_id" />
<input id="us_writer_{{ $seq }}" type="hidden" name="writer" />
<div id="us_autocomplete_{{ $seq }}"></div>
<script>
    const options_{{ $seq }} = [
        @foreach($options as $option)
        {
            label: '{{array_get($option, 'label')}}',
            value: '{{array_get($option, 'value')}}'
        },
        @endforeach
    ];

    $(document).ready(function() {
        new MultiSelect2('#us_autocomplete_{{ $seq }}', {
            options: options_{{ $seq }},
            multiple: false,
            autocomplete: true,
            onChange: value => {
                if(value){
                    let arr = value.split('|@|');
                    $('#us_user_id_{{ $seq }}').val(arr[0]);
                    if(arr.length > 1) $('#us_writer_{{ $seq }}').val(arr[1]);
                }
            }
        });

        $('#user_chg_btn_{{ $seq }}').on('click', function() {
            $('#us_user_id_{{ $seq }}').val('');
            $('#us_writer_{{ $seq }}').val('');

            $('#us_autocomplete_{{ $seq }} .multi-select__label').text('{{ sprintf('%s(%s)',$display_name, $login_id) }}');
            {{--let txt = $(this).text();
            if(txt == '변경'){
                $('#us_autocomplete_{{ $seq }} .multi-select__label').text('{{ sprintf('%s(%s)',$display_name, $login_id) }}');
                $('#us_autocomplete_{{ $seq }}').show();
                $(this).text('취소');
            }else {
                $('#us_autocomplete_{{ $seq }}').hide();
                $('#us_user_id_{{ $seq }}').val('');
                $('#us_writer_{{ $seq }}').val('');

                $('#us_autocomplete_{{ $seq }} .multi-select__label').empty();
                $(this).text('변경');
            }--}}
        });

        $('#us_autocomplete_{{ $seq }} .multi-select__label').text('{{ sprintf('%s(%s)',$display_name, $login_id) }}');
    });
</script>
