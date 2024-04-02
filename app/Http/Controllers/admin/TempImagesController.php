<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TempImage;

class TempImagesController extends Controller
{
    public function create(Request $request){
        $image = $request->image;
        
        if(!empty($image)){
            $ext = $image->getClientOriginalExtension();
            $fileName = time().'.'.$ext;

            $tempImage = new TempImage();
            $tempImage->name = $fileName;
            $tempImage->save();
            
            $image->move(public_path().'/temp/', $fileName);

            return response()->json([
                'status' => true,
                'image_id' => $tempImage->id,
                'message' => 'Image uploaded successfully'
            ]);

        }
    }
}
