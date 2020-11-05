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
                        <th>제목</th>
                        <th>작성자</th>
                        <th>작성일</th>
                        <th>수정일</th>
                    </tr>
                    </thead>
                    <tbody>
                @if ($cptDocs->count() > 0)
                    @foreach($cptDocs as $doc)
                    <tr>
                        <td>{{ $doc->title }}</td>
                        <td>
                            @if ($doc->user !== null)
                                {{ $doc->user->getDisplayName() }}
                            @else
                                Guest
                            @endif
                        </td>
                        <td>{{ $doc->created_at->format('Y-m-d H:i:s') }}</td>
                        <td>{{ $doc->updated_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                    @endforeach
                @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
