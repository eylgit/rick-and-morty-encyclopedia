<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Rick and Morty Encyclopedia')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #14181f; --ink-2: #3b4350; --muted: #667085;
            --line: #e4e7ec; --line-strong: #cfd4dc;
            --canvas: #f2f4f7; --surface: #fff; --tint: #f7f8fa;
            --alive: #16a34a; --dead: #dc2626; --unknown: #9aa1ac;
            --bs-body-bg: var(--canvas); --bs-body-color: var(--ink);
            --bs-body-font-family: "IBM Plex Sans", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            --bs-body-font-size: .9375rem; --bs-border-color: var(--line);
            --bs-secondary-color: var(--muted); --bs-code-color: var(--ink);
            --bs-link-color-rgb: 20, 24, 31; --bs-link-hover-color-rgb: 0, 0, 0;
        }
        h1, h2, .h1, .h2, .h3, .h4, .h5, .h6 { font-weight: 600; letter-spacing: -.01em; }

        /* Top bar with a CSS-only portal mark */
        .topbar { background: var(--ink); padding: .5rem 0; }
        .topbar .navbar-brand { display: flex; align-items: center; gap: .55rem; font-size: 1rem; font-weight: 600; }
        .topbar .navbar-brand::before {
            content: ""; flex: none; width: 1.125rem; height: 1.125rem; border-radius: 50%;
            background: radial-gradient(circle, #eaffc4 0 18%, #a6dd5a 42%, #4f9a24 70%, #1f3d0e 100%);
            box-shadow: 0 0 0 2px rgba(151, 206, 76, .18), 0 0 12px rgba(151, 206, 76, .55);
        }

        /* Filter bar: a compact toolbar, sticky from tablet up */
        .filter-bar {
            margin: 0 0 1rem; padding: .25rem .5rem .75rem;
            background: var(--surface); border: 1px solid var(--line); border-radius: .75rem;
            box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
        }
        @media (min-width: 768px) {
            .filter-bar { position: sticky; top: .5rem; z-index: 1020;
                box-shadow: 0 1px 2px rgba(16, 24, 40, .05), 0 10px 24px -14px rgba(16, 24, 40, .3); }
        }
        .filter-bar .form-label, .spec > dt, .section-title {
            font-size: .6875rem; font-weight: 600; letter-spacing: .07em; text-transform: uppercase; color: var(--muted);
        }
        .filter-bar .form-label { margin-bottom: .25rem; }
        .filter-bar .form-control, .filter-bar .form-select, .filter-bar .btn {
            font-size: .875rem; padding-top: .375rem; padding-bottom: .375rem; border-radius: .5rem;
        }
        .form-control, .form-select { border-color: var(--line-strong); }
        .form-control:focus, .form-select:focus { border-color: var(--ink-2); box-shadow: 0 0 0 .2rem rgba(20, 24, 31, .12); }
        .btn-primary {
            --bs-btn-bg: var(--ink); --bs-btn-border-color: var(--ink); --bs-btn-font-weight: 500;
            --bs-btn-hover-bg: #2b323d; --bs-btn-hover-border-color: #2b323d;
            --bs-btn-active-bg: #000; --bs-btn-active-border-color: #000; --bs-btn-focus-shadow-rgb: 20, 24, 31;
        }
        .btn-outline-secondary {
            --bs-btn-color: var(--ink-2); --bs-btn-border-color: var(--line-strong);
            --bs-btn-hover-color: var(--ink); --bs-btn-hover-bg: var(--tint); --bs-btn-hover-border-color: var(--line-strong);
            --bs-btn-active-color: var(--ink); --bs-btn-active-bg: #e9ecf0; --bs-btn-active-border-color: var(--line-strong);
        }

        /* Result count with a hairline rule */
        .result-meta { display: flex; align-items: center; gap: .75rem; margin: 0 0 .75rem;
            font-size: .8125rem; color: var(--muted); font-variant-numeric: tabular-nums; }
        .result-meta::after { content: ""; flex: 1; height: 1px; background: var(--line); }

        /* Compact catalog cards */
        .char-card { overflow: hidden; background: var(--surface); border: 1px solid var(--line); border-radius: .625rem;
            transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease; }
        .char-card:hover { border-color: var(--line-strong); box-shadow: 0 10px 24px -14px rgba(16, 24, 40, .45); transform: translateY(-2px); }
        .char-card:focus-visible { outline: 2px solid var(--ink); outline-offset: 2px; }
        .char-card .card-img-top { aspect-ratio: 1 / 1; object-fit: cover; background: #e6e9ee; border-radius: 0; }
        .char-card .card-body { padding: .5rem .625rem .625rem; }
        .char-card .card-title { font-size: .875rem; line-height: 1.25;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .char-card .card-text { font-size: .75rem; line-height: 1.35; }
        @media (prefers-reduced-motion: reduce) { .char-card { transition: none; } .char-card:hover { transform: none; } }

        /* Status dot: colour is reserved for status */
        .status-dot { display: inline-block; width: .5rem; height: .5rem; margin-right: .375rem; vertical-align: .05em;
            border-radius: 50%; background: var(--unknown); box-shadow: 0 0 0 2px rgba(154, 161, 172, .22); }
        .status-alive { background: var(--alive); box-shadow: 0 0 0 2px rgba(22, 163, 74, .18); }
        .status-dead { background: var(--dead); box-shadow: 0 0 0 2px rgba(220, 38, 38, .16); }

        /* Pagination (Laravel bootstrap-5 markup) */
        .char-grid + nav { border-top: 1px solid var(--line); padding-top: 1rem; }
        .pagination {
            --bs-pagination-padding-x: .7rem; --bs-pagination-padding-y: .3rem; --bs-pagination-font-size: .875rem;
            --bs-pagination-color: var(--ink-2); --bs-pagination-border-color: var(--line); --bs-pagination-border-radius: .5rem;
            --bs-pagination-hover-color: var(--ink); --bs-pagination-hover-bg: var(--tint); --bs-pagination-hover-border-color: var(--line-strong);
            --bs-pagination-focus-color: var(--ink); --bs-pagination-focus-bg: var(--tint); --bs-pagination-focus-box-shadow: 0 0 0 .2rem rgba(20, 24, 31, .15);
            --bs-pagination-active-bg: var(--ink); --bs-pagination-active-border-color: var(--ink);
            --bs-pagination-disabled-color: #a0a7b2; --bs-pagination-disabled-border-color: var(--line);
            margin-bottom: 0; font-variant-numeric: tabular-nums;
        }
        .d-sm-none > .pagination { width: 100%; justify-content: space-between; }
        .d-sm-none > .pagination .page-link { border-radius: .5rem; }

        .empty-state { background: var(--surface); border: 1px dashed var(--line-strong); border-radius: .75rem;
            color: var(--ink-2); text-align: center; padding: 2rem 1rem; }
        .empty-state .alert-link { color: var(--ink); }

        /* Detail page: two-column sheet */
        .crumbs .breadcrumb { --bs-breadcrumb-divider: "›"; font-size: .8125rem; }
        .crumbs .breadcrumb a { color: var(--ink-2); text-decoration: none; }
        .crumbs .breadcrumb a:hover { color: var(--ink); text-decoration: underline; }
        .sheet { background: var(--surface); border: 1px solid var(--line); border-radius: .875rem; box-shadow: 0 1px 2px rgba(16, 24, 40, .04); }
        .sheet-aside { padding: 1.25rem; text-align: center; background: var(--tint);
            border-bottom: 1px solid var(--line); border-radius: .875rem .875rem 0 0; }
        .sheet-aside img { display: block; width: 100%; max-width: 13rem; margin-inline: auto; }
        .sheet-main { padding: 1.25rem; }
        .sheet-main h1 { font-size: 1.625rem; }
        @media (min-width: 768px) {
            .sheet-aside { border-bottom: 0; border-right: 1px solid var(--line); border-radius: .875rem 0 0 .875rem; }
            .sheet-aside img { max-width: 100%; position: sticky; top: 1.25rem; }
            .sheet-main { padding: 1.5rem 1.75rem; }
        }
        .spec { --bs-gutter-x: 0; border-top: 1px solid var(--line); }
        .spec > dt, .spec > dd { margin: 0; padding: .5rem 0; line-height: 1.5rem; border-bottom: 1px solid var(--line); }
        .spec > dd { padding-left: 1rem; }
        .section-title { display: flex; align-items: center; gap: .5rem; margin-bottom: .5rem; }
        .ep-count { background: #eceff3; color: var(--ink-2); font-size: .75rem; font-weight: 500;
            letter-spacing: 0; text-transform: none; border-radius: 999px; padding: .3em .65em; }
        .episodes .list-group-item { background: transparent; border-color: var(--line); padding-top: .5rem; padding-bottom: .5rem;
            font-size: .875rem; align-items: baseline; break-inside: avoid; }
        .episodes > .list-group-item:last-child { border-bottom-width: 1px; }
        .episodes code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .75rem;
            background: #eef1f4; padding: .1rem .375rem; border-radius: .25rem; }
        .episodes .small { font-variant-numeric: tabular-nums; }
        @media (min-width: 1200px) { .episodes { display: block; columns: 2; column-gap: 2rem; } }

        .site-footer { border-top: 1px solid var(--line); padding-top: 1rem; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark topbar mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ route('characters.index') }}">Rick and Morty Encyclopedia</a>
        </div>
    </nav>

    <main class="container pb-5">
        @yield('content')
    </main>

    <footer class="container pb-4 text-muted small site-footer">
        Data from <a href="https://rickandmortyapi.com" class="link-secondary">rickandmortyapi.com</a>.
    </footer>
</body>
</html>
