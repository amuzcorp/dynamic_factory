jQuery(document).ready(function(){
  $(".doFavorite").click(function(){
    var that = $(this);
    var class_name = $(this).data('toggle-class') ? $(this).data('toggle-class') : 'on';
    XE.ajax({
      url: $(this).data('favorite-url'),
      type: 'post',
      data: {},
      success: function (json) {
        if(json.favorite === true){
          that.addClass(class_name);
        }else{
          that.removeClass(class_name);
        }
      }
    });
  });
});
