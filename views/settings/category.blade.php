@section('page_title')
    <div class="clearfix">
        <h2 class="pull-left">카테고리 관리</h2>
        <a href="{{ route('dyFac.setting.create_taxonomy') }}" class="xu-button xu-button--primary pull-right">새 카테고리 추가</a>
    </div>
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
                        <th>삭제</th>
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
                            <td>
                                <a class="btn btn-danger cate_del_btn" data-category_id="{{ $category->category_id }}">삭제</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
$(document).ready(function() {
    $('.cate_del_btn').on('click', function() {
        var category_id = $(this).data('category_id');

        if(confirm('정말 이 카테고리를 삭제 하시겠습니까?')) {
            window.XE.ajax({
                url: '{{ route('dyFac.setting.category.delete') }}',
                type: 'post',
                dataType: 'json',
                data: {id: category_id}
            }).done(function (json) {
                console.log(json);
            })
        }
    });
});
</script>
