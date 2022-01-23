@php
    use Overcode\XePlugin\DynamicFactory\Components\UIObjects\TaxoSelect\TaxoSelectUIObject;
@endphp

<script type="text/javascript">
    function getChildTaxonomies(name, title, taxo_id) {
        XE.ajax({
            url: '{{route('dyFac.child.taxonomies')}}',
            method: 'get',
            data: {
                taxo_id: taxo_id
            },
            cache: false,
            success: function (data) {
                var response = data.taxonomies;
                var sub_item_count = +$('input[name=' + name + '_sub_item_count]').val();
                var str = '';
                if(response.length !== 0) {
                    str = ` <div id="${name}_${sub_item_count}_form"><label>${title}</label>
                            <div class="xe-dropdown __xe-dropdown-form">
                                <select class="form-control" name="${name}_select_${sub_item_count}" onchange="selectCategory('${name}', this, 'sub', ${sub_item_count + 1})">
                                   <option value="0_0">${title} 전체</option>`;
                    var child = 0;
                    for(let i = 0; i < response.length; i++) {
                        if(response[i].child === true) {
                            child = 1;
                        } else {
                            child = 0;
                        }
                        str += `<option value="${response[i].id}_${child}">${response[i].word}</option>`;
                    }
                    str += `</div></select></div>`;

                    $("#"+name+"_depth_sub_categories").append(str);
                }
            }
        });
    }
</script>

@if($value === "")
    @php
        $value = [];
    @endphp
@endif
<div id="{{$name}}_depth_categories">

    <input type="hidden" name="{{$name}}_sub_item_count" value="{{count($childItem) > 0 ? count($childItem) - 1 : 0}}" />

    <input type="hidden" id="{{$name}}_0_value" name="{{$name}}[]" value="{{isset($value[0]) ? $value[0] : 0}}" />

    <div id="{{$name}}_category_selected">
        @php $index = 1; @endphp
        @if($value !== "")
            @foreach($value as $key => $val)
                @if($key > 0)
                    <input type="hidden" id="{{$name}}_{{$index}}_value" name="{{$name}}[]" value="{{$val}}" />
                    @php $index += 1; @endphp
                @endif
            @endforeach
        @endif
    </div>
    <div class="xe-dropdown __xe-dropdown-form">

        <select class="form-control" name="{{$name}}_select_0" onchange="selectCategory('{{$name}}', this, 'first', 1)">
            <option value="0_0">전체</option>
            @foreach($items as $item)
                <option value="{{$item['value']}}_{{TaxoSelectUIObject::hasChildren($item) ? 1 : 0}}" @if(isset($value[0]) && (int) $value[0] === (int)$item['value']) selected="" @endif>{{$item['text']}}</option>
            @endforeach
        </select>
    </div>
