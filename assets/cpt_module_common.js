jQuery(document).ready(function(){
  $(".doFavorite").click(function(){
    var that = $(this);
    XE.ajax({
      url: $(this).data('favorite-url'),
      type: 'post',
      data: {},
      success: function (json) {
        if(json.favorite === true){
          that.addClass('on');
        }else{
          that.removeClass('on');
        }
      }
    });
  });
});
