<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Comment;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KonnectController extends Controller
{
    public function feed(Request $request)
    {
        $user = $request->user();

        $posts = Post::where('company_id', $user->company_id)
            ->with(['user', 'comments.user', 'likes'])
            ->withCount('likes', 'comments')
            ->orderBy('created_at', 'desc')
            ->get();

        // Add a flag to indicate if the current user liked the post
        $posts->each(function ($post) use ($user) {
            $post->is_liked_by_me = $post->likes->contains('user_id', $user->id);
            // Hide full likes array to save bandwidth if not needed, but we keep it for now.
        });

        return response()->json($posts);
    }

    public function storePost(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'content' => 'required|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('konnect_images', 'public');
        }

        $post = Post::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'content' => $request->input('content'),
            'image_path' => $imagePath,
        ]);

        return response()->json($post->load('user'), 201);
    }

    public function deletePost(Request $request, $id)
    {
        $user = $request->user();
        $post = Post::where('company_id', $user->company_id)->findOrFail($id);

        if ($user->role !== 'admin' && $post->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized to delete this post'], 403);
        }

        $post->delete();
        return response()->json(['message' => 'Post deleted']);
    }

    public function storeComment(Request $request, $postId)
    {
        $user = $request->user();
        $post = Post::where('company_id', $user->company_id)->findOrFail($postId);

        $request->validate([
            'content' => 'required|string|max:500',
        ]);

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => $request->input('content'),
        ]);

        return response()->json($comment->load('user'), 201);
    }

    public function deleteComment(Request $request, $id)
    {
        $user = $request->user();
        $comment = Comment::whereHas('post', function($q) use ($user) {
            $q->where('company_id', $user->company_id);
        })->findOrFail($id);

        if ($user->role !== 'admin' && $comment->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized to delete this comment'], 403);
        }

        $comment->delete();
        return response()->json(['message' => 'Comment deleted']);
    }

    public function toggleLike(Request $request, $postId)
    {
        $user = $request->user();
        $post = Post::where('company_id', $user->company_id)->findOrFail($postId);

        $existingLike = Like::where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingLike) {
            $existingLike->delete();
            return response()->json(['message' => 'Unliked', 'liked' => false]);
        } else {
            Like::create([
                'post_id' => $post->id,
                'user_id' => $user->id,
            ]);
            return response()->json(['message' => 'Liked', 'liked' => true]);
        }
    }
}
