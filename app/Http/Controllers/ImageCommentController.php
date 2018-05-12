<?php

namespace App\Http\Controllers;

use App\ImageComment;
use App\Image;
use App\Gallery;
use Illuminate\Http\Request;

class ImageCommentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'comment_body' => 'required'
        ]);

        $comment = ImageComment::create([
            'comment_body' => $request->comment_body,
            'user_id' => \Auth::user()->id,
            'image_id' => $request->image_id
        ]);

        // $user = $comment->user()->get();
        // return response()->json(compact('comment', 'user')); //ovo se malo komplikovano hendluje na frontendu, pa cu ipak raditi drugacije

        $storedCommentWithUser = ImageComment::with('user')->find($comment->id);
        $galleryID = $comment->image->gallery->id;
        $gallery = Gallery::with(['user', 'images.comments.user', 'comments.user'])->find($galleryID);

        // return $commentWithUser;
        return response()->json(compact('storedCommentWithUser', 'gallery'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\ImageComment  $imageComment
     * @return \Illuminate\Http\Response
     */
    public function show(ImageComment $imageComment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\ImageComment  $imageComment
     * @return \Illuminate\Http\Response
     */
    public function edit(ImageComment $imageComment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\ImageComment  $imageComment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ImageComment $imageComment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\ImageComment  $imageComment
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $commentWithUser = ImageComment::with('user')->find($id);

        // user moze da brise samo svoje komentare
        if($commentWithUser->user_id === \Auth::user()->id){
            $comment = ImageComment::find($id);
            $comment->delete();
            
            $galleryID = $comment->image->gallery->id;
            $gallery = Gallery::with(['user', 'images.comments.user', 'comments.user'])->find($galleryID);
            return $gallery;
        }else{
            return response()->json(["error" => "You are not authorized to delete this comment!"], 401);
        }
    }
}
