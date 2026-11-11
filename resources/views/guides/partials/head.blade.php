<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
<meta name="robots" content="index,follow,max-image-preview:large">
<link rel="canonical" href="{{ $canonical }}">
<meta property="og:type" content="{{ $ogType ?? 'article' }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ url('/images/og-home.svg') }}">
<meta property="og:locale" content="zh_TW">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ url('/images/og-home.svg') }}">
<meta name="theme-color" content="#0a0b10">
<link rel="icon" type="image/svg+xml" href="{{ url('/images/og-home.svg') }}">
@foreach ($schemas as $schema)
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endforeach
@if ($loadGuideScript ?? true)
    @vite('resources/js/guides.js')
@endif
@include('guides.partials.styles')
