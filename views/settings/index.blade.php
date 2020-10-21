@section('page_title')
    <div class="clearfix">
        <h2 class="pull-left">CPT 관리</h2>
        <a href="{{ route('d_fac.setting.create') }}" class="xu-button xu-button--primary pull-right">새 유형 추가</a>
    </div>
@endsection
<div class="row">
    <div class="col-sm-12">
        <div class="panel">
            <div class="panel-body">
                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th>글 유형</th>
                        <th>확장 필드</th>
                        <th>분류</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($cpts as $cpt)
                    <tr>
                        <td>
                            <a href="{{ route('d_fac.setting.edit', ['cpt_id' => $cpt->cpt_id]) }}">{{ $cpt->cpt_name }}</a>
                        </td>
                        <td>
                            <a href="{{ route('d_fac.setting.create_extra', ['cpt_id' => $cpt->cpt_id]) }}" class="btn btn-sm btn-warning">확장 필드 추가</a>
                        </td>
                        <td></td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
