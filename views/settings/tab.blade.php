@php
$route_1 = 'dyFac.setting.edit';
$route_2 = 'dyFac.setting.edit_editor';
$route_3 = 'dyFac.setting.edit_columns';
$route_4 = 'dyFac.setting.create_extra';
$route_5 = 'dyFac.setting.edit_orders';
$route_6 = 'dyFac.setting.edit_permission';
$cur_route_name = Route::current()->getName();
@endphp
<ul class="nav nav-tabs">
    <li @if($cur_route_name === $route_1) class="active"@endif><a href="{{ route($route_1, ['cpt_id' => $cpt['cpt_id']]) }}">기본정보</a></li>
    <li @if($cur_route_name === $route_2) class="active"@endif><a href="{{ route($route_2, ['cpt_id' => $cpt['cpt_id']]) }}">에디터</a></li>
    <li @if($cur_route_name === $route_6) class="active"@endif><a href="{{ route($route_6, ['cpt_id' => $cpt['cpt_id']]) }}">권한</a></li>
    <li @if($cur_route_name === $route_3) class="active"@endif><a href="{{ route($route_3, ['cpt_id' => $cpt['cpt_id']]) }}">출력순서</a></li>
    <li @if($cur_route_name === $route_5) class="active"@endif><a href="{{ route($route_5, ['cpt_id' => $cpt['cpt_id']]) }}">정렬</a></li>
    <li @if($cur_route_name === $route_4) class="active"@endif><a href="{{ route($route_4, ['cpt_id' => $cpt['cpt_id']]) }}">확장필드</a></li>
    <li class="pull-right"><a href="{{ route('dyFac.setting.index') }}">사용자 정의 문서목록</a></li>
    <li class="pull-right"><a href="{{ route('dyFac.setting.' . $cpt['cpt_id']) }}">{{ $cpt['menu_name'] }}</a></li>
</ul>
