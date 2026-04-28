<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockEntry;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockEntryController extends Controller
{
    public function index(Request $request): View
    {
        $productId = $request->query('product_id');

        $products = Product::orderBy('name')->get();

        $entriesQuery = StockEntry::with('product')
            ->orderByDesc('entry_date')
            ->orderByDesc('id');

        if (!empty($productId)) {
            $entriesQuery->where('product_id', $productId);
        }

        $entries = $entriesQuery
            ->paginate(15)
            ->withQueryString();

        return view('backoffice.stock-entries.index', compact(
            'entries',
            'products',
            'productId'
        ));
    }

    public function create(Request $request): View
    {
        $products = Product::orderBy('name')->get();
        $selectedProductId = $request->query('product_id');

        return view('backoffice.stock-entries.create', compact(
            'products',
            'selectedProductId'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'entry_date' => ['required', 'date'],
            'units' => ['required', 'integer', 'min:1'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ], [
            'product_id.required' => 'Debes seleccionar un producto.',
            'product_id.exists' => 'El producto seleccionado no existe.',
            'entry_date.required' => 'La fecha de entrada es obligatoria.',
            'units.required' => 'Debes indicar las unidades que entran.',
            'units.integer' => 'Las unidades deben ser un número entero.',
            'units.min' => 'Las unidades deben ser al menos 1.',
            'unit_cost.numeric' => 'El coste unitario debe ser numérico.',
            'unit_cost.min' => 'El coste unitario no puede ser negativo.',
        ]);

        DB::transaction(function () use ($data) {
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);

            $stockBefore = (int) $product->current_stock;
            $stockAfter = $stockBefore + (int) $data['units'];

            $entry = StockEntry::create([
                'product_id' => $product->id,
                'entry_date' => $data['entry_date'],
                'units' => $data['units'],
                'unit_cost' => $data['unit_cost'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $product->update([
                'current_stock' => $stockAfter,
            ]);

            StockMovement::create([
                'product_id' => $product->id,
                'movement_type' => 'entry',
                'quantity' => (int) $data['units'],
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'movement_date' => now(),
                'source_type' => 'stock_entry',
                'source_id' => $entry->id,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return redirect()
            ->route('stock-entries.index')
            ->with('success', 'Entrada de stock registrada correctamente.');
    }
}