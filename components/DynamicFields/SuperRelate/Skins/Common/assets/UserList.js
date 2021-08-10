class UserList {
  constructor ({ wrapper_id, $wrapper, searchUrl, field_name, config_name, config_id }) {
    this.wrapper_id = wrapper_id
    this.$wrapper = $wrapper
    this.searchUrl = searchUrl
    this.field_name = field_name
    this.config_name = config_name
    this.config_id = config_id
    this.MIN_QUERY_LENGTH = 2
    this.ENTER = 13
    this.TAB = 9
    this.BACKSPACE = 8
    this.UP_ARROW = 38
    this.DOWN_ARROW = 40
    this.ESCAPE = 27
  }

  bindEvents () {
    var _this = this

    this.$wrapper.on('change', function (e) {
      console.log('changed');
    })

    this.$wrapper.on('keydown', function (e) {
      var query = e.target.value.trim()
      var $this = $(this)
      var keyCode = e.keyCode

      if (query.length >= _this.MIN_QUERY_LENGTH) {
        console.log('keydown');
      }
    })

    this.$wrapper.find('.ReactTags__suggestions').on('mouseenter', 'li', function () {
      var $this = $(this)

      $this.addClass('active').siblings().removeClass('active')
    })

    this.$wrapper.find('.ReactTags__suggestions').on('click', 'li', function () {
      var $this = $(this)
      var tag = $this.data('tag')
      var $ul = $this.closest('ul')
      var $input = $this.closest('.ReactTags__tagInput').find('input:text')
      var $input_hidden = $this.closest('.ReactTags__tags').find('.input_hidden')
      var name = _this.type
      var bSameWord = false
      var wrapper_id = _this.wrapper_id
      var config_id = _this.config_id
      $('#'+_this.wrapper_id).find('.input_hidden input[type=hidden]').each(function() {
        if($(this).val() == tag.id) {
          bSameWord = true
        }
      })

      if (!bSameWord) {
        // 값이 변경되었음
        $('#'+wrapper_id + ' input[name='+config_id+'_srf_chg]').val('1')

        //단일선택 리스트 내용 지움
        $input_hidden.find('input').remove();

        //내용 append
        $input_hidden.append(`<input type="hidden" name="${_this.field_name}[]" value="${tag.id}">`)

        //단일선택 리스트 내용 지움
        $ul.closest('.ReactTags__tags').find('.ReactTags__tag').remove()

        $ul.closest('.ReactTags__tags').find('.ReactTags__selected')
          .append(`<span class="ReactTags__tag">${(tag.display_name || tag.name)}<a class="ReactTags__remove btnRemoveTag" data-id="${tag.id}">x</a></span>`)
      }

      $ul.remove()
      $input.val('').data('index', -1).focus()
    })

    this.$wrapper.on('keyup', function (e) {
      var query = e.target.value.trim()
      var $this = $(this)
      var keyCode = e.keyCode

      if (query.length >= _this.MIN_QUERY_LENGTH) {
        if ([_this.ENTER, _this.TAB, _this.UP_ARROW, _this.DOWN_ARROW, _this.ESCAPE, 37, 39].indexOf(keyCode) == -1) {
          var temp = ''
          temp += `<ul>`
          temp += `<li>Searching ... <i class="xi-spin xi-spinner-1"></i></li>`
          temp += `</ul>`

          $this.parent().find('.ReactTags__suggestions').html(temp)

          _this.searchTitle($this, query)
        }
      } else {
        $this.parent().find('.ReactTags__suggestions').empty()
      }
    })

    this.$wrapper.on('click', '.btnRemoveTag', function (e) {
      e.preventDefault()

      var id = $(this).data('id')
      var field_id = _this.field_name
      var wrapper_id = _this.wrapper_id
      var config_id = _this.config_id
      // 값이 변경되었음
      $('#'+wrapper_id + ' input[name='+config_id+'_srf_chg]').val('1')

      $(this).closest('span').remove()

      $('#'+wrapper_id).find('.input_hidden input[type=hidden]').each(function() {
        if($(this).val() == id) {
          $(this).remove()
        }
      })

    })
  }

  makeIt (item, query) {
    // console.log(item, query);
    var escapedRegex = query.trim().replace(/[-\\^$*+?.()|[\]{}]/g, '\\$&')
    var r = RegExp(escapedRegex, 'gi')
    var itemName = item.display_name || item.name

    return itemName.replace(r, '<mark>$&</mark>')
  }

  searchTitle ($input, keyword) {
    var _this = this
    var searchUrl = _this.searchUrl

    XE.ajax({
      url: searchUrl + '/' + keyword,
      method: 'get',
      data: {
        'cn' : _this.config_name
      },
      dataType: 'json',
      cache: false,
      success: function (data) {
        if (data.length > 0) {
          var temp = ''
          temp += `<ul>`

          data.forEach(function (item, i) {
            temp += `<li class="" data-tag='${JSON.stringify(item)}'>`
            temp += `<span>${_this.makeIt(item, keyword)}</span>`
            temp += `</li>`
          })

          temp += `</ul>`

          $input.parent().find('.ReactTags__suggestions').html(temp)
        } else {
          $input.parent().find('.ReactTags__suggestions').empty()
        }
      },
      error: function (xhr, status, err) {

      }
    })
  }
}
