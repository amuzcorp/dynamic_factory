@section('page_title')
    <h2>{{xe_trans('dyFac::category_title')}}</h2>
@stop

@section('page_description')
    <small>{{xe_trans('dyFac::category_description')}}</small>
@endsection
<div class="row">
    <div class="col-sm-12">
        <div class="panel-group">
            <div class="panel">
                <div class="panel-heading">
                    <div class="pull-left"><h4>{{xe_trans('dyFac::gen_category_list')}}</h4></div>
                    <div class="pull-right">
                        @if(\Auth::user()->login_id == 'amuzcorp')
                            <a href="{{ route('dyFac.setting.create_taxonomy') }}" class="xe-btn xe-btn-primary">{{xe_trans('dyFac::create_new_category')}}</a>
                        @endif
                    </div>
                </div>
                <div class="panel-body">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>ID</th>
                            <th>{{xe_trans('dyFac::category_name')}}</th>
                            <th>{{xe_trans('dyFac::template')}}</th>
                            <th>{{xe_trans('dyFac::use_types')}}</th>
                            <th>{{xe_trans('xe::management')}}</th>
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
                                    @if(\Auth::user()->login_id == 'amuzcorp')
                                        <a class="xe-btn xe-btn-danger xe-btn-xs cate_del_btn" style="color:#fff;" data-category_id="{{ $category->category_id }}">삭제</a>
                                    @endif
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
