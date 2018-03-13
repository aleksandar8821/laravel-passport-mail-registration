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

        // Nazalost ovo ne radi kad hoces da vratis samo pet prvih slika iz galerije jbg... Vidi https://github.com/laravel/framework/issues/4835 , inace za upit kucas u google laravel Constraining Eager Loads limit not working
        // $galleries = Gallery::with(['user', 'images' => function($query){
        //    $query->offset(0)->limit(5);
        // }])->latest()->get();

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

    public function get_clean_microtimestamp_string() {
        //Get raw microtime (with spaces and dots and digits)
        $mt = microtime();
        
        //Remove all non-digit (or non-integer) characters
        $r = "";
        $length = strlen($mt);
        for($i = 0; $i < $length; $i++) {
            if(ctype_digit($mt[$i])) {
                $r .= $mt[$i];
            }
        }
        
        //Return
        return $r;
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

    public function store_multiple_images(Request $request){
        
        $request->validate([
            'name' => 'required|min:2|unique:galleries',
            'selectedImagesFiles' => 'required',
            'selectedImagesFiles.*' => 'image'
        ]);

        $imageFiles = $request->file('selectedImagesFiles');
        $imagesDescriptions = $request->input('selectedImagesDescriptions');
        $imagesVerticalInfo = $request->input('selectedImagesVerticalInfo');
        $uploadedImagesFolder = 'http://127.0.0.1:8000/uploaded-images/';

        /*foreach($imageFiles as $image){
            $originalName = $image->getClientOriginalName();
            //ekstenzija mi zapravo i ne treba, jer getClientOriginalName ocigledno uzima i ekstenziju, al dobro je da ti ostane tu i ova funkcija da znas da postoji i kako se zove
            $extension = $image->getClientOriginalExtension();
            $generatedName = $this->get_clean_microtimestamp_string().str_random(30).'_'.$originalName;
            $image->move(public_path('uploaded-images/'), $generatedName);
        }*/

               
        $gallery = Gallery::create([
            'name' => $request->input('name'),
            'description' => $request->input('descriptionGallery'),
            'user_id' => \Auth::user()->id
        ]);
        

        // $gallery = new Gallery;
        // $gallery->name = $request->input('name');
        // if($request->input('descriptionGallery') === 'null'){
        //     $gallery->description = null;
        // }else{
        //     $gallery->description = $request->input('descriptionGallery');
        // }
        // $gallery->user_id = \Auth::user()->id;
        // $gallery->save();

        for ($i=0; $i < count($imageFiles); $i++) { 
            $originalName = $imageFiles[$i]->getClientOriginalName();
            //ekstenzija mi zapravo i ne treba, jer getClientOriginalName ocigledno uzima i ekstenziju, al dobro je da ti ostane tu i ova funkcija da znas da postoji i kako se zove
            $extension = $imageFiles[$i]->getClientOriginalExtension();
            $generatedName = $this->get_clean_microtimestamp_string().str_random(30).'_'.$originalName;
            $imageFiles[$i]->move(public_path('uploaded-images/'.$gallery->id.'/'), $generatedName);

         
            $gallery->images()->create([
                'url' => $uploadedImagesFolder.$gallery->id.'/'.$generatedName,
                'description' => $imagesDescriptions[$i],
                'vertical' => intval($imagesVerticalInfo[$i])
            ]);
            

            
        }

        // return $_FILES;
        // $desc = $request->file('selectedImagesFiles');
        // $desc = microtime(true);
        $pera = intval($imagesVerticalInfo[0]);
        return response()->json(compact('pera'));
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
