@section('page_title')
    <h2>새로운 분류 유형 추가하기</h2>
@endsection

<div class="row">
    <div class="col-sm-12">
        <div class="panel-group">
            <form method="post" action="{{-- route('dyFac.setting.store_cpt_tax', ['cpt_id' => $cpt['cpt_id']]) --}}">
                <div class="panel">
                    <div class="panel-body">
                        {!! csrf_field() !!}

                        <span>분류 이름</span>
                        {!! uio('langText', ['name'=>'name']) !!}

                        <span>슬러그</span>
                        <input type="text" class="form-control" name="slug">
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-body">
                        <span>분류 유형</span>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="is_hierarchy" id="exampleRadios1" value="Y" checked>
                            <label class="form-check-label" for="exampleRadios1">계층형</label> - Category
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="is_hierarchy" id="exampleRadios2" value="N">
                            <label class="form-check-label" for="exampleRadios2">단일형</label> - Tag
                        </div>
                    </div>
                </div>
                <br>
                <button type="submit" class="btn btn-primary">생성</button>
                {{--<div class="panel">
                    <ul class="tx-list">
                        @foreach ($taxonomies as $taxonomy)
                            <li>  <a href="#"><i class="icon-arrow xi-angle-right-thin"></i>{{ xe_trans($taxonomy->name) }}</a></li>
                        @endforeach
                    </ul>
                </div>--}}
            </form>
        </div>
    </div>
</div>
