@extends('layouts.app')

@section('title', $character->name)

@section('content')
    <nav aria-label="breadcrumb" class="crumbs mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('characters.index') }}">Characters</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $character->name }}</li>
        </ol>
    </nav>

    <div class="sheet row g-0">
        <div class="col-12 col-md-4 col-lg-3 sheet-aside">
            <img src="{{ route('avatars.show', $character->id, absolute: false) }}" class="img-fluid rounded shadow-sm" alt="{{ $character->name }}">
        </div>

        <div class="col-12 col-md-8 col-lg-9 sheet-main">
            <h1 class="h2 mb-3">{{ $character->name }}</h1>

            <dl class="row spec mb-4">
                <dt class="col-5 col-sm-4 col-xl-3">Status</dt>
                <dd class="col-7 col-sm-8 col-xl-9"><span class="status-dot status-{{ strtolower($character->status) }}" aria-hidden="true"></span>{{ $character->status }}</dd>

                <dt class="col-5 col-sm-4 col-xl-3">Species</dt>
                <dd class="col-7 col-sm-8 col-xl-9">{{ $character->species }}@if ($character->type !== '') <span class="text-muted">({{ $character->type }})</span>@endif</dd>

                <dt class="col-5 col-sm-4 col-xl-3">Gender</dt>
                <dd class="col-7 col-sm-8 col-xl-9">{{ $character->gender }}</dd>

                <dt class="col-5 col-sm-4 col-xl-3">Origin</dt>
                <dd class="col-7 col-sm-8 col-xl-9">{{ $character->origin }}</dd>

                <dt class="col-5 col-sm-4 col-xl-3">Last known location</dt>
                <dd class="col-7 col-sm-8 col-xl-9">{{ $character->location }}</dd>
            </dl>

            <h2 class="section-title">
                Episodes
                <span class="badge ep-count">{{ count($episodes) }} {{ count($episodes) === 1 ? 'episode' : 'episodes' }}</span>
            </h2>

            @if ($episodes === [])
                <p class="text-muted">No episodes recorded for this character.</p>
            @else
                <ul class="list-group list-group-flush episodes">
                    @foreach ($episodes as $episode)
                        <li class="list-group-item px-0 d-flex gap-3">
                            <code class="text-nowrap">{{ $episode->code }}</code>
                            <span>{{ $episode->name }}</span>
                            <span class="ms-auto text-muted small text-nowrap">{{ $episode->airDate }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection
