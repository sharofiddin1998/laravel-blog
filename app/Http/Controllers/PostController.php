<?php

namespace App\Http\Controllers;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Support\Facades\Gate;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
// use App\Http\Post;
use App\Http\Requests\StorePostRequest;
use Illuminate\Support\Facades\Storage;
// use public\Storage;

class PostController extends Controller
{
    public function __construct(){

        $this->middleware('auth')->except(['index','show']);
    }

    public function index()
    {
        $posts = Post::latest()->paginate(8);
        return view("posts.index")->with(
            'posts', $posts
        );
    }

    public function create()
    {
        return view("posts.create")->with([
            'categories' => Category::all(),
            'tags' => Tag::all(),
        ]);
    }

    public function store(StorePostRequest $request)
    {
        if ($request->hasFile('photo')){
            $name = $request->file('photo')->getClientOriginalName();
            $path = $request->file('photo')->storeAs('post-photos', $name);
        }


        $post = Post::create([
            'user_id'=>auth()->user()->id,
            "category_id"=>$request->category_id,
            'title' => $request->title,
            'short_content' => $request->short_content,
            'content' => $request->content,
            'photo' => $path ?? null,
        ]);

        if (isset($request->tags)){
            foreach($request->tags as $tag){
                $post->tags()->attach($tag);
            }
        }

        return redirect()->route('posts.index');

    }

    public function show(Post $post)
    {
        return view('posts.show')->with([
            'post' => $post,
            'recent_posts' => Post::latest()->get()->except($post->id)->take(5),
            'tags' => Tag::all(),
            'categories' => Category::all(),
        ]);

        }


    public function edit(Post $post)
    {
        if (! Gate::allows('update-post', $post)){
            abort(403);
        }

        // Gate::authorize('update-post', $post);
        return view('posts.edit')->with(['post'=>$post]);
    }


    public function update(StorePostRequest $request, Post $post)
    {

        Gate::authorize('update-post', $post);

        if ($request->hasFile('photo')){

            if (isset($post->photo)){
                Storage::delete($post->photo);
            }

            $name = $request->file('photo')->getClientOriginalName();
            $path = $request->file('photo')->storeAs('post-photos', $name);
        }

        $post->update([
            
            'title' => $request->title,
            'short_content' => $request->short_content,
            'content' => $request->content,
            'photo' => $path ?? $post->photo,
        ]);
        return redirect()->route('posts.show', ['post' => $post->id]);

    }

    public function destroy(Post $post)
    {
        if (isset($post->photo)){
            Storage::delete($post->photo);
        }
        $post->delete();
        return redirect()->route('posts.index');
    }
}
