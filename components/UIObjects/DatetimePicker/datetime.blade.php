<a href="#" class="toggle_btn">변경</a>
<input class="form-control" id="datetimepicker_{{ $seq }}" type="text" name="published_at" style="display:none;">
<input class="form-control" id="dt_default_{{ $seq }}" type="text" value="{{ $published_at != null ? $published_at : '현재 시각'}}" readonly="readonly" />
<script>
    $(document).ready(function() {
        $.datetimepicker.setLocale('ko');
        $('#datetimepicker_{{ $seq }}').datetimepicker({
            defaultDate:new Date(),
            step:5,
            format:'Y-m-d H:i:s',
            mask:true
        });


        $('.toggle_btn').on('click', function() {
            let txt = $(this).text();
            if(txt === '변경'){
                console.log(getDefaultDate());
                $('#datetimepicker_{{ $seq }}').val(getDefaultDate());
                $('#datetimepicker_{{ $seq }}').show();
                $('#dt_default_{{ $seq }}').hide();

                $(this).text('변경취소');
            }else {
                $('#datetimepicker_{{ $seq }}').hide();
                $('#dt_default_{{ $seq }}').show();
                $('#datetimepicker_{{ $seq }}').val('');
                $(this).text('변경');
            }
        });

        function getDefaultDate(){
            @if($published_at != null)
                return '{{ $published_at }}';
            @else
                let m = new Date();
                let dateString =
                    m.getFullYear() + "-" +
                    ("0" + (m.getMonth()+1)).slice(-2) + "-" +
                    ("0" + m.getDate()).slice(-2) + " " +
                    ("0" + m.getHours()).slice(-2) + ":" +
                    ("0" + m.getMinutes()).slice(-2) + ":" +
                    ("0" + m.getSeconds()).slice(-2);
                return dateString;
            @endif
        }
    });
</script>
