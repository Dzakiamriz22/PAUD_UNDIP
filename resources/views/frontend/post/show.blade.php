@extends('frontend.layout')
@section('content')
<section class="text-gray-600 body-font">
  <div class="container px-5 py-12 mx-auto flex flex-col">
    <div class="lg:w-4/5 mx-auto">
      <div class="rounded-lg h-full overflow-hidden">
        <img alt="content" class="object-cover object-center h-full w-full" src="{{ $post->getFeaturedImageUrl('large') != '' ? $post->getFeaturedImageUrl('large') : "https://dummyimage.com/1280x720"  }}">
      </div>
      <div class="flex flex-col sm:flex-row mt-10">
        <div class="sm:w-full mx-auto sm:border-l-8 border-gray-200 sm:border-t-0 border-t lg:pl-8 sm:py-4 sm:pl-4 mt-4 pt-4 sm:mt-2">
            <h1 class="text-2xl font-medium title-font mb-3">{{ $post->title }}</h1>
            <div class="post-content">{!! $post->content_html !!}</div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection