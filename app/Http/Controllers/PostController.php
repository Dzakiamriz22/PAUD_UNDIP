<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PostController extends Controller
{
    //
    public function list()
    {
        $post_list = \App\Models\Blog\Post::published();
        return view('frontend.post.list', ['post_list' => $post_list]);
    }

    public function show($slug)
    {
        $post = \App\Models\Blog\Post::where('slug', $slug)->firstOrFail();
        return view('frontend.post.show', ['post' => $post]);
    }
}
