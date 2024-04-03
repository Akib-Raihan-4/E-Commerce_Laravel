<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Category;
use App\Models\TempImage;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class CategoryController extends Controller
{
    public function index(Request $request){
        $categories = Category::latest();
        
        if(!empty($request->get('keyword'))){
            $categories = $categories->where('name','like', '%'.$request->get('keyword').'%');
        }
        $categories = $categories->paginate(10); //Category::orderBy('created_at', 'DESC')->paginate(10);
        // $data['categories'] = $categories;
        return view('admin.category.list', compact('categories')); // return view('admin.category.list', $data);
    }

    public function create(){
        return view('admin.category.create');
    }


    public function store(Request $request){
        // To check if the input field are given
        $validator = Validator::make($request->all(),[
            'name' => 'required',
            'slug' => 'required|unique:categories',

        ]);

        if ($validator->passes()){
            // storing on DB 
            $category = new Category();
            $category->name = $request->name;
            $category->slug = $request->slug;
            $category->status = $request->status;
            $category->save();


            // Save Image
            if(!empty($request->image_id)){
                $tempImage = TempImage::find($request->image_id);
                $extArray = explode('.',$tempImage->name);
                $ext = last($extArray);

                $newImageName = $category->id.'.'.$ext;
                // echo $newImageName;

                $sPath = public_path().'/temp/'.$tempImage->name;
                $dPath = public_path().'/uploads/category/'.$newImageName;

                File::copy($sPath,$dPath);

                // // Generate Image Thumbnail
                $dPath = public_path().'/uploads/category/thumb/'.$newImageName;


                // resize image using image intervention
                $manager = new ImageManager(new Driver());
                $image = $manager->read($sPath);
                $image = $image->resize(300,300);
                $image->toPng()->save($dPath);


                $category->image = $newImageName;
                $category->save();

            }
            

            session()->flash('success', 'Category added successfully');

            return response()->json([
                'status' => true,
                'message' => 'Category added successfully'
            ]);

        }else{
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function edit($categoryID, Request $request){
        $category = Category::find($categoryID);
        if(empty($category)){
            return redirect()->route('categories.index');
        }
        return view('admin.category.edit', compact( 'category' ));
    }

    public function update($categoryID, Request $request){

        $category = Category::find($categoryID);
        if(empty($category)){
            return response()->json([
                'status' => false,
                'notFound' => true,
                'message'=> 'Category not found',

            ]);
        }
       
        // To check if the input field are given
        $validator = Validator::make($request->all(),[
            'name' => 'required',
            'slug' => 'required|unique:categories,slug,'.$category->id.',id',

        ]);

        if ($validator->passes()){
            // storing on DB 
            $category->name = $request->name;
            $category->slug = $request->slug;
            $category->status = $request->status;
            $category->save();

            $oldImage = $category->image;

            // Save Image
            if(!empty($request->image_id)){
                $tempImage = TempImage::find($request->image_id);
                $extArray = explode('.',$tempImage->name);
                $ext = last($extArray);

                $newImageName = $category->id.'-'.time().'.'.$ext;
                // echo $newImageName;

                $sPath = public_path().'/temp/'.$tempImage->name;
                $dPath = public_path().'/uploads/category/'.$newImageName;

                File::copy($sPath,$dPath);

                // // Generate Image Thumbnail
                $dPath = public_path().'/uploads/category/thumb/'.$newImageName;


                // resize image using image intervention
                $manager = new ImageManager(new Driver());
                $image = $manager->read($sPath);
                // $image = $image->resize(300,300);
                $image = $image->resize(450, 600, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $image->toPng()->save($dPath);

                


                $category->image = $newImageName;
                $category->save();

                // Delete old image
                File::delete(public_path().'/uploads/category/thumb/'.$oldImage);
                File::delete(public_path().'/uploads/category/'.$oldImage);

            }
            

            session()->flash('success', 'Category updated successfully');

            return response()->json([
                'status' => true,
                'message' => 'Category updated successfully'
            ]);

        }else{
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }

    }
    

    public function destroy($categoryID, Request $request){
        $category = Category::find($categoryID);

        if(empty($category)){
            return redirect()->route('categories.index');
        }

        File::delete(public_path().'/uploads/category/'.$category->image);
        File::delete(public_path().'/uploads/category/thumb/'.$category->image);

        $category->delete();

        session()->flash('success', 'Category deleted successfully');

        return response()->json([
                'status' => true,
                'message'=> 'Category deleted successfully'
        ]);
    }
}
