<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Rick and Morty Encyclopedia')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ route('characters.index') }}">Rick and Morty Encyclopedia</a>
        </div>
    </nav>

    <main class="container pb-5">
        @yield('content')
    </main>

    <footer class="container pb-4 text-muted small">
        Data from <a href="https://rickandmortyapi.com" class="link-secondary">rickandmortyapi.com</a>.
    </footer>
</body>
</html>
