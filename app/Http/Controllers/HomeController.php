<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    //
    public function index()
    {
        $featured_post = \App\Models\Blog\Post::published()->featured()->first();
        return view('frontend.home.index', compact('featured_post'));
    }

    public function services()
    {
        $post_list = \App\Models\Blog\Post::published();
        return view('frontend.home.services', ['post_list' => $post_list]);
    }
}
