@extends('layouts.app')

@section('title', 'Not found')

@section('content')
    <div class="alert alert-secondary" role="alert">
        <h1 class="h4 alert-heading">Not found</h1>
        <p class="mb-2">{{ $exception->getMessage() ?: 'There is nothing at this address.' }}</p>
        <a href="{{ route('characters.index') }}" class="alert-link">Back to the character list</a>
    </div>
@endsection
