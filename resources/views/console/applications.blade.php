@extends('layouts.console')
@section('title', __('saas.console.applications'))
@section('console')
  <h1>{{ __('saas.console.applications') }}</h1>
  <p>
    <a href="{{ route('console.applications') }}" class="{{ $status ? '' : 'on' }}">{{ __('saas.a.all') }}</a>
    @foreach(\App\Models\Application::STATUSES as $s)
      &middot; <a href="{{ route('console.applications', ['status' => $s]) }}">{{ __("saas.a.status.$s") }}
        ({{ $counts[$s] ?? 0 }})</a>
    @endforeach
  </p>
  <div class="card scroll">
    <table>
      <thead><tr>
        <th>{{ __('saas.a.when') }}</th><th>{{ __('saas.a.farm') }}</th><th>{{ __('saas.a.phone') }}</th>
        <th>{{ __('saas.a.district') }}</th><th>{{ __('saas.a.ponds') }}</th><th>{{ __('saas.a.state') }}</th>
      </tr></thead>
      <tbody>
      @forelse($applications as $a)
        <tr>
          <td class="muted">{{ $a->created_at->translatedFormat('d M H:i') }}</td>
          <td><a href="{{ route('console.applications.show', $a->id) }}"><strong>{{ $a->farm_name }}</strong></a>
              <div class="muted">{{ $a->owner_name }}</div></td>
          <td>{{ $a->phone }}</td>
          <td class="muted">{{ $a->district ?: '-' }}</td>
          <td class="muted">{{ $a->pond_count ?? '-' }}</td>
          <td><span class="pill {{ $a->status }}">{{ __("saas.a.status.$a->status") }}</span></td>
        </tr>
      @empty
        <tr><td colspan="6" class="muted">{{ __('saas.a.none') }}</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  {{ $applications->links() }}
@endsection
