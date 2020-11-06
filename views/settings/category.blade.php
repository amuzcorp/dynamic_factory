@section('page_title')
    <h2>카테고리 관리</h2>
@endsection
<div class="row">
    <div class="col-sm-12">
        <div class="panel">
            <div class="panel-body">
                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>카테고리 명</th>
                        <th>슬러그</th>
                        <th>템플릿</th>
                        <th>사용중인 유형</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($categories as $category)
                        <tr>
                            <td>{{ $category->category_id }}</td>
                            <td>
                                <a href="{{ route('dyFac.setting.create_taxonomy',[ 'tax_id' => $category->category_id]) }}">{{ xe_trans($category->category->name) }}</a>
                            </td>
                            <td>{{ $category->slug }}</td>
                            <td>{{ $category->template }}</td>
                            <td>
                            @foreach($category->cpt_tax as $cpt_tax)
                                @if(!empty(app('overcode.df.service')->getItem($cpt_tax->cpt_id)->cpt_name))
                                <a href="{{ route('dyFac.setting.edit', ['cpt_id' => $cpt_tax->cpt_id]) }}">
                                    <span class="badge badge-secondary">
                                    {{ app('overcode.df.service')->getItem($cpt_tax->cpt_id)->cpt_name }}
                                    </span>
                                </a>
                                @endif
                            @endforeach
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
