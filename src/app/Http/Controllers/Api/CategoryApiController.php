<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryApiController extends Controller
{
    public function index()
    {
        $categories = Category::with('parent')->orderBy('name')->get();

        return CategoryResource::collection($categories);
    }

    public function show(Category $category)
    {
        $category->load('parent');

        return new CategoryResource($category);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:categories,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $category = Category::create($data);

        return (new CategoryResource($category->load('parent')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('categories', 'code')->ignore($category->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id', Rule::notIn([$category->id])],
        ]);

        $category->update($data);

        return new CategoryResource($category->load('parent'));
    }

    public function destroy(Category $category)
    {
        if ($category->children()->exists()) {
            return response()->json([
                'message' => 'No puedes borrar una categoría con hijas.',
            ], 422);
        }

        if ($category->products()->exists()) {
            return response()->json([
                'message' => 'No puedes borrar una categoría con productos asociados.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Categoría eliminada correctamente.',
        ]);
    }
}
