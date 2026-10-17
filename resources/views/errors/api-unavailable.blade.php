@extends('layouts.app')

@section('title', 'Temporarily unavailable')

@section('content')
    <div class="alert alert-warning" role="alert">
        <h1 class="h4 alert-heading">
            @if ($rateLimited)
                Too many requests to the character database right now.
            @else
                The character database is not responding.
            @endif
        </h1>
        <p class="mb-2">Please try again in a moment.</p>
        <a href="{{ route('characters.index') }}" class="alert-link">Back to the character list</a>
    </div>
@endsection
