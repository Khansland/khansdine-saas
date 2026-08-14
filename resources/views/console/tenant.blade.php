@extends('layouts.console')
@section('title', $tenant->subdomain)
@section('console')
  <h1>{{ $tenant->subdomain }} <span class="pill {{ $tenant->status }}">{{ $tenant->status }}</span></h1>
  <p><a class="btn ghost" href="{{ $siteUrl }}">{{ __('saas.t.open_site') }}</a></p>

  <div class="card">
    <table>
      <tr><th>{{ __('saas.t.database') }}</th><td>{{ $tenant->database_name }}</td></tr>
      <tr><th>{{ __('saas.t.bundle') }}</th><td>{{ $tenant->bundle ?: '-' }}</td></tr>
      <tr><th>{{ __('saas.t.created') }}</th><td>{{ $tenant->created_at ?: '-' }}</td></tr>
      <tr><th>{{ __('saas.t.notes') }}</th><td class="muted">{{ $tenant->notes ?: '-' }}</td></tr>
    </table>
  </div>

  <h2>{{ __('saas.t.holds') }}</h2>
  <div class="card">
    @if($stat)
      <table>
        <tr><th>{{ __('saas.t.tanks') }}</th><td>{{ $stat->tanks ?? '-' }}</td></tr>
        <tr><th>{{ __('saas.t.batches') }}</th><td>{{ $stat->batches ?? '-' }}</td></tr>
        <tr><th>{{ __('saas.t.users') }}</th><td>{{ $stat->users ?? '-' }}</td></tr>
        <tr><th>{{ __('saas.t.db_size') }}</th>
            <td>{{ $stat->db_bytes ? number_format($stat->db_bytes / 1048576, 1) . ' MB' : '-' }}</td></tr>
        <tr><th>{{ __('saas.t.last_backup') }}</th>
            <td>@include('console.partials.backup', ['stat' => $stat])
                @if($stat->backup_file)<div class="muted" style="font-size:.78rem">{{ $stat->backup_file }}</div>@endif
            </td></tr>
      </table>
      @if($stat->error)<div class="err">{{ $stat->error }}</div>@endif
      {{-- Said out loud, because these are not live. The console cannot open a
           tenant database and is not meant to be able to. --}}
      <p class="muted">{{ __('saas.t.as_of', ['when' => $stat->collected_at_local?->translatedFormat('d M H:i') ?: '-']) }}</p>
    @else
      <p class="muted">{{ __('saas.t.no_stats') }}</p>
    @endif
    <p class="muted">{{ __('saas.t.tenant_data_note') }}</p>
  </div>

  <h2>{{ __('saas.t.actions') }}</h2>
  <div class="card">
    @foreach($verbs as $verb)
      <a class="btn ghost" style="margin:.2rem .3rem .2rem 0"
         href="{{ route('console.tenants.command', [$tenant->subdomain, $verb]) }}">{{ __("saas.verb.$verb") }}</a>
    @endforeach
    <p class="muted">{{ __('saas.t.actions_note') }}</p>
  </div>

  <h2>{{ __('saas.t.recent') }}</h2>
  <div class="card scroll">
    <table>
      @forelse($audit as $e)
        <tr><td class="muted">{{ $e->created_at->translatedFormat('d M H:i') }}</td>
            <td>{{ $e->action }}</td><td class="muted">{{ $e->actor }}</td></tr>
      @empty
        <tr><td class="muted">{{ __('saas.t.no_events') }}</td></tr>
      @endforelse
    </table>
  </div>
@endsection
