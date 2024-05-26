<?php

namespace App\Http\Controllers\api;

use App\Enums\VcardTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\DefaultCategory;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Category::class, 'category');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Category::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request)
    {
        if($request->boolean('credit')){
            $categoryCheck = Category::withTrashed()->where('vcard', $request->input('vcard') )->where('type', VcardTypeEnum::CREDIT)->where('name', $request->input('name'))->first();

            if ($categoryCheck) {
                $categoryCheck->restore();
            }else{
                $category = new Category();
                $category->vcard = $request->input('vcard');
                $category->name = $request->input('name');
                $category->type = VcardTypeEnum::CREDIT;
                $category->save();
            }

        }
        if($request->boolean('debit')){
            $categoryCheck = Category::withTrashed()->where('vcard', $request->input('vcard') )->where('type', VcardTypeEnum::DEBIT)->where('name', $request->input('name'))->first();

            if ($categoryCheck) {
                $categoryCheck->restore();
            }else{
                $category = new Category();
                $category->vcard = $request->input('vcard');
                $category->name = $request->input('name');
                $category->type = VcardTypeEnum::DEBIT;
                $category->save();
            }

        }

        return response()->json([
            'message' => 'Categoria criada com sucesso.',
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        if ($category->type == VcardTypeEnum::CREDIT){
            $categoryCheck = Category::withTrashed()->where('vcard', $request->input('vcard') )->where('type', VcardTypeEnum::CREDIT)->where('name', $request->input('name'))->first();
            if ($categoryCheck) {
                return response()->json([
                    'message' => 'Nome não disponivel.',
                ], 401);
            }
        }else{
            $categoryCheck = Category::withTrashed()->where('vcard', $request->input('vcard') )->where('type', VcardTypeEnum::DEBIT)->where('name', $request->input('name'))->first();
            if ($categoryCheck) {
                return response()->json([
                    'message' => 'Nome não disponivel.',
                ], 401);
            }
        }

        $category->name = $request->input('name');
        $category->save();

        return response()->json([
            'message' => 'Categoria atualizada com sucesso.',
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        if ($category->transactions()->count() > 0) {
            $transactions_with_category = $category->transactions()->get();
            $transactions_with_category->map(function ($transaction) {
                $transaction->category_id = null;
                $transaction->save();
                return $transaction;
            });
            $category->delete();
            return response()->json([
                'message' => 'Categoria apagada com sucesso.',
            ], 200);
        }

        if ($category->transactions()->count() == 0) {
            $category->forceDelete();
            return response()->json([
                'message' => 'Categoria apagada com sucesso.',
            ], 200);
        }

        return response()->json([
            'message' => "Error",
        ], 401);
    }
}
