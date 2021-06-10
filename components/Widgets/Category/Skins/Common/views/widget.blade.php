<div class="list-widget">
    <h3 class="article-table-title">
        {{$title}}
    </h3>

    <div>

    </div>

    <ul>
        <li>아이디</li>
        <li>명칭</li>
    </ul>
    <ul>
        @foreach($categoryItems as $categoryItem)
            <li>
                <a href="/{{$instanceUrl}}?{{$parameterKey}}={{$categoryItem->id}}">{{xe_trans($categoryItem->word)}}</a>
            </li>
        @endforeach
    </ul>
</div>
