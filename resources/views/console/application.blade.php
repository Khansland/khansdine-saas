@extends('layouts.console')
@section('title', $a->farm_name)
@section('console')
  <h1>{{ $a->farm_name }} <span class="pill {{ $a->status }}">{{ __("saas.a.status.$a->status") }}</span></h1>

  <div class="card">
    <table>
      <tr><th>{{ __('saas.a.owner') }}</th><td>{{ $a->owner_name }}</td></tr>
      <tr><th>{{ __('saas.a.phone') }}</th><td><a href="tel:{{ $a->phone }}">{{ $a->phone }}</a></td></tr>
      <tr><th>{{ __('saas.a.district') }}</th><td>{{ $a->district ?: '-' }}</td></tr>
      <tr><th>{{ __('saas.a.ponds') }}</th><td>{{ $a->pond_count ?? '-' }}</td></tr>
      <tr><th>{{ __('saas.a.species') }}</th><td>{{ $a->species ?: '-' }}</td></tr>
      <tr><th>{{ __('saas.a.bundles') }}</th>
          <td>{{ empty($a->bundles) ? '-' : implode(', ', array_map(fn($b) => __("saas.bundle.$b.title"), $a->bundles)) }}</td></tr>
      <tr><th>{{ __('saas.a.language') }}</th><td>{{ $a->locale }}</td></tr>
      <tr><th>{{ __('saas.a.note') }}</th><td>{{ $a->note ?: '-' }}</td></tr>
      <tr><th>{{ __('saas.a.when') }}</th><td class="muted">{{ $a->created_at->translatedFormat('d M Y H:i') }}</td></tr>
    </table>
  </div>

  <h2>{{ __('saas.a.decide') }}</h2>
  <form method="POST" action="{{ route('console.applications.update', $a->id) }}" class="card">
    @csrf
    <label for="status">{{ __('saas.a.state') }}</label>
    <select id="status" name="status">
      @foreach(\App\Models\Application::STATUSES as $s)
        <option value="{{ $s }}" @selected($a->status === $s)>{{ __("saas.a.status.$s") }}</option>
      @endforeach
    </select>

    <label for="proposed_subdomain">{{ __('saas.a.subdomain') }}</label>
    <input type="text" id="proposed_subdomain" name="proposed_subdomain"
           value="{{ old('proposed_subdomain', $a->proposed_subdomain ?: $suggested) }}">
    <div class="muted">{{ __('saas.a.subdomain_note') }}</div>

    <label for="admin_note">{{ __('saas.a.admin_note') }}</label>
    <textarea id="admin_note" name="admin_note">{{ old('admin_note', $a->admin_note) }}</textarea>

    <p style="margin-top:1rem"><button class="btn">{{ __('saas.a.save') }}</button></p>
    <p class="muted">{{ __('saas.a.approve_note') }}</p>
  </form>

  <h2>{{ __('saas.t.recent') }}</h2>
  <div class="card scroll"><table>
    @forelse($audit as $e)
      <tr><td class="muted">{{ $e->created_at->translatedFormat('d M H:i') }}</td>
          <td>{{ $e->action }}</td><td class="muted">{{ $e->actor }}</td></tr>
    @empty<tr><td class="muted">{{ __('saas.t.no_events') }}</td></tr>@endforelse
  </table></div>
@endsection
