<?php

namespace App\Http\Controllers;

use App\Models\Feed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FeedController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $feeds = Feed::with(['user','comments.user','comments.replies.user','likes'])
            ->where(function($q) {
                $q->where('visibility', 'public')
                ->orWhere(function($q2) {
                    $q2->where('visibility', 'private')
                        ->where('user_id', Auth::id());
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($feeds);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'image' => 'nullable|image',
            'visibility' => 'required|in:public,private',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $imagePath = null;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imagePath = time() . '.' . $extension;
            $path = $image->move(public_path('uploads/post_images'), $imagePath);
        }

        $feed = Feed::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'image' => $imagePath,
            'visibility' => $request->visibility,
        ]);

        return response()->json([
            'id' => $feed->id,
            'title' => $feed->title,
            'image' => $feed->image ,
            'visibility' => $feed->visibility,
        ], 201);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $feed = Feed::find($id);
        return response()->json([
            'id' => $feed->id,
            'title' => $feed->title,
            'image' => $feed->image,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $feed = Feed::findOrFail($id);

        if ($request->has('title')) {
            $feed->title = $request->title;
        }

        if ($request->hasFile('image')) {
            $existingImage = $feed->image;
            if ($existingImage) {
                $oldImagePath = public_path('uploads/post_images/' . $existingImage);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $newImageName = time() . '.' . $extension;

            $image->move(public_path('uploads/post_images'), $newImageName);
            $feed->image = $newImageName;
        }

        $feed->save();

        return response()->json([
            'id' => $feed->id,
            'title' => $feed->title,
            'image' => $feed->image
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $feed = Feed::find($id);

        if (!$feed) {
            return response()->json(['message' => 'Feed not found'], 401);
        }

        $existingImage = $feed->image;

        if ($existingImage) {
            $imagePath = public_path('uploads/post_images/' . $existingImage);
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $feed->delete();

        return response()->json(['message' => 'Feed deleted successfully']);
    }

    //this is for comment section
    public function comment(Request $request, $feedId)
{
    $validator = Validator::make($request->all(), [
        'comment' => 'required|string',
        'parent_id' => 'nullable|exists:comments,id'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()
        ], 422);
    }

    $feed = Feed::findOrFail($feedId);

    // Create comment or reply
    $comment = $feed->comments()->create([
        'user_id' => Auth::id(),
        'comment' => $request->comment,
        'parent_id' => $request->parent_id ?? null
    ]);

    // Load user info for frontend
    $comment->load('user');

    return response()->json($comment, 201);
}



    //this is for like and unlike section
    public function like(Request $request, $feedId)
    {
        $feed = Feed::findOrFail($feedId);

        $existing = $feed->likes()->where('user_id', Auth::id())->first();
        if ($existing) {
            return response()->json(['message' => 'Already liked'], 400);
        }

        $like = $feed->likes()->create(['user_id' => Auth::id()]);
        return response()->json($like);
    }


    public function unlike(Request $request, $feedId)
    {
        $feed = Feed::findOrFail($feedId);

        $like = $feed->likes()->where('user_id', Auth::id())->first();
        if (!$like) {
            return response()->json(['message' => 'Not liked yet'], 400);
        }

        $like->delete();
        return response()->json(['message' => 'Unliked']);
    }


}
