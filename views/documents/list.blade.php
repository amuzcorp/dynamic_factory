@section('page_title')
    <div class="clearfix">
        <h2 class="pull-left">{{ $cpt->cpt_name }} 리스트</h2>
        <a href="{{ route($current_route_name, ['type' => 'create']) }}" class="xu-button xu-button--primary pull-right">{{ sprintf($cpt->labels['new_add_cpt'], $cpt->cpt_name) }}</a>
    </div>
@endsection
<div class="row">
    <div class="col-sm-12">
        <div class="panel">
            <div class="panel-body">
                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
