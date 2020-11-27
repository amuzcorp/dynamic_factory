@section('page_title')
    <h2>카테고리 목록</h2>

@endsection
<div class="row">
    <div class="col-sm-12">
        <div class="panel-group">
            <div class="panel">
                <div class="panel-heading">
                    <div class="pull-left"><h4>생성된 카테고리 목록</h4></div>
                    <div class="pull-right">
                        <a href="{{ route('dyFac.setting.create_taxonomy') }}" class="xe-btn xe-btn-primary">새 카테고리 추가</a>
                    </div>
                </div>
                <div class="panel-body">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>ID</th>
                            <th>카테고리 명</th>
                            <th>템플릿</th>
                            <th>사용중인 유형</th>
                            <th>관리</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($categories as $category)
                            <tr>
                                <td>{{ $category->category_id }}</td>
                                <td>{{ $category->slug }}</td>
                                <td>{{ xe_trans($category->category->name) }}</td>
                                <td>{{ $category->template }}</td>
                                <td>
                                @foreach($category->cpt_tax as $cpt_tax)
                                    @if(!empty(app('overcode.df.service')->getItem($cpt_tax->cpt_id)->cpt_name))
                                    <a href="{{ route('dyFac.setting.edit', ['cpt_id' => $cpt_tax->cpt_id]) }}">
                                        <span class="label label-info">
                                        {{ app('overcode.df.service')->getItem($cpt_tax->cpt_id)->cpt_name }}
                                        </span>
                                    </a>
                                    @endif
                                @endforeach
                                </td>
                                <td>
                                    <a class="xe-btn xe-btn-default xe-btn-xs" href="{{ route('dyFac.setting.create_taxonomy',[ 'tax_id' => $category->category_id]) }}">설정</a>
                                    <a class="xe-btn xe-btn-danger xe-btn-xs cate_del_btn" style="color:#fff;" data-category_id="{{ $category->category_id }}">삭제</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
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
                location.reload();
            })
        }
    });
});
</script>
