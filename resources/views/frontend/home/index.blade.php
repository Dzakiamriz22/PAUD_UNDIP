@extends('frontend.layout')
@section('content')
<section class="text-gray-600 body-font bg-sky-50">
  <div class="bg-white">  
    <div class="relative">
      <div class="mx-auto max-w-7xl">
        <div class="relative z-10 pt-14 lg:w-full lg:max-w-2xl">
          <svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true" class="absolute inset-y-0 right-8 hidden h-full w-80 translate-x-1/2 transform fill-white lg:block">
            <polygon points="0,0 90,0 50,100 0,100" />
          </svg>
          <div class="relative px-6 py-32 sm:py-40 lg:px-8 lg:pr-0">
            <div class="mx-auto max-w-2xl lg:mx-0 lg:max-w-xl">
              <h1 class="text-pretty text-5xl font-semibold tracking-tight text-gray-900 sm:text-7xl">{{ $featured_post->title }}</h1>
              <p class="mt-8 text-pretty text-lg font-medium text-gray-500 sm:text-xl/8">{{ $featured_post->content_overview }}</p>
              <div class="mt-10 flex items-center gap-x-6">
                <a href="{{ route('post.show', ['slug' => $featured_post->slug]) }}" class="rounded-md bg-sky-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-sky-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Selengkapnya</a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="bg-gray-50 lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2">
        <img src="{{ $featured_post->getFeaturedImageUrl('large') }}" alt="" class="aspect-[3/2] object-cover lg:aspect-auto lg:size-full" />
      </div>
    </div>
  </div>
</section>
@endsection