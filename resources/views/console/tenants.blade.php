@extends('layouts.console')
@section('title', __('saas.console.tenants'))
@section('console')
  <h1>{{ __('saas.console.tenants') }}</h1>
  @unless($healthy)
    <div class="err">{{ __('saas.console.registry_down') }}</div>
  @endunless
  <div class="card scroll">
    <table>
      <thead><tr>
        <th>{{ __('saas.t.subdomain') }}</th><th>{{ __('saas.t.database') }}</th>
        <th>{{ __('saas.t.status') }}</th><th>{{ __('saas.t.bundle') }}</th>
        <th>{{ __('saas.t.created') }}</th><th>{{ __('saas.t.last_backup') }}</th><th></th>
      </tr></thead>
      <tbody>
      @forelse($tenants as $t)
        @php $s = $stats[$t->subdomain] ?? null; @endphp
        <tr>
          <td><a href="{{ route('console.tenants.show', $t->subdomain) }}"><strong>{{ $t->subdomain }}</strong></a></td>
          <td class="muted">{{ $t->database_name }}</td>
          <td><span class="pill {{ $t->status }}">{{ $t->status }}</span></td>
          <td class="muted">{{ $t->bundle ?: '-' }}</td>
          <td class="muted">{{ $t->created_at ? \Carbon\Carbon::parse($t->created_at)->translatedFormat('d M Y') : '-' }}</td>
          <td>@include('console.partials.backup', ['stat' => $s])</td>
          <td>
            @foreach(\App\Services\Lifecycle::availableFor($t->status) as $verb)
              <a href="{{ route('console.tenants.command', [$t->subdomain, $verb]) }}"
                 style="font-size:.8rem;margin-right:.4rem">{{ __("saas.verb.$verb") }}</a>
            @endforeach
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="muted">{{ __('saas.console.no_tenants') }}</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="card">
    <strong>{{ __('saas.bk.legend') }}</strong>
    <ul class="muted" style="margin:.4rem 0 0;padding-left:1.1rem">
      <li><span class="bk bk-ok">{{ __('saas.bk.ok') }}</span> — {{ __('saas.bk.legend_ok') }}</li>
      <li><span class="bk bk-stale">{{ __('saas.bk.stale', ['h' => \App\Models\TenantStat::STALE_HOURS]) }}</span> — {{ __('saas.bk.legend_stale', ['h' => \App\Models\TenantStat::STALE_HOURS]) }}</li>
      <li><span class="bk bk-none">{{ __('saas.bk.none') }}</span> — {{ __('saas.bk.legend_none') }}</li>
      <li><span class="bk bk-unknown">{{ __('saas.bk.cannot') }}</span> — {{ __('saas.bk.legend_cannot') }}</li>
      <li><span class="bk bk-unknown">{{ __('saas.bk.never') }}</span> — {{ __('saas.bk.legend_never') }}</li>
    </ul>
  </div>

  {{-- B.5: these two are not tenants and hold everything the console itself
       knows - every application and the whole audit trail. Watched the same way. --}}
  <h2>{{ __('saas.bk.system_title') }}</h2>
  <div class="card scroll">
    <table>
      <thead><tr><th>{{ __('saas.t.database') }}</th><th>{{ __('saas.bk.holds') }}</th>
        <th>{{ __('saas.t.last_backup') }}</th></tr></thead>
      <tbody>
      @forelse($system as $sys)
        <tr>
          <td><strong>{{ $sys->database_name }}</strong></td>
          <td class="muted">{{ __('saas.bk.holds_' . $sys->subdomain) }}</td>
          <td>@include('console.partials.backup', ['stat' => $sys])</td>
        </tr>
      @empty
        <tr><td colspan="3">@include('console.partials.backup', ['stat' => null])</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>

  <p class="muted">{{ __('saas.console.read_only_note') }}</p>
  <p class="muted">{{ __('saas.bk.source', ['when' => $collectedAt ?: __('saas.bk.never_lower')]) }}</p>
@endsection
