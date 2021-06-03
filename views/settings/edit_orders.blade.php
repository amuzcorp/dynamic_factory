@section('page_title')
    <h2>{{ $cpt['cpt_name'] }} - 정렬</h2>
@endsection

@include('dynamic_factory::views.settings.tab')

<div class="row">
    <div class="col-sm-12">
        <div class="panel-group">
            <div class="panel">
                <div class="panel-heading clearfix">
                    <div class="pull-left">
                        <h3 class="panel-title">정렬</h3><br>
                        <small>리스트 출력시 OrderBy 조건을 설정 할 수 있습니다. 없을 경우 기본 정렬을 사용합니다.</small>
                    </div>
                    <div class="pull-right">
                        <button type="button" class="btn btn-info" data-toggle="modal" data-target="#orderModal">새 정렬 추가</button>
                    </div>

                </div>

                <form method="post" action="{{ route('dyFac.setting.update_orders', ['cpt_id' => $cpt['cpt_id']]) }}">
                    <input type="hidden" name="_token" value="{{{ Session::token() }}}" />

                    <div id="collapseTwo" class="panel-collapse collapse in">
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <div class="table-responsive item-setting">
                                            <table class="table table-sortable">
                                                <colgroup>
                                                    <col width="200">
                                                    <col>
                                                    <col>
                                                </colgroup>
                                                <tbody id="order_tbody">
                                                @foreach($config->get('orders', []) as $orders)
                                                    @php
                                                        $order_arr = explode('|@|', $orders);
                                                    @endphp
                                                    <tr><td>
                                                        <button type="button" class="btn handler"><i class="xi-drag-vertical"></i></button>
                                                        <em class="item-title">{{ $columnLabels[$order_arr[0]] }} ({{  $order_arr[0] }})</em>
                                                    </td><td>
                                                        <span class="item-subtext">{{ $order_arr[1] == 'asc' ? '오름차순 (Ascending)' : '내림차순 (Descending)' }}</span>
                                                        <input type="hidden" name="orders[]" value="{{ $orders }}" />
                                                    </td><td>
                                                        <div class="pull-right"><button type="button" class="btn btn-danger delBtn">삭제</button></div>
                                                    </td></tr>
                                                @endforeach
                                                @if(count($config->get('orders', [])) == 0)
                                                <tr id="no_data"><td colspan="3" class="text-align--center" style="padding:20px 0;">등록된 정렬 규칙이 없습니다.</td></tr>
                                                @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="panel-footer">
                            <button type="submit" class="btn btn-primary"><i class="xi-download"></i>{{xe_trans('xe::save')}}</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderModalLabel">새 정렬 추가</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="field_id">필드명</label>
                    <select class="form-control" id="field_id" aria-describedby="fieldIdHelp">
                        @foreach($sortListColumns as $column)
                            <option value="{{ $column }}">{{ array_get($columnLabels, $column) }} ({{ $column }})</option>
                        @endforeach
                    </select>
                    <small id="fieldIdHelp" class="form-text text-muted">정렬할 필드를 선택하세요.</small>
                </div>
                <div class="form-group">
                    <label for="order_type">정렬 형식</label>
                    <select class="form-control" id="order_type">
                        <option value="desc">내림차순 (Descending)</option>
                        <option value="asc">오름차순 (Ascending)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
                <button type="button" class="btn btn-primary" id="addBtn">추가</button>
            </div>
        </div>
    </div>
</div>

{{ XeFrontend::js('assets/vendor/jqueryui/jquery-ui.min.js')->load() }}

<script type="text/javascript">
    $(function() {
        // sortable 한 table 구현해야 함
        $(".table-sortable tbody").sortable({
            handle: '.handler',
            cancel: '',
            update: function( event, ui ) {
            },
            start: function(e, ui) {
                ui.placeholder.height(ui.helper.outerHeight());
                ui.placeholder.css("display", "table-row");
                ui.helper.css("display", "table");
            },
            stop: function(e, ui) {
                $(ui.item.context).css("display", "table-row");
                ui.item.attr('style', '');  // overcode added!
            }
        }).disableSelection();

        $(".table-sortable tbody").closest('form').bind('submit', function(event) {
            var list = [];

            $('[name="listColumns[]"]').each(function() {
                list.push($(this).val());
            });

            $('[name="sortListColumns[]"]').remove();
            for (var i in list) {
                $(this).append($('<input type="hidden" name="sortListColumns[]">').val(list[i]));
            }

            list = [];
            $('[name="formColumns[]"]').each(function() {
                list.push($(this).val());
            });

            $('[name="sortFormColumns[]"]').remove();
            for (var i in list) {
                $(this).append($('<input type="hidden" name="sortFormColumns[]">').val(list[i]));
            }
        });

        // Modal - 추가 버튼 누를시
        $('#addBtn').on('click', function() {
            let field_id = $('#field_id').val();
            let field_text = $('#field_id option:selected').text();
            let order_type = $('#order_type').val();
            let order_type_text = order_type == 'asc' ? '오름차순 (Ascending)' : '내림차순 (Descending)';

            let html = `
<tr><td>
    <button type="button" class="btn handler"><i class="xi-drag-vertical"></i></button>
    <em class="item-title">${field_text}</em>
</td><td>
    <span class="item-subtext">${order_type_text}</span>
    <input type="hidden" name="orders[]" value="${field_id}|@|${order_type}" />
</td><td>
    <div class="pull-right"><button type="button" class="btn btn-danger delBtn">삭제</button></div>
</td></tr>`;

            $('#no_data').hide();
            // 중복체크후 없으면 추가
            $('#orderModal').modal('hide');
            $('#order_tbody').append(html);
        });

        $('.delBtn').on('click', function() {
            $(this).closest('tr').remove();
        });
    });
</script>
