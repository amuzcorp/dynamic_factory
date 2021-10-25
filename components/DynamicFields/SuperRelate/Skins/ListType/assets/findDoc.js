jQuery(document).ready( function($) {
  $(".relate_tags").each(function() {
    var config_id = $(this).data('config_id');
    $(this).sortable({
      update:function(e) {
        $("input[name=" + config_id + "_srf_chg").val( 1 );
        setTagIndex(config_id);
      }
    });
    $("#"+config_id+"_tags").disableSelection();
  });

  //bind events
  $('.cpt_search_doc').click(function() {
    var configName = $(this).data('config_name');
    var configId = $(this).data('config_id');
    cpt_search(configId, configName);
  });

  $(".cpt_search_doc_input").on("keyup",function(key){
    var configName = $(this).data('config_name');
    var configId = $(this).data('config_id');
    if(key.keyCode === 13) cpt_search(configId, configName);
  });
});

function cpt_search(configId, configName) {
  let keyword = $("input[name=" + configId + "_cpt_search]").val();
  var searchUrl = $("#sr_configs_" + configId).data('target_url');

  var listId =  configId + "_cptListItem";

  XE.ajax({
  url: searchUrl + '/' + keyword,
  method: 'get',
  data: {
    //TODO 이거 지금은 configName에 id가 들어가는데 name을 꼭받아야하는가?
  'cn': configName
  },
  dataType: 'json',
  cache: false,
  success: function (data) {
    document.getElementById(listId).innerHTML = '';
    var str = '';
    var title = '';
    var hidden = 0;
    if (data.length > 0) {
    var inputs = $("." + configId + "_input_hidden input");
    var clip_list = [];
    for(let i = 0; i < inputs.length; i++) {
    if(inputs[i].value) clip_list.push(inputs[i].value);
  }
  for(let i = 0; i < data.length; i++) {
    title = '';
    if($("#sr_configs_" + configId).data('r_instance_id') === 'user') {
      title = data[i].display_name;
    } else {
      title = data[i].title;
    }
      if (clip_list.includes(data[i].id)) {
      str += `<li class="item-latest" id="${data[i].id}" onclick="selectItem('`+configId+`', '${data[i].id}', '${title}')" style="display: none;">${title}</li>`;
      hidden += 1;
    } else {
      str += `<li class="item-latest" id="${data[i].id}" onclick="selectItem('`+configId+`', '${data[i].id}', '${title}')">${title}</li>`;
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
  function selectItem(configName, id, name) {
  var tagIndex = document.getElementById(configName + "_tags").getElementsByClassName('ReactTags__tag');
  var index_no = (tagIndex.length + 1);

  var hidden = `<input type="hidden" name="hidden_` + configName + `[]" value="${id}">`;
  var tags = `<span class="ReactTags__tag" id="tag_${id}">
                        <span class="tag_index">${index_no}. </span>${name}
                        <a class="ReactTags__remove btnRemoveTag" data-id="${id}" onclick="remove_cpt_item(this, '` + configName + `')">x</a>
                    </span>`;

  $("." + configName + "_input_hidden").append(hidden);
  $("#" + configName + "_tags").append(tags);

  $("#"+id).hide();
  $("input[name=" + configName + "_srf_chg").val( 1 );

  var lists = document.querySelectorAll("#" + configName + "_cptListItem li");
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
  function remove_cpt_item(event, configName) {
  var target_id = $(event).data('id');
  document.getElementById('tag_' + target_id).remove();
  var inputs = $("."+ configName +"_input_hidden input");
  for(let i = 0; i < inputs.length; i++) {
  if(inputs[i].value === target_id) inputs[i].remove();
}
  $("#"+target_id).show();
  $('#noItem').hide();

  $("input[name="+ configName +"_srf_chg").val( 1 );

  setTagIndex(configName);
}
function setTagIndex(config_id) {
  var tag_document = document.getElementById(config_id + "_tags");
  var child = tag_document.getElementsByClassName('ReactTags__tag');

  document.getElementsByClassName(config_id + "_input_hidden")[0].innerHTML = '';
  for(let i = 0; i < child.length; i++) {
    child[i].getElementsByClassName('tag_index')[0].innerText = (i+1) + '. ';
    var data_id = child[i].getElementsByTagName('a')[0].getAttribute('data-id');

    var hidden = `<input type="hidden" name="hidden_` + config_id + `[]" value="${data_id}">`;
    $("."+config_id+"_input_hidden").append(hidden);
  }
}
