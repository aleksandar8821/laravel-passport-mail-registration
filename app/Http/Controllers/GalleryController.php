<?php
namespace App\Http\Controllers;

use App\Gallery;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {   
        $galleries = Gallery::with(['user', 'images'])->latest()->get();
        return $galleries;
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
        $path = public_path('uploaded-images/');
        $message = '';

        // Uploadovani file je ipak dostupan u $request promenljivoj, moze mu se pristupiti sa $request->file('selectedImage'), a kao sto vidis mozes uraditi i validaciju:
        $isImage = $request->validate([
            'selectedImage' => 'image'
        ]);

        // Iako sam otkrio da je uploadovani file ipak dostupan u $request promenljivoj (moze mu se pristupiti sa $request->file('selectedImage')), sam upload fajla je zasad ostao u plain PHP formi:
      if (isset($_FILES['selectedImage'])) {
        $originalName = $_FILES['selectedImage']['name'];
        $ext = '.'.pathinfo($originalName, PATHINFO_EXTENSION);
        $generatedName = md5($_FILES['selectedImage']['tmp_name']).$ext;
        $filePath = $path.$generatedName;
        
        if (!is_writable($path)) {
          $message =  json_encode(array(
            'status' => false,
            'msg'    => 'Destination directory not writable.'
          ));
        }

        if (move_uploaded_file($_FILES['selectedImage']['tmp_name'], $filePath)) {
          $message = json_encode(array(
            'status'        => true,
            'originalName'  => $originalName,
            'generatedName' => $generatedName
          ));
        }
      }
      else {
        $message = json_encode(
          array('status' => false, 'msg' => 'No file uploaded.')
        );
      }

        $uploadedImagesFolder = 'http://127.0.0.1:8000/uploaded-images/';

        $gallery = Gallery::create([
            'name' => $request->input('name'),
            'description' => $request->input('descriptionGallery'),
            'user_id' => \Auth::user()->id
        ]);

        $image = $gallery->images()->create([
            'url' => $uploadedImagesFolder.$generatedName,
            'description' => $request->input('descriptionImage')
        ]);

        
        // $error = $_FILES['selectedImage']['error'];
        // $slika = $request->file('selectedImage'); //UPLOADOVANI FAJL JE IPAK DOSTUPAN NA OVAJ NACIN! 
        // return response()->json(compact('slika'));
        return $message;    
    
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Gallery  $gallery
     * @return \Illuminate\Http\Response
     */
    public function show(Gallery $gallery)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Gallery  $gallery
     * @return \Illuminate\Http\Response
     */
    public function edit(Gallery $gallery)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Gallery  $gallery
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Gallery $gallery)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Gallery  $gallery
     * @return \Illuminate\Http\Response
     */
    public function destroy(Gallery $gallery)
    {
        //
    }
}
