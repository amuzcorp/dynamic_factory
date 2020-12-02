<h4>{{ xe_trans($config->get('label')) }}</h4>
<div>
@foreach($items as $item)
    <div class="ovcd_col">
        <div class="ovcd_card">
            <p class="ovcd_card_title">{{ $item->getTitle() }}</p>
            <a href="{{ $item->getCptSlug() }}" class="ovcd_btn">문서로 이동</a>
        </div>
    </div>
@endforeach
</div>

<style>
    .ovcd_col {
        display: inline-flex;
    }
    .ovcd_card {
        width: 100px;
        padding: 10px;
        border-radius: 4px;
        border: 1px solid #aaa;
    }
    .ovcd_card_title {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-weight: bold;
    }
    .ovcd_btn {
        background: #1b6d85;
        border-radius: 2px;
        font-size: 10px;
        padding:5px 7px;
        text-decoration: none;
        color: #fff;
    }
</style>
