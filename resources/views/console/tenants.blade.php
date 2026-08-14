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
          <td class="muted">{{ $s?->last_backup_at ? $s->last_backup_at->translatedFormat('d M H:i') : __('saas.t.none_seen') }}</td>
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
  <p class="muted">{{ __('saas.console.read_only_note') }}</p>
@endsection
