{{-- ONE CELL, FIVE THINGS IT CAN SAY, ALL OF THEM VISIBLY DIFFERENT.

     This column exists to shout on the night the backup stops. It could not do
     that before: it printed the same grey "none seen" whether backups were
     running or not, because nothing had ever run the collector. So the states
     are separated here, and every one of them is a colour and a word, never a
     blank and never a dash. --}}
@php($v = $stat?->backup_verdict)
@if(! $stat)
    <span class="bk bk-unknown">{{ __('saas.bk.never') }}</span>
@elseif($v === 'ok')
    <span class="bk bk-ok">{{ __('saas.bk.ok') }}</span>
    <div class="bk-detail">
        {{ $stat->backup_at_local?->translatedFormat('d M H:i') }}
        &middot; {{ $stat->backup_size }}
        &middot; {{ __('saas.bk.age', ['h' => $stat->backup_age_hours]) }}
    </div>
@elseif($v === 'stale')
    <span class="bk bk-stale">{{ __('saas.bk.stale', ['h' => \App\Models\TenantStat::STALE_HOURS]) }}</span>
    <div class="bk-detail">
        {{ $stat->backup_at_local?->translatedFormat('d M H:i') }}
        &middot; {{ $stat->backup_size }}
        &middot; <strong>{{ __('saas.bk.age', ['h' => $stat->backup_age_hours]) }}</strong>
    </div>
@elseif($v === 'none_found')
    <span class="bk bk-none">{{ __('saas.bk.none') }}</span>
    <div class="bk-detail">{{ __('saas.bk.none_why') }}</div>
@else
    <span class="bk bk-unknown">{{ __('saas.bk.cannot') }}</span>
    <div class="bk-detail">{{ $stat->backup_note ?: __('saas.bk.cannot_why') }}</div>
@endif
