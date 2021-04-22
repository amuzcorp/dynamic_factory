@section('page_title')
    <h2>{{ $cpt->cpt_name }}</h2>
@stop

@section('page_description')
    <small>{{ $cpt->description }}</small>
@endsection

<div class="row">
    <div class="col-sm-12">
        <div class="admin-tab-info">
            <ul class="admin-tab-info-list">
                @foreach ($stateTypeCounts as $stateType => $count)
                    <li @if (Request::get('stateType', 'all') === $stateType) class="on" @endif>
                        <a href="{{ route($current_route_name, ['type' => 'list', 'stateType' => $stateType]) }}" class="__plugin-install-link admin-tab-info-list__link">{{ xe_trans('dynamic_factory::' . $stateType) }} <span class="admin-tab-info-list__count">{{ $count }}</span></a>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="panel-group">
            <div class="panel">
                <div class="panel-heading">
                    <div class="pull-left">
                        <h3 class="panel-title">{{ $cpt->cpt_name }} 목록</h3>
                    </div>
                    <div class="pull-right">
                        <a href="{{ route('dyFac.setting.edit', ['cpt_id' => $cpt->cpt_id]) }}" class="xe-btn xe-btn-positive-outline"><i class="xi-cog"></i> 설정</a>
                        <a href="{{ route($current_route_name, ['type' => 'create']) }}" class="xe-btn xe-btn-primary" data-toggle="xe-page-modal"><i class="xi-file-text-o"></i> {{ sprintf($cpt->labels['new_add_cpt'], $cpt->cpt_name) }}</a>
                    </div>
                </div>
                <div class="panel-heading">
                    <div class="pull-left">
                        <div class="btn-group __xe_function_buttons" role="group" aria-label="...">
                            <button type="button" class="btn btn-default __xe_button" data-mode="trash">{{xe_trans('xe::trash')}}</button>
                        </div>
                    </div>
                    <div class="pull-right">
                        <form id="__xe_search_form" class="input-group search-group">
                            <div class="input-group-btn __xe_btn_search_target">
                                <input type="hidden" name="search_target" value="{{ Request::get('search_target') }}">
                                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="__xe_text">{{Request::has('search_target') && Request::get('search_target') != '' ? xe_trans('board::' . $searchTargetWord) : xe_trans('xe::select')}}</span> <span class="caret"></span></button>
                                <ul class="dropdown-menu" role="menu">
                                    <li @if(Request::get('search_target') == '') class="active" @endif><a href="#" value="">{{xe_trans('board::select')}}</a></li>
                                    <li @if(Request::get('search_target') == 'title_pure_content') class="active" @endif><a href="#" value="title_pure_content">{{xe_trans('board::titleAndContent')}}</a></li>
                                    <li @if(Request::get('search_target') == 'title') class="active" @endif><a href="#" value="title">{{xe_trans('board::title')}}</a></li>
                                    <li @if(Request::get('search_target') == 'pure_content') class="active" @endif><a href="#" value="pure_content">{{xe_trans('board::content')}}</a></li>
                                    <li @if(Request::get('search_target') == 'writer') class="active" @endif><a href="#" value="writer">{{xe_trans('board::writer')}}</a></li>
                                    <li @if(Request::get('search_target') == 'writeId') class="active" @endif><a href="#" value="writerId">{{ xe_trans('board::writerId') }}</a></li>
                                </ul>
                            </div>
                            <div class="search-input-group">
                                <input type="text" name="search_keyword" class="form-control" aria-label="Text input with dropdown button" placeholder="{{xe_trans('xe::enterKeyword')}}" value="{{Request::get('search_keyword')}}">
                                <button class="btn-link">
                                    <i class="xi-search"></i><span class="sr-only">{{xe_trans('xe::search')}}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="panel-body">
                    <form class="__xe_form_list" method="post">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <table class="table">
                            <thead>
                            <tr>
                                <th scope="col"><input type="checkbox" class="__xe_check_all"></th>
                                <th>#</th>
                            @foreach($config['listColumns'] as $columnName)
                                @if($columnName == 'title')
                                    <th>{{ $cpt->labels['title'] }}</th>
                                @else
                                    <th>{{ xe_trans($column_labels[$columnName]) }}</th>
                                @endif
                            @endforeach
                                <th>상태</th>
                            </tr>
                            </thead>
                            <tbody>
                            @if ($cptDocs->count() == 0)
                                <tr>
                                    <td colspan="{{ count($config['listColumns']) + 3 }}" style="padding:40px 0; text-align: center;">게시물이 없습니다.</td>
                                </tr>
                            @endif
                            @if ($cptDocs->count() > 0)
                                @foreach($cptDocs as $doc)
                                <tr>
                                    <td><input type="checkbox" name="id[]" class="__xe_checkbox" value="{{ $doc->id }}"></td>
                                    <td>{{ $doc->seq }}</td>
                                    @foreach($config['listColumns'] as $columnName)
                                        @if ($columnName === 'title')
                                            <td>
                                                <a href="{{ route('dyFac.setting.'.$cpt->cpt_id, ['type' => 'edit', 'doc_id' => $doc->id]) }}">
                                                    {!! $doc->title == null ? '<span style="font-style: italic; color:#999;">[제목없음]</span>' : $doc->title !!}
                                                </a>
                                            </td>
                                        @elseif ($columnName === 'writer')
                                            <td>
                                                @if ($doc->user !== null)
                                                    {{ $doc->user->getDisplayName() }}
                                                @else
                                                    Guest
                                                @endif
                                            </td>
                                        @elseif ($columnName === 'assent_count')
                                            <td>{{ $doc->assent_count }}</td>
                                        @elseif ($columnName === 'dissent_count')
                                            <td>{{ $doc->dissent_count }}</td>
                                        @elseif ($columnName === 'read_count')
                                            <td>{{ $doc->read_count }}</td>
                                        @elseif ($columnName === 'created_at')
                                            <td>{{ $doc->created_at->format('Y-m-d H:i:s') }}</td>
                                        @elseif ($columnName === 'updated_at')
                                            <td>{{ $doc->updated_at->format('Y-m-d H:i:s') }}</td>
                                        @else
                                            <td>
                                                @if (($fieldType = XeDynamicField::get('documents_'.$cpt->cpt_id, $columnName)) !== null)
                                                    <div class="xe-list-board-list__dynamic-field xe-list-board-list__dynamic-field-{{ $columnName }} xe-list-board-list__mobile-style">
                                                        <span class="sr-only">{{ xe_trans($column_labels[$columnName]) }}</span>
                                                        @if($fieldType->getId() == 'fieldType/dynamic_field_extend@instance_selector')
                                                            {{ get_menu_instance_name($fieldType->getSkin()->output($columnName, $doc->getAttributes())) }}
                                                        @else
                                                        {!! $fieldType->getSkin()->output($columnName, $doc->getAttributes()) !!}
                                                        @endif
                                                    </div>
                                                @else
                                                    {!! $doc->{$columnName} !!}
                                                @endif
                                            </td>
                                        @endif
                                    @endforeach
                                    <td>
                                        @if($doc->isTemp() === true)<span class="xe-badge xe-warning">임시</span>
                                        @elseif($doc->isPrivate() === true)<span class="xe-badge xe-black">비공개</span>
                                        @elseif($doc->isPublic() === true && $doc->isPublished() === true)<span class="xe-badge xe-success">발행</span>
                                        @elseif($doc->isPublic() === true && $doc->isPublishReserved() === true)<span class="xe-badge xe-primary">예약</span>
                                        @endif

                                    </td>
                                </tr>
                                @endforeach
                            @endif
                            </tbody>
                        </table>
                    </form>
                </div>
                @if ($cptDocs->count() > 0)
                    <div class="panel-footer">
                        <div class="text-center" style="padding: 24px 0;">
                            <nav>
                                {!! $cptDocs->render() !!}
                            </nav>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function ($) {
        $('.__xe_check_all').click(function () {
            if ($(this).is(':checked')) {
                $('input.__xe_checkbox').prop('checked', true);
            } else {
                $('input.__xe_checkbox').prop('checked', false);
            }
        });

        $('.__xe_function_buttons .__xe_button').click(function (e) {
            e.preventDefault();

            var mode = $(this).attr('data-mode'), flag = false;

            $('input.__xe_checkbox').each(function () {
                if ($(this).is(':checked')) {
                    flag = true;
                }
            });

            if (flag !== true) {
                alert('select document');
                return;
            }

            var $f = $('.__xe_form_list');
            $('<input>').attr('type', 'hidden').attr('name', 'redirect').val(location.href).appendTo($f);

            eval('actions.' + mode + '($f)');
        });

        $('.__xe_btn_search_target .dropdown-menu a').click(function (e) {
            e.preventDefault();

            $('[name="search_target"]').val($(this).attr('value'));
            $('.__xe_btn_search_target .__xe_text').text($(this).text());

            $(this).closest('.dropdown-menu').find('li').removeClass('active');
            $(this).closest('li').addClass('active');
        });
    });

    var actions = {
        trash: function ($f) {
            $f.attr('action', '{{ route('dyFac.setting.trash_cpt_documents') }}');
            send($f);
        }
    };

    var send = function($f) {
        if(confirm('선택한 글들을 휴지통으로 이동하시겠습니까?')) {
            var url = $f.attr('action'),
                params = $f.serialize();

            XE.ajax({
                type: 'post',
                dataType: 'json',
                data: params,
                url: url,
                success: function (response) {
                    document.location.reload();
                }
            });
        }
    }
</script>
