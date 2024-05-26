<?php


namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\DefaultCategoryResource;
use App\Models\Category;
use App\Models\DefaultCategory;
use Illuminate\Http\Request;

class DefaultCategoryController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(DefaultCategory::class, 'App\Models\DefaultCategory');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $vCardDefaultCategories = DefaultCategory::all();
        //return $vCardUsers;
        return response()->json([
            'message' => "Success",
            'defaultCategories' => DefaultCategoryResource::collection($vCardDefaultCategories)
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if($request->input('credit')){
            $categoryCheck = DefaultCategory::where('name', $request->only('name'))->where('type', 'C')->first();

            if ($categoryCheck) {
                return response()->json([
                    'message' => "A categoria já se encontra registada.",
                ], 401);
            }
            $category = new DefaultCategory();
            $category->name = $request->input('name');
            $category->type = 'C';
            $category->save();
        }
        if($request->input('debit')){
            $categoryCheck = DefaultCategory::where('name', $request->only('name'))->where('type', 'D')->first();

            if ($categoryCheck) {
                return response()->json([
                    'message' => "A categoria já se encontra registada.",
                ], 401);
            }
            $category = new DefaultCategory();
            $category->name = $request->input('name');
            $category->type = 'D';
            $category->save();
        }

        return response()->json([
            'message' => 'Categoria criada com sucesso.',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(DefaultCategory $defaultcategory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DefaultCategory $defaultcategory)
    {
        $defaultcategory->name = $request->input('name');

        $defaultcategory->save();

        return response()->json([
            'message' => 'Categoria atualizada com sucesso.',
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DefaultCategory $defaultcategory)
    {
        $defaultcategory->forceDelete();

        return response()->json([
            'message' => 'Categoria apagada com sucesso.',
        ], 200);
    }
}
