@php
    use Overcode\XePlugin\DynamicFactory\Handlers\DynamicFactoryHandler;
@endphp
@section('page_title')
    <h2>사용자 정의 문서 관리</h2>
@endsection
<div class="row">
    <div class="col-sm-12">
        <div class="panel-group">
            <div class="panel">
                <div class="panel-heading">
                    <div class="pull-left"><h4>생성된 사용자 정의 문서</h4></div>
                    <div class="pull-right text-align--right">
                        <div class="search-btn-group">
                            <a href="{{ route('dyFac.setting.create') }}" class="xe-btn xe-btn-primary __xe_make_plugin"><i class="xi-file-text-o"></i> 신규 생성</a>
                            <a href="{{ route('dyFac.setting.create_taxonomy') }}" class="xe-btn"><i class="xi-list-square"></i> 카테고리 생성</a>
                        </div>
                    </div>
                </div>

                <ul class="list-group list-plugin">
                @foreach($cpts as $cpt)
                    <li class="list-group-item">
                        <div class="left-group">
                            <span class="plugin-title">{{ $cpt->cpt_name }}</span>
                            <dl>
                                <dt class="sr-only">ID</dt>
                                <dd title="ID">{{ $cpt->cpt_id }}</dd>
                                <dt class="sr-only">Category</dt>
                                <dd title="Category">
                                @foreach($cpt->categories as $cate)
                                    <a href="{{ route('dyFac.setting.create_taxonomy',[ 'tax_id' => $cate->id]) }}">
                                        <span class="label label-info">{{ xe_trans($cate->name) }}</span>
                                    </a>
                                @endforeach
                                </dd>
                            </dl>
                            <p class="ellipsis">{{ $cpt->description }}</p>
                        </div>

                        <div class="btn-right form-inline">
                            <a href="{{ route('dyFac.setting.edit', ['cpt_id' => $cpt->cpt_id]) }}" class="xe-btn xe-btn-positive-outline"><i class="xi-cog"></i> 설정</a>
                            <a href="#" class="xe-btn xe-btn-danger-outline __xe_remove_plugin remove_cpt_btn" data-url="{{ route('dyFac.setting.destroy', ['cpt_id' => $cpt->cpt_id]) }}"><i class="xi-trash"></i> 삭제</a>
                        </div>
                    </li>
                @endforeach
                @if(count($cpts) === 0)
                <li class="list-group-item off" style="padding:25px 20px;">생성된 문서가 없습니다.</li>
                @endif
                </ul>
            </div>

            <div class="panel">
                <div class="panel-heading">
                    <div class="pull-left"><h4>다른 플러그인에서 생성된 사용자 정의 문서</h4></div>
                </div>
                <ul class="list-group list-plugin">
                @foreach($cpts_fp as $cpt)
                    <li class="list-group-item">
                        <div class="left-group">
                            <span class="plugin-title">{{ $cpt->cpt_name }}</span>
                            <dl>
                                <dt class="sr-only">ID</dt>
                                <dd title="ID">{{ $cpt->cpt_id }}</dd>
                                <dt class="sr-only">Category</dt>
                                <dd title="Category">
                                    @foreach($cpt->categories as $cate)
                                        <a href="{{ route('dyFac.setting.create_taxonomy',[ 'tax_id' => $cate->id]) }}">
                                            <span class="label label-info">{{ xe_trans($cate->name) }}</span>
                                        </a>
                                    @endforeach
                                </dd>
                            </dl>
                            <p class="ellipsis">{{ $cpt->description }}</p>
                        </div>

                        <div class="btn-right form-inline">
                            <a href="{{ route('dyFac.setting.edit', ['cpt_id' => $cpt->cpt_id]) }}" class="xe-btn xe-btn-positive-outline"><i class="xi-cog"></i> 설정</a>
                        </div>
                    </li>
                @endforeach
                @if(count($cpts_fp) === 0)
                    <li class="list-group-item off" style="padding:25px 20px;">생성된 문서가 없습니다.</li>
                @endif
                </ul>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $('.remove_cpt_btn').on('click', function(e) {
            e.preventDefault();

            if(confirm('해당 CPT 에 종속된 모든 게시물, 확장필드도 모두 함께 삭제됩니다.\n계속 하시겠습니까?')){
                XE.ajax({
                    url: $(this).data('url'),
                    type: 'post',
                    success: function (data) {
                        console.log(data);
                        location.reload();
                    },
                    error: function (data) {
                        console.log(data);
                    }
                });
            }
        });
    });
</script>
