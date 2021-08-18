@php
    $is_user = ($config->get('r_instance_id') == 'user');
@endphp

<div class="form-group">
    <label>{{ xe_trans($config->get('label')) }} <small>{{ $config->get('id') }}</small></label>
    <div id="selected_{{ $config->get('id') }}">
        <input type="hidden" name="{{$config->get('id')}}_srf_chg" value="0" />

        <div class="Tags__tags">
            <div class="ReactTags__selected {{ $config->get('id') }}_tags" id="{{ $config->get('id') }}_tags">
                @foreach($items as $key => $item)
                    <span class="ReactTags__tag" id="tag_{{$item->r_id}}">
                        <span class="tag_index">
                            {{$key + 1}}.
                        </span>
                        {{$item->r_name}}
                        <a class="ReactTags__remove btnRemoveTag" data-id="{{$item->r_id}}" onclick="remove_cpt_item(this)">x</a>
                    </span>
                @endforeach
            </div>

            <div class="{{ $config->get('id') }}_input_hidden">
                @foreach($items as $item)
                    <input type="hidden" name="hidden_{{ $config->get('id') }}[]" value="{{$item->r_id}}">
                @endforeach
            </div>

            <div class="Tags__tagInput">
                <input type="text" name="{{$config->get('id')}}_cpt_search" id="cpt_document_search_input" placeholder="{{ $is_user ? '사용자의 이름' : '문서의 제목' }}으로 검색하세요." class="form-control input-left"/>
                <a class="btn btn-primary btn-right" id="cpt_document_search" style="">검색</a>

                <div class="ReactTags__suggestions"></div>
            </div>
            <div class="input_hidden"></div>
        </div>

    </div>
</div>
<div class="form-group">
    <ul class="list-latest-tfc reset-list" id="{{$config->get('id')}}_cptListItem">
    </ul>

</div>

<script>
    $( function() {
        $("#{{ $config->get('id') }}_tags").sortable({
            update:function(event) {
                $("input[name={{$config->get('id')}}_srf_chg").val( 1 );

                setTagIndex();
            }
        });
        $("#{{ $config->get('id') }}_tags").disableSelection();
    } );

    $('#cpt_document_search').click(function() {
        cpt_search();
    });

    $("#cpt_document_search_input").on("keyup",function(key){
        if(key.keyCode==13) {
            cpt_search();
        }
    });

    function cpt_search() {
        let keyword = $("input[name={{$config->get('id')}}_cpt_search]").val();
        var searchUrl = '';

        var listId = "{{$config->get('id')}}_cptListItem";

        @if($is_user)
            searchUrl = '{{ route('dyFac.user.search') }}';
        @else
            searchUrl = '{{ route('dyFac.document.search') }}';
        @endif

        XE.ajax({
            url: searchUrl + '/' + keyword,
            method: 'get',
            data: {
                'cn': '{{ $config->name }}'
            },
            dataType: 'json',
            cache: false,
            success: function (data) {
                document.getElementById(listId).innerHTML = '';
                var str = '';
                var hidden = 0;
                if (data.length > 0) {
                    var inputs = $(".{{ $config->get('id') }}_input_hidden input");
                    var clip_list = [];
                    for(let i = 0; i < inputs.length; i++) {
                        if(inputs[i].value) clip_list.push(inputs[i].value);
                    }
                    for(let i = 0; i < data.length; i++) {
                        if(clip_list.includes( data[i].id )) {
                            str += `<li class="item-latest" id="${data[i].id}" onclick="selectItem('${data[i].id}', '${data[i].title}')" style="display: none;">${data[i].title}</li>`;
                        } else {
                            str += `<li class="item-latest" id="${data[i].id}" onclick="selectItem('${data[i].id}', '${data[i].title}')">${data[i].title}</li>`;
                        }
                    }

                    if(hidden === data.length) {
                        str += `<li class="item-latest" id="noItem">조회된 문서를 모두 선택했습니다</li>`;
                    } else {
                        str += `<li class="item-latest" id="noItem" style="display: none;">조회된 문서를 모두 선택했습니다</li>`;
                    }


                } else {
                    str = `<li class="item-latest" id="noItem" >조회된 문서가 없습니다</li>`;
                }

                document.getElementById(listId).innerHTML = str;
            }
        });
    }
    function selectItem(id, name) {

        var tagIndex = document.getElementById("{{ $config->get('id') }}_tags").getElementsByClassName('ReactTags__tag');
        var index_no = (tagIndex.length + 1);

        var hidden = `<input type="hidden" name="hidden_{{ $config->get('id') }}[]" value="${id}">`;
        var tags = `<span class="ReactTags__tag" id="tag_${id}">
                        <span class="tag_index">${index_no}. </span>${name}
                        <a class="ReactTags__remove btnRemoveTag" data-id="${id}" onclick="remove_cpt_item(this)">x</a>
                    </span>`;

        $(".{{ $config->get('id') }}_input_hidden").append(hidden);
        $(".{{ $config->get('id') }}_tags").append(tags);

        $("#"+id).hide();
        $("input[name={{$config->get('id')}}_srf_chg").val( 1 );

        var lists = document.querySelectorAll("#{{$config->get('id')}}_cptListItem li");
        var hide = 0;
        for(let i = 0; i < lists.length; i++) {
            if(lists[i].style.display === 'none') {
                hide += 1;
            }
        }
        if(hide === lists.length) {
            $('#noItem').show();
        } else {
            $('#noItem').hide();
        }
    }
    function remove_cpt_item(event) {
        var target_id = $(event).data('id');
        document.getElementById('tag_' + target_id).remove();

        var inputs = $(".{{ $config->get('id') }}_input_hidden input");
        for(let i = 0; i < inputs.length; i++) {
            if(inputs[i].value === target_id) inputs[i].remove();
        }
        $("#"+target_id).show();
        $('#noItem').hide();

        $("input[name={{$config->get('id')}}_srf_chg").val( 1 );

        setTagIndex();
    }
    function setTagIndex() {
        var tag_document = document.getElementById("{{ $config->get('id') }}_tags");
        var child = tag_document.getElementsByClassName('ReactTags__tag');

        document.getElementsByClassName("{{ $config->get('id') }}_input_hidden")[0].innerHTML = '';
        for(let i = 0; i < child.length; i++) {
            child[i].getElementsByClassName('tag_index')[0].innerText = (i+1) + '. ';
            var data_id = child[i].getElementsByTagName('a')[0].getAttribute('data-id');

            var hidden = `<input type="hidden" name="hidden_{{ $config->get('id') }}[]" value="${data_id}">`;
            $(".{{ $config->get('id') }}_input_hidden").append(hidden);
        }
    }
</script>