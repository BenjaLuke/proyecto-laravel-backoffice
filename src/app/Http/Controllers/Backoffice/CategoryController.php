<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::with('parent')
            ->orderBy('name')
            ->paginate(15);

        return view('backoffice.categories.index', compact('categories'));
    }

    public function create(): View
    {
        $parents = Category::orderBy('name')->get();

        return view('backoffice.categories.create', compact('parents'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateCategory($request);

        Category::create($data);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Categoría creada correctamente.');
    }

    public function edit(Category $category): View
    {
        $excludedParentIds = $category->descendantIds();
        $excludedParentIds[] = $category->id;

        $parents = Category::where('id', '!=', $category->id)
            ->whereNotIn('id', $excludedParentIds)
            ->orderBy('name')
            ->get();

        return view('backoffice.categories.edit', compact('category', 'parents'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validateCategory($request, $category);

        $category->update($data);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->children()->exists()) {
            return redirect()
                ->route('categories.index')
                ->with('error', 'No puedes borrar una categoría que tiene categorías hijas.');
        }

        if ($category->products()->exists()) {
            return redirect()
                ->route('categories.index')
                ->with('error', 'No puedes borrar una categoría que tiene productos asociados.');
        }

        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('success', 'Categoría eliminada correctamente.');
    }

    private function validateCategory(Request $request, ?Category $category = null): array
    {
        $rules = [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('categories', 'code')->ignore($category?->id),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],
        ];

        if ($category) {
            $rules['parent_id'][] = Rule::notIn([$category->id]);
        }

        $data = $request->validate($rules);

        if ($category && !empty($data['parent_id'])) {
            // Una categoria no puede moverse debajo de una hija/nieta suya:
            // eso crearia un ciclo y el arbol dejaria de tener sentido.
            $invalidParentIds = $category->descendantIds();

            if (in_array((int) $data['parent_id'], $invalidParentIds, true)) {
                throw ValidationException::withMessages([
                    'parent_id' => 'No puedes seleccionar una categoria descendiente como padre.',
                ]);
            }
        }

        return $data;
    }
}
