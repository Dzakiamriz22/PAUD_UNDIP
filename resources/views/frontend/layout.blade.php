<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>

        @php
        $generalSettings = app(\App\Settings\GeneralSettings::class);
        $seoSettings = app(\App\Settings\SiteSeoSettings::class);
        $siteSettings = app(\App\Settings\SiteSettings::class);
        $siteSocialSettings = app(\App\Settings\SiteSocialSettings::class);
        $favicon = $generalSettings->site_favicon;
        $brandLogo = $generalSettings->brand_logo;
        $brandName = $siteSettings->name ?? $generalSettings->brand_name ?? config('app.name', 'Super Starter Kit');
        $tagLine = $siteSettings->tagline ?? "";

        $separator = $seoSettings->title_separator ?? '|';
        $page_type = $page_type ?? 'standard';

        $_main_variables = [
            '{site_name}' => $brandName,
            '{separator}' => $separator,
        ];

        switch ($page_type) {
            case 'blog_post':
                $titleFormat = $seoSettings->blog_title_format ?? '{post_title} {separator} {site_name}';
                $variables = array_merge($_main_variables, [
                    '{post_title}' => $postTitle ?? '',
                    '{post_category}' => $postCategory ?? '',
                    '{author_name}' => $authorName ?? '',
                    '{publish_date}' => isset($publishDate) ? $publishDate->format('Y') : '',
                ]);
                break;

            case 'product':
                $titleFormat = $seoSettings->product_title_format ?? '{product_name} {separator} {product_category} {separator} {site_name}';
                $variables = array_merge($_main_variables, [
                    '{product_name}' => $productName ?? '',
                    '{product_category}' => $productCategory ?? '',
                    '{product_brand}' => $productBrand ?? '',
                    '{price}' => $productPrice ?? '',
                ]);
                break;

            case 'category':
                $titleFormat = $seoSettings->category_title_format ?? '{category_name} {separator} {site_name}';
                $variables = array_merge($_main_variables, [
                    '{category_name}' => $categoryName ?? '',
                    '{parent_category}' => $parentCategory ?? '',
                    '{products_count}' => $productsCount ?? '',
                ]);
                break;

            case 'search':
                $titleFormat = $seoSettings->search_title_format ?? 'Search results for "{search_term}" {separator} {site_name}';
                $variables = array_merge($_main_variables, [
                    '{search_term}' => $searchTerm ?? '',
                    '{results_count}' => $resultsCount ?? '',
                ]);
                break;

            case 'author':
                $titleFormat = $seoSettings->author_title_format ?? 'Posts by {author_name} {separator} {site_name}';
                $variables = array_merge($_main_variables, [
                    '{author_name}' => $authorName ?? '',
                    '{post_count}' => $postCount ?? '',
                ]);
                break;

            default:
                $titleFormat = $seoSettings->meta_title_format ?? '{page_title} {separator} {site_name}';
                $variables = array_merge($_main_variables, [
                    '{page_title}' => $pageTitle ?? '',
                ]);
        }

        // Process the format by replacing placeholders
        $title = str_replace(
            array_keys($variables),
            array_values($variables),
            $titleFormat
        );

        // Clean up the title (remove double separators, eliminate leading/trailing separators)
        $title = preg_replace('/\s*' . preg_quote($separator) . '\s*' . preg_quote($separator) . '\s*/', " $separator ", $title);
        $title = trim($title);
        $title = trim($title, " $separator");

        // Fallback if empty
        if (empty(trim($title))) {
            $title = $brandName;
        }
        @endphp
        
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="application-name" content="{{ $brandName }}">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @if (!$generalSettings->search_engine_indexing)
        <meta name="robots" content="noindex, nofollow">
        @endif

        <!-- Canonical URL -->
        <link rel="canonical" href="{{ $seoSettings->canonical_url ?? url()->current() }}" />
        
        <!-- SEO Meta Tags -->
        <meta name="description"
            content="{{ $siteSettings->description ?? '' }}">
        
        <title>{{ $title }} | {{ $tagLine }}</title>

        <!-- Favicon from settings -->
        <link rel="shortcut icon" href="{{ $favicon ? Storage::url($favicon) : 'https://placehold.co/50x50.jpeg?text=Favicon' }}"
            type="image/x-icon">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css'])
    </head>

    @if(isset($siteSettings->is_maintenance) && $siteSettings->is_maintenance)
    <body class="antialiased">
        <div class="maintenance-mode">
            <div class="container">
                <h1>Site Under Maintenance</h1>
                <p>We're currently performing maintenance. Please check back soon.</p>
            </div>
        </div>
    </body>
    @else
    <body class="antialiased flex flex-col min-h-screen">
        @php
        $siteLogo = $siteSettings->logo ? Storage::url($siteSettings->logo) : 'https://placehold.co/240x50.jpeg?text=No%20Image';
        @endphp
        <main class="flex-1">
            <header class="text-gray-50 body-font bg-sky-600">
                <div class="container mx-auto flex flex-wrap p-5 flex-col md:flex-row items-center">
                    <a class="flex title-font font-medium items-center text-gray-900 mb-4 md:mb-0">
                        <img src="{{ $siteLogo }}" alt="Logo" class="h-[60px]">  
                    </a>
                    <nav class="md:ml-auto md:mr-auto flex flex-wrap items-center text-base justify-center">
                    <a class="mr-5 hover:text-amber-400" href="{{ route('home') }}">Home</a>
                    <a class="mr-5 hover:text-amber-400" href="{{ route('post.list') }}">Informasi</a>
                    <a class="mr-5 hover:text-amber-400" href="{{ route('home.services') }}">Layanan</a>
                    <a class="mr-5 hover:text-amber-400" href="{{ url('/post/faq') }}">Pertanyaan</a>
                    </nav>
                    <a href="{{ route('filament.admin.auth.login') }}" 
                        class="inline-flex items-center py-1 px-3 focus:outline-none hover:text-amber-400 rounded text-base text-gray-50 mt-4 md:mt-0 border-b-2 border-gray-50 hover:border-amber-400">
                        Login / Register<x-heroicon-o-arrow-right-start-on-rectangle class="w-4 h-4 fi-icon-btn-icon" />
                    </a>
                </div>
            </header>

            @yield('content')

        </main>

        <footer class="text-gray-600 body-font bg-green-50">
            <div class="bg-gray-100">
                <div class="container px-5 py-6 mx-auto flex items-center sm:flex-row flex-col">
                <div class="text-gray-500">
                    <div class="font-semibold">
                        {{ $siteSettings->company_name ?? "" }}
                    </div>
                    <div class="font-normal">
                        {{ $siteSettings->company_phone ?? "" }}
                    </div>
                    <div class="max-w-[500px] break-words">
                        {{ $siteSettings->company_address ?? "" }}
                    </div>
                </div>

                <span class="inline-flex sm:ml-auto sm:mt-0 mt-4 justify-center sm:justify-start">
                    <a title="Email" href="mailto:{{ $siteSettings->company_email ?? "" }}" class="text-gray-500 hover:text-yellow-600">
                    <svg fill="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="w-5 h-5" viewBox="0 0 24 24">
                        <path fill="currentColor" d="M21 10V4c0-1.1-.9-2-2-2H3c-1.1 0-1.99.9-1.99 2L1 16c0 1.1.9 2 2 2h11v-5c0-1.66 1.34-3 3-3zm-9.47.67c-.32.2-.74.2-1.06 0L3.4 6.25a.85.85 0 1 1 .9-1.44L11 9l6.7-4.19a.85.85 0 1 1 .9 1.44z"/><path fill="currentColor" d="M22 14c-.55 0-1 .45-1 1v3c0 1.1-.9 2-2 2s-2-.9-2-2v-4.5c0-.28.22-.5.5-.5s.5.22.5.5V17c0 .55.45 1 1 1s1-.45 1-1v-3.5a2.5 2.5 0 0 0-5 0V18c0 2.21 1.79 4 4 4s4-1.79 4-4v-3c0-.55-.45-1-1-1"/>
                    </svg>
                    </a>
                    <a title="Facebook" href="{{ $siteSocialSettings->facebook_url ?? "" }}" class="ml-3 text-gray-500 hover:text-yellow-600">
                    <svg fill="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="w-5 h-5" viewBox="0 0 24 24">
                        <path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"></path>
                    </svg>
                    </a>
                    <a title="Twitter" href="{{ $siteSocialSettings->twitter_url ?? "" }}" class="ml-3 text-gray-500 hover:text-yellow-600">
                    <svg fill="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="w-5 h-5" viewBox="0 0 24 24">
                        <path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"></path>
                    </svg>
                    </a>
                    <a title="Instagram" href="{{ $siteSocialSettings->instagram_url ?? "" }}" class="ml-3 text-gray-500 hover:text-yellow-600">
                        <svg fill="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="w-5 h-5" viewBox="0 0 30 30">
                            <path d="M 9.9980469 3 C 6.1390469 3 3 6.1419531 3 10.001953 L 3 20.001953 C 3 23.860953 6.1419531 27 10.001953 27 L 20.001953 27 C 23.860953 27 27 23.858047 27 19.998047 L 27 9.9980469 C 27 6.1390469 23.858047 3 19.998047 3 L 9.9980469 3 z M 22 7 C 22.552 7 23 7.448 23 8 C 23 8.552 22.552 9 22 9 C 21.448 9 21 8.552 21 8 C 21 7.448 21.448 7 22 7 z M 15 9 C 18.309 9 21 11.691 21 15 C 21 18.309 18.309 21 15 21 C 11.691 21 9 18.309 9 15 C 9 11.691 11.691 9 15 9 z M 15 11 A 4 4 0 0 0 11 15 A 4 4 0 0 0 15 19 A 4 4 0 0 0 19 15 A 4 4 0 0 0 15 11 z"></path>
                        </svg>
                    </a>
                    <a title="Linkedin" href="{{ $siteSocialSettings->linkedin_url ?? "" }}" class="ml-3 text-gray-500 hover:text-yellow-600">
                        <svg fill="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="w-5 h-5" viewBox="0 0 50 50">
                            <path d="M41,4H9C6.24,4,4,6.24,4,9v32c0,2.76,2.24,5,5,5h32c2.76,0,5-2.24,5-5V9C46,6.24,43.76,4,41,4z M17,20v19h-6V20H17z M11,14.47c0-1.4,1.2-2.47,3-2.47s2.93,1.07,3,2.47c0,1.4-1.12,2.53-3,2.53C12.2,17,11,15.87,11,14.47z M39,39h-6c0,0,0-9.26,0-10 c0-2-1-4-3.5-4.04h-0.08C27,24.96,26,27.02,26,29c0,0.91,0,10,0,10h-6V20h6v2.56c0,0,1.93-2.56,5.81-2.56 c3.97,0,7.19,2.73,7.19,8.26V39z"></path>
                        </svg>
                    </a>
                </span>
                </div>
            </div>
        </footer>
        @vite(['resources/js/app.js'])
    </body>
    @endif
</html>