</div>
<div id="{{$name}}_depth_sub_categories">
    @php $index = 1; @endphp
    @foreach($childItem as $key => $child_items)
        @if(count($child_items) !== 0)
            <div id="{{$name}}_{{$index}}_form">
                <label>{{xe_trans($selectedItemCollection[$key]->word)}}</label>
                <div class="xe-dropdown __xe-dropdown-form">
                    <select class="form-control" name="{{$name}}_select_{{$index}}" onchange="selectCategory('{{$name}}', this, 'sub', {{$index + 1}})">
                        <option value="0_0">{{xe_trans($selectedItemCollection[$key]->word)}} 전체</option>
                        @foreach($child_items as $item)
                            <option value="{{$item->id}}_{{$item->child === true ? 1 : 0}}" @if(in_array((string) $item->id, $value)) selected @endif>{{xe_trans($item->word)}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @php $index += 1; @endphp
        @endif
    @endforeach
</div>

<script>
    function selectCategory(name, target, type, index) {
        var value = +target.value.split("_")[0];
        var child = +target.value.split("_")[1];
        var selected_text = target[target.selectedIndex].text;

        let new_count = 0;

        if(document.getElementById(name+"_input")) {
            document.getElementById(name+"_input").value = value;
        } else {

            var count = +$('input[name=' + name + '_sub_item_count]').val();

            if(type === 'first'){
                document.getElementById(name+"_category_selected").innerHTML = '';
                document.getElementById(name+"_depth_sub_categories").innerHTML = '';
                if(value !== 0) {
                    document.getElementById(name + "_0_value").value = value;
                    $('input[name=' + name + '_sub_item_count]').val(1);
                } else {
                    document.getElementById(name + "_0_value").value = 0;
                    $('input[name=' + name + '_sub_item_count]').val(0);
                }
            } else {
                var remove_count = 0;
                for(let i = 0; i < index + 1; i++) {
                    if(i > (index - 1)) {
                        if(document.getElementById(name + '_' + i + '_form')) {
                            $("#" + name + '_' + i + '_form').remove();
                            $("#" + name + '_' + i + '_value').remove();
                            remove_count++;
                        }
                    }
                }
                new_count = count - remove_count;
                $('input[name=' + name + '_sub_item_count]').val( new_count );
            }

            if(type !== 'first') {
                if(document.getElementById(name + "_" + (index - 1) + "_value")) {
                    document.getElementById(name + "_" + (index - 1) + "_value").value = value;
                } else {
                    if(value !== 0) {
                        var str = `<input type="hidden" id="${name}_${(index - 1)}_value" name="${name}[]" value="${value}" />`;
                        $("#" + name + "_category_selected").append(str);
                    }
                }
            }

            if(value !== 0) {
                if (child === 1) {
                    new_count = new_count + 1;
                    $('input[name=' + name + '_sub_item_count]').val(new_count);

                    getChildTaxonomies(name, selected_text, value)
                }
            }

        }
    }
</script>

<style>
.xe-dropdown-menu li.on a {
    color: unset;
}
.xe-select-label .label-input ul,
.xe-select-label .label-list ul {
    margin: 0;
    padding: 0
}

.xe-select-label .label-input ul li,
.xe-select-label .label-list ul li {
    list-style: none
}

.xe-select-label .label-list li {
    list-style: none
}

.xe-select-label .label-list li a {
    text-decoration: none
}

.xe-dropdown-menu,
.xe-dropdown-menu ul {
    margin: 0;
    padding: 0
}

.xe-dropdown-menu li,
.xe-dropdown-menu ul li {
    list-style: none
}

.xe-dropdown-menu li a,
.xe-dropdown-menu ul li a {
    text-decoration: none
}

.xe-dropdown,
.xe-dropup {
    display: block;
    position: relative;
    font-size: 14px;
    white-space: nowrap;
    vertical-align: middle
}

.xe-dropdown>.xe-btn,
.xe-dropup>.xe-btn,
.xe-select-box.xe-btn {
    display: block;
    position: relative;
    width: 100%;
    padding-right: 37px;
    text-align: left;
    white-space: nowrap;
    outline: none
}

.xe-dropdown>.xe-btn:focus,
.xe-dropdown>.xe-btn.focus,
.xe-dropdown>.xe-btn:active,
.xe-dropdown>.xe-btn.active,
.xe-dropup>.xe-btn:focus,
.xe-dropup>.xe-btn.focus,
.xe-dropup>.xe-btn:active,
.xe-dropup>.xe-btn.active,
.xe-select-box.xe-btn:focus,
.xe-select-box.xe-btn.focus,
.xe-select-box.xe-btn:active,
.xe-select-box.xe-btn.active {
    background-color: #fff
}

.xe-dropdown>.xe-btn:hover,
.xe-dropup>.xe-btn:hover,
.xe-select-box.xe-btn:hover {
    background-color: #fff
}

.xe-dropdown>.xe-btn:active:focus,
.xe-dropup>.xe-btn:active:focus,
.xe-select-box.xe-btn:active:focus {
    background-color: #fff;
    outline: none
}

.xe-dropdown.outline-off>.xe-btn,
.xe-dropup.outline-off>.xe-btn,
.xe-select-box.outline-off.xe-btn {
    border: none
}

.xe-dropdown>.xe-btn::after,
.xe-dropup>.xe-btn::after,
.xe-select-box.xe-btn::after,
.xe-select-label .label-input::after {
    display: inline-block;
    position: absolute;
    right: 15px;
    top: 50%;
    width: 0;
    height: 0;
    margin-top: -2px;
    border-style: solid;
    border-width: 3px;
    border-color: #656973 transparent transparent transparent;
    content: ""
}

.xe-dropup .xe-dropdown-menu {
    margin-top: auto;
    margin-bottom: -1px;
    top: auto;
    bottom: 100%
}

.xe-dropdown-menu {
    display: none;
    overflow-y: auto;
    position: absolute;
    left: 0;
    top: 100%;
    max-height: 366px;
    margin-top: -1px !important;
    border: 1px solid #51586b;
    border-radius: 0 0 2px 2px;
    background-color: #fff;
    z-index: 10
}

.open .xe-dropdown-menu {
    display: block;
    min-width: 100%
}

.xe-dropdown-menu ul li>a {
    position: relative
}

.xe-dropdown-menu ul li>a~ul {
    padding-left: 15px
}

.xe-dropdown-menu ul .xe-dropdown-menu__sub li>a::before {
    content: '⌞';
    position: absolute;
    top: 0px;
    font-size: 1.3em;
    -webkit-transform: translateX(-15px);
    -ms-transform: translateX(-15px);
    transform: translateX(-15px)
}

.xe-dropdown-menu li>a {
    display: block;
    position: relative;
    padding: 8px 15px;
    color: #2c2e37;
    text-align: left;
    line-height: 1.42857
}

.xe-dropdown-menu li>a:hover {
    text-decoration: underline;
    background-color: transparent;
    color: #2c8beb !important
}

.xe-dropdown-menu li.on>a {
    color: #2c8beb
}

.xe-select-box {
    overflow: hidden;
    text-overflow: ellipsis;
    word-break: break-word;
    white-space: nowrap
}

.xe-select-box select {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 34px;
    font-size: 14px;
    opacity: 0;
    filter: alpha(opacity=0);
    -webkit-appearance: menulist-button
}

.xe-select-label {
    position: relative
}

.xe-select-label .label-input {
    display: block;
    position: relative;
    overflow: hidden;
    padding: 0 37px 0 10px;
    background-color: #f6f7f9;
    border: 1px solid #dbdadf;
    border-radius: 2px
}

.xe-select-label .label-input ul li {
    float: left
}

.xe-select-label .label-input ul li:first-child .label-choice {
    margin-left: 0
}

.xe-select-label .label-input ul li .label-choice {
    display: block;
    position: relative;
    margin: 5px 2px;
    padding: 1px 28px 1px 8px;
    color: #fff;
    font-size: 13px;
    background-color: #b5b8bd;
    border: 1px solid #dbdadf;
    border-radius: 4px;
    line-height: 1.42857
}

.xe-select-label .label-input ul li .label-choice button {
    position: absolute;
    right: 3px;
    top: 1px;
    background-color: transparent;
    border: transparent;
    outline: none;
    font-size: 11px;
    color: #fff;
    cursor: pointer
}

.xe-select-label .label-input ul li .search-label {
    height: 21px;
    margin: 5px 2px;
    border-color: transparent;
    background-color: transparent
}

.xe-select-label .label-list {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    width: 100%;
    border: 1px solid #8dc5ff;
    background-color: #fff;
    z-index: 100
}

.xe-select-label.open .label-list {
    display: block
}

.xe-select-label .label-list .label-division {
    padding: 10px;
    font-size: 13px;
    line-height: 1.42857;
    text-align: left
}

.xe-select-label .label-list .label-division+.label-division {
    border-top: 1px solid #dbdbdd
}

.xe-select-label .label-list .label-division strong {
    display: block;
    margin-bottom: 10px;
    color: #333
}

.xe-select-label .label-list .label-division a {
    display: block;
    padding: 0 10px;
    color: #696e7a;
    line-height: 24px;
    border-radius: 4px
}

.xe-select-label .label-list .label-division a:hover {
    background-color: #2992fb;
    color: #fff
}
</style>
