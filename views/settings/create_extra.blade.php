@section('page_title')
    <h2>{{ $cpt['cpt_name'] }} - 확장 필드</h2>
@endsection
<!-- CSS -->
{{XeFrontend::css('assets/vendor/bootstrap/css/bootstrap.min.css')->load()}}
{{XeFrontend::css('assets/core/settings/css/admin.css')->load()}}
{{XeFrontend::css('plugins/ckeditor/assets/css/editor.css')->load()}}
{{XeFrontend::css('plugins/ckeditor/assets/css/content.css')->load()}}


@include('dynamic_factory::views.settings.tab')
<div class="row">
    <div class="col-sm-12">
        <div class="panel-group">
            <div class="panel">
                <div class="panel-body">
                    <div class="clearfix">
                        {!! $dynamicFieldSection !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 에디터 스크립트 -->
{{ XeFrontend::js([
    'assets/vendor/jqueryui/jquery-ui.min.js',
    'assets/core/editor/editor.bundle.js',
    'assets/vendor/jQuery-File-Upload/js/vendor/jquery.ui.widget.js',
    'assets/vendor/jQuery-File-Upload/js/jquery.iframe-transport.js',
    'assets/vendor/jQuery-File-Upload/js/jquery.fileupload.js',
    'plugins/ckeditor/assets/ckeditor/ckeditor.js',
    'plugins/ckeditor/assets/ckeditor/styles.js',
    'plugins/ckeditor/assets/js/media_library.widget.js',
    'plugins/ckeditor/assets/js/xe.ckeditor.define.js'
])->load() }}
