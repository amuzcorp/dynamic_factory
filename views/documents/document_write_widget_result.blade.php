@if($status === 'wait')
@elseif($status === 'success')
    @if($after_work === 'reset')
        <script>
            parent.location.reload();
            // var frm = parent.document.getElementById("createForm");
            // var em = frm.elements;
            // frm.reset();
            // for (var i = 0; i < em.length; i++) {
            //     if (em[i].type == 'text') em[i].value = '';
            //     if (em[i].type == 'checkbox') em[i].checked = false;
            //     if (em[i].type == 'radio') em[i].checked = false;
            //     if (em[i].type == 'select-one') {
            //         em[i].selectedIndex = 0;
            //         $('select[name='+em[i].name+'] option').prop('selected', function() {
            //             return this.defaultSelected;
            //         });
            //     }
            //     if (em[i].type == 'textarea') em[i].value = '';
            // }

            alert('접수가 완료되었습니다.');
        </script>
    @else
        <script>
            alert('접수가 완료되었습니다.');
            window.location.href = '{{Route('dyFac.setting.'.$result, ['type' => 'list'])}}';
        </script>
    @endif
@endif
