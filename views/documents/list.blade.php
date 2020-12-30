@section('page_title')
    <h2>{{ $cpt->cpt_name }}</h2>
@stop

@section('page_description')
    <small>{{ $cpt->description }}</small>
@endsection

<div class="row">
    <div class="col-sm-12">

        <!-- admin-tab-info 들어갈 부분 -->

        <div class="panel-group">
            <div class="panel">
                <div class="panel-heading">
                    <div class="pull-left">
                        <h3 class="panel-title">{{ $cpt->cpt_name }} 목록</h3>
                    </div>
                    <div class="pull-right">
                        <a href="{{ route($current_route_name, ['type' => 'create']) }}" class="xe-btn xe-btn-primary" data-toggle="xe-page-modal">{{ sprintf($cpt->labels['new_add_cpt'], $cpt->cpt_name) }}</a>
                    </div>
                </div>
                <div class="panel-body">
                    <table class="table">
                        <thead>
                        <tr>
                            @foreach($config['listColumns'] as $columnName)
                            <th>{{ xe_trans($column_labels[$columnName]) }}</th>
                            @endforeach
                        </tr>
                        </thead>
                        <tbody>
                    @if ($cptDocs->count() > 0)
                        @foreach($cptDocs as $doc)
                        <tr>
                            @foreach($config['listColumns'] as $columnName)
                                @if ($columnName === 'title')
                                <td><a href="{{ route('dyFac.setting.'.$cpt->cpt_id, ['type' => 'edit', 'doc_id' => $doc->id]) }}">{{ $doc->title }}</a></td>
                                @elseif ($columnName === 'writer')
                                <td>
                                    @if ($doc->user !== null)
                                        {{ $doc->user->getDisplayName() }}
                                    @else
                                        Guest
                                    @endif
                                </td>
                                @elseif ($columnName === 'assent_count')
                                <td>{{ $doc->assent_count }}</td>
                                @elseif ($columnName === 'dissent_count')
                                    <td>{{ $doc->dissent_count }}</td>
                                @elseif ($columnName === 'read_count')
                                <td>{{ $doc->read_count }}</td>
                                @elseif ($columnName === 'created_at')
                                <td>{{ $doc->created_at->format('Y-m-d H:i:s') }}</td>
                                @elseif ($columnName === 'updated_at')
                                <td>{{ $doc->updated_at->format('Y-m-d H:i:s') }}</td>
                                @else
                                <td>
                                    @if (($fieldType = XeDynamicField::get('documents_'.$cpt->cpt_id, $columnName)) !== null)
                                        <div class="xe-list-board-list__dynamic-field xe-list-board-list__dynamic-field-{{ $columnName }} xe-list-board-list__mobile-style">
                                            <span class="sr-only">{{ xe_trans($column_labels[$columnName]) }}</span>
                                            {!! $fieldType->getSkin()->output($columnName, $doc->getAttributes()) !!}
                                        </div>
                                    @else
                                        {!! $doc->{$columnName} !!}
                                    @endif
                                </td>
                                @endif
                            @endforeach
                        </tr>
                        @endforeach
                    @endif
                        </tbody>
                    </table>
                </div>
                @if ($cptDocs->count() > 0)
                    <div class="panel-footer">
                        <div class="text-center" style="padding: 24px 0;">
                            <nav>
                                {!! $cptDocs->render() !!}
                            </nav>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
