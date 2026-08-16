{{-- ONE CELL, FOUR THINGS IT CAN SAY, ALL OF THEM VISIBLY DIFFERENT.

     Same discipline as the backup column, for the same reason: on 14 August
     seven storefronts went down and nothing said so. "No data" must never look
     like "all fine" — that collapse is exactly what hid two missing backups
     until the column was taught to separate the states. --}}
@php($st = $site['state'] ?? null)
@if(! $site)
    <span class="bk bk-unknown">{{ __('saas.up.never') }}</span>
@elseif($st === 'up')
    <span class="bk bk-ok">{{ __('saas.up.up') }}</span>
    <div class="bk-detail">
        HTTP {{ $site['status'] }} &middot; {{ number_format((int) $site['bytes']) }} b
        &middot; {{ $site['ms'] }} ms
        @if(isset($site['origin']) && $site['origin']['state'] !== 'up')
            &middot; <strong>{{ __('saas.up.edge_only') }}</strong>
        @endif
    </div>
@elseif($st === 'down')
    <span class="bk bk-none">{{ __('saas.up.down') }}</span>
    <div class="bk-detail">
        <strong>{{ $site['why'] }}</strong>
        @if(! empty($site['down_since']))
            <br>{{ __('saas.up.down_for', ['d' => \Khansdine\SubdomainShared\Support\SiteVerdict::downFor($site['down_since'])]) }}
        @endif
        @if(isset($site['origin']))
            <br>{{ __('saas.up.origin_says', ['s' => strtoupper(str_replace('_', ' ', $site['origin']['state']))]) }}
            @if($site['origin']['state'] === 'up') — {{ __('saas.up.edge_broken') }} @endif
        @endif
    </div>
@else
    <span class="bk bk-unknown">{{ __('saas.up.cannot') }}</span>
    <div class="bk-detail">{{ $site['why'] ?: __('saas.up.cannot_why') }}</div>
@endif
