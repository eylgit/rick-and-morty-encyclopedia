@extends('layouts.app')

@section('title', 'Characters, page ' . $result->page)

@section('content')
    <h1 class="h3 mb-3">Characters</h1>

    @if ($result->isEmpty())
        <div class="alert alert-secondary" role="status">
            No characters found.
            <a href="{{ route('characters.index') }}" class="alert-link">Back to the full list</a>.
        </div>
    @else
        <p class="text-muted">
            Page {{ $result->page }} of {{ $result->pages }} &middot; {{ number_format($result->count) }} characters
        </p>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3 mb-4">
            @foreach ($result->characters as $character)
                <div class="col">
                    <a href="{{ url('/characters/' . $character->id) }}" class="card h-100 text-decoration-none text-body">
                        <img src="{{ $character->image }}" class="card-img-top" alt="{{ $character->name }}" loading="lazy">
                        <div class="card-body">
                            <h2 class="h6 card-title mb-1">{{ $character->name }}</h2>
                            <p class="card-text small text-muted mb-0">
                                {{ $character->status }} &middot; {{ $character->species }}
                            </p>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        {{ $paginator->links() }}
    @endif
@endsection
