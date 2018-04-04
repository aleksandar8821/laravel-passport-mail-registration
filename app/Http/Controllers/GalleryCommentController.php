<?php

namespace App\Http\Controllers;

use App\GalleryComment;
use Illuminate\Http\Request;

class GalleryCommentController extends Controller
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

        $comment = GalleryComment::create([
            'comment_body' => $request->comment_body,
            'user_id' => \Auth::user()->id,
            'gallery_id' => $request->gallery_id
        ]);

        // $user = $comment->user()->get();
        // return response()->json(compact('comment', 'user')); //ovo se malo komplikovano hendluje na frontendu, pa cu ipak raditi drugacije

        $commentWithUser = GalleryComment::with('user')->find($comment->id);
        return $commentWithUser;

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\GalleryComment  $galleryComment
     * @return \Illuminate\Http\Response
     */
    public function show(GalleryComment $galleryComment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\GalleryComment  $galleryComment
     * @return \Illuminate\Http\Response
     */
    public function edit(GalleryComment $galleryComment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\GalleryComment  $galleryComment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, GalleryComment $galleryComment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\GalleryComment  $galleryComment
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $commentWithUser = GalleryComment::with('user')->find($id);

        // user moze da brise samo svoje komentare
        if($commentWithUser->user_id === \Auth::user()->id){
            $comment = GalleryComment::find($id);
            $comment->delete();
            // ove responseve sam radio po ovom kako su radili na jwt-u (https://github.com/tymondesigns/jwt-auth/wiki/Creating-Tokens#laravel-5 , http://jwt-auth.readthedocs.io/en/develop/quick-start/#create-the-authcontroller), a i na ovom linku se preporucuje https://stackoverflow.com/questions/41692898/laravel-response-function-only-return-200
            return response()->json(["success" => "success"], 200);
        }else{
            return response()->json(["error" => "You are not authorized to delete this comment!"], 401);
        }
    }
}
