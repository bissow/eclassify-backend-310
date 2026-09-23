<!doctype html>
<html lang="en" data-theme="{{ $config->get('ui.theme', 'light') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="color-scheme" content="{{ $config->get('ui.theme', 'light') }}">
    <title>{{ $config->get('ui.title') ?? config('app.name') . ' - API Docs' }}</title>

    <link rel="stylesheet" href="{{ asset('assets/css/stoplight-styles.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/api-docs.css') }}">
</head>
<body style="height: 100vh; overflow-y: auto;">

<elements-api
    id="docs"
    tryItCredentialsPolicy="{{ $config->get('ui.try_it_credentials_policy', 'include') }}"
    router="hash"
    hideTryIt="true"
    @if($config->get('ui.hide_schemas')) hideSchemas="true" @endif
    @if($config->get('ui.logo')) logo="{{ $config->get('ui.logo') }}" @endif
    @if($config->get('ui.layout')) layout="{{ $config->get('ui.layout') }}" @endif
/>

<!-- 2-Column Layout Container -->
<div id="custom-api-cards-container">
    <div class="api-base-url-bar" style="display: flex; align-items: center; gap: 10px;">
        <label for="api-base-url-input">API Base URL</label>
        <input type="text" id="api-base-url-input" spellcheck="false" />
        <button id="api-base-url-reset" type="button">Reset</button>

        <!-- Theme Toggle Button -->
        <button id="theme-toggle" type="button" class="theme-toggle-btn" onclick="toggleTheme()">
            <span id="theme-toggle-icon">🌙</span>
            <span id="theme-toggle-text">Dark Mode</span>
        </button>
    </div>
</div>

<script>
    window.globalSpec = @json($spec);
    window.systemTheme = "{{ $config->get('ui.theme', 'light') }}";
</script>
<script src="{{ asset('assets/js/stoplight-elements.min.js') }}"></script>
<script src="{{ asset('assets/js/api-docs.js') }}"></script>
</body>
</html>