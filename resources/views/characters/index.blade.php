@extends('layouts.app')

@section('title', 'Characters, page ' . $result->page)

@section('content')
    <h1 class="h3 mb-3">Characters</h1>

    <form method="get" action="{{ route('characters.index') }}" class="filter-bar row g-2 align-items-end mb-3" role="search">
        <div class="col-12 col-md-3">
            <label for="name" class="form-label">Name</label>
            <input type="search" class="form-control" id="name" name="name" maxlength="100"
                   placeholder="e.g. Rick" value="{{ $filters['name'] ?? '' }}">
        </div>
        <div class="col-6 col-md-2">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">Any</option>
                @foreach (\App\Http\Requests\CharacterListRequest::STATUSES as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label for="gender" class="form-label">Gender</label>
            <select class="form-select" id="gender" name="gender">
                <option value="">Any</option>
                @foreach (\App\Http\Requests\CharacterListRequest::GENDERS as $gender)
                    <option value="{{ $gender }}" @selected(($filters['gender'] ?? '') === $gender)>{{ ucfirst($gender) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-2 col-lg-3">
            <label for="species" class="form-label">Species</label>
            <input type="search" class="form-control" id="species" name="species" maxlength="100"
                   placeholder="e.g. Human" value="{{ $filters['species'] ?? '' }}">
        </div>
        <div class="col-12 col-md-3 col-lg-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill">Search</button>
            @if ($filters !== [])
                <a href="{{ route('characters.index') }}" class="btn btn-outline-secondary">Clear</a>
            @endif
        </div>
    </form>

    @if ($result->isEmpty())
        <div class="alert alert-secondary empty-state" role="status">
            No characters found.
            @if ($filters !== [])
                <a href="{{ route('characters.index') }}" class="alert-link">Clear the search</a>.
            @else
                <a href="{{ route('characters.index') }}" class="alert-link">Back to the full list</a>.
            @endif
        </div>
    @else
        <p class="result-meta">
            Page {{ $result->page }} of {{ $result->pages }} &middot; {{ number_format($result->count) }} characters
        </p>

        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-2 g-md-3 mb-4 char-grid">
            @foreach ($result->characters as $character)
                <div class="col">
                    <a href="{{ route('characters.show', $character->id) }}" class="card char-card h-100 text-decoration-none text-body">
                        <img src="{{ $character->image }}" class="card-img-top" alt="{{ $character->name }}" loading="lazy">
                        <div class="card-body">
                            <h2 class="h6 card-title mb-1">{{ $character->name }}</h2>
                            <p class="card-text small text-muted mb-0">
                                <span class="status-dot status-{{ strtolower($character->status) }}" aria-hidden="true"></span>{{ $character->status }} &middot; {{ $character->species }}
                            </p>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        {{ $paginator->links() }}
    @endif
@endsection
