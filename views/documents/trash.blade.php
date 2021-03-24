@section('page_title')
    <h2>휴지통 관리</h2>
@stop

@section('page_description')
    <small>휴지통에 있는 사용자 정의 문서를 복원 또는 삭제합니다.</small>
@endsection

<div class="row">
    <div class="col-sm-12">
        <div class="panel-group">
            <div class="panel">
                <div class="panel-heading">
                    <div class="pull-left">
                        <h3 class="panel-title">휴지통</h3> (total : {{$cptDocs->count()}})
                    </div>
                </div>
                <div class="panel-heading">
                    <div class="pull-left">
                        <div class="btn-group __xe_function_buttons" role="group" aria-label="...">
                            <button type="button" class="btn btn-default __xe_button" data-mode="restore">{{xe_trans('xe::restore')}}</button>
                            <button type="button" class="btn btn-default __xe_button" data-mode="destroy">{{xe_trans('xe::destroy')}}</button>
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
                                @if(is_null($cpt_id))
                                <th>CPT ID</th>
                                @endif
                                @foreach($listColumns as $columnName)
                                    <th>{{ xe_trans($column_labels[$columnName]) }}</th>
                                @endforeach
                            </tr>
                            </thead>
                            <tbody>
                            @if ($cptDocs->count() == 0)
                                <tr>
                                   <td colspan="{{ count($listColumns) + 3 }}" style="padding:40px 0; text-align: center;">게시물이 없습니다.</td>
                                </tr>
                            @endif
                            @if ($cptDocs->count() > 0)
                                @foreach($cptDocs as $doc)
                                    <tr>
                                        <td><input type="checkbox" name="id[]" class="__xe_checkbox" value="{{ $doc->id }}"></td>
                                        <td>{{ $doc->seq }}</td>

                                        @if(is_null($cpt_id))
                                        <td>{{ $doc->instance_id }}</td>
                                        @endif

                                        @foreach($listColumns as $columnName)
                                            @if ($columnName === 'title')
                                                <td><a href="{{ route('dyFac.setting.'.$doc->type, ['type' => 'edit', 'doc_id' => $doc->id]) }}">{{ $doc->title }}</a></td>
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
                                                            {!! $fieldType->getSkin()->output($columnName, $doc->getAttributes()) !!}
                                                        </div>
                                                    @else
                                                        {!! $doc->{$columnName} !!}
                                                    @endif
                                                </td>
                                            @endif
                                        @endforeach
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
    });

    var actions = {
        restore: function ($f) {
            $f.attr('action', '{{ route('dyFac.setting.restore_cpt_documents') }}');
            send($f);
        },
        destroy: function ($f) {
            $f.attr('action', '{{ route('dyFac.setting.remove_cpt_documents') }}');
            send($f);
        },
    };

    var send = function($f) {
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
</script>
