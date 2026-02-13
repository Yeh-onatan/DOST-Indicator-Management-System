<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

{{-- Brand favicon: DOST logo (light/dark aware) --}}
<link rel="icon" type="image/png" href="{{ asset('DOST Logo.png') }}" media="(prefers-color-scheme: light)">
<link rel="icon" type="image/png" href="{{ asset('DOST Logo.png') }}" media="(prefers-color-scheme: dark)">
<link rel="apple-touch-icon" href="/apple-touch-icons.png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
