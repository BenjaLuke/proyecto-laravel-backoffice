<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderReturn;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseOrderReturnController extends Controller
{
    public function create(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['product', 'purchaseOrderReturns']);

        if ($purchaseOrder->status !== 'servido') {
            abort(403, 'Solo se pueden registrar devoluciones sobre pedidos servidos.');
        }

        $alreadyReturnedUnits = (int) $purchaseOrder->purchaseOrderReturns->sum('returned_units');
        $remainingReturnableUnits = max(0, (int) $purchaseOrder->units - $alreadyReturnedUnits);

        return view('backoffice.purchase-order-returns.create', compact(
            'purchaseOrder',
            'alreadyReturnedUnits',
            'remainingReturnableUnits'
        ));
    }

    public function store(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $purchaseOrder->load(['product', 'purchaseOrderReturns']);

        if ($purchaseOrder->status !== 'servido') {
            throw ValidationException::withMessages([
                'returned_units' => 'Solo se pueden registrar devoluciones sobre pedidos servidos.',
            ]);
        }

        $data = $request->validate([
            'return_date' => ['required', 'date'],
            'returned_units' => ['required', 'integer', 'min:1'],
            'restocked_units' => ['required', 'integer', 'min:0'],
            'defective_units' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ], [
            'return_date.required' => 'La fecha de devolución es obligatoria.',
            'returned_units.required' => 'Debes indicar las unidades devueltas.',
            'returned_units.integer' => 'Las unidades devueltas deben ser un número entero.',
            'returned_units.min' => 'Las unidades devueltas deben ser al menos 1.',
            'restocked_units.required' => 'Debes indicar las unidades que vuelven a stock.',
            'restocked_units.integer' => 'Las unidades que vuelven a stock deben ser un número entero.',
            'restocked_units.min' => 'Las unidades que vuelven a stock no pueden ser negativas.',
            'defective_units.required' => 'Debes indicar las unidades defectuosas.',
            'defective_units.integer' => 'Las unidades defectuosas deben ser un número entero.',
            'defective_units.min' => 'Las unidades defectuosas no pueden ser negativas.',
        ]);

        DB::transaction(function () use ($data, $purchaseOrder) {
            $purchaseOrder = PurchaseOrder::with(['product', 'purchaseOrderReturns'])
                ->lockForUpdate()
                ->findOrFail($purchaseOrder->id);

            $product = Product::lockForUpdate()->findOrFail($purchaseOrder->product_id);

            $returnedUnits = (int) $data['returned_units'];
            $restockedUnits = (int) $data['restocked_units'];
            $defectiveUnits = (int) $data['defective_units'];

            if (($restockedUnits + $defectiveUnits) !== $returnedUnits) {
                throw ValidationException::withMessages([
                    'restocked_units' => 'La suma de unidades restockeadas y defectuosas debe coincidir con las unidades devueltas.',
                    'defective_units' => 'La suma de unidades restockeadas y defectuosas debe coincidir con las unidades devueltas.',
                ]);
            }

            $alreadyReturnedUnits = (int) $purchaseOrder->purchaseOrderReturns->sum('returned_units');
            $remainingReturnableUnits = (int) $purchaseOrder->units - $alreadyReturnedUnits;

            if ($returnedUnits > $remainingReturnableUnits) {
                throw ValidationException::withMessages([
                    'returned_units' => 'No puedes devolver más unidades de las que quedan pendientes de devolver en este pedido.',
                ]);
            }

            $purchaseOrderReturn = PurchaseOrderReturn::create([
                'purchase_order_id' => $purchaseOrder->id,
                'return_date' => $data['return_date'],
                'returned_units' => $returnedUnits,
                'restocked_units' => $restockedUnits,
                'defective_units' => $defectiveUnits,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($restockedUnits > 0) {
                $stockBefore = (int) $product->current_stock;
                $stockAfter = $stockBefore + $restockedUnits;

                $product->update([
                    'current_stock' => $stockAfter,
                ]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'movement_type' => 'return_restock',
                    'quantity' => $restockedUnits,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'movement_date' => now(),
                    'source_type' => 'purchase_order_return',
                    'source_id' => $purchaseOrderReturn->id,
                    'notes' => $data['notes'] ?? 'Reposición de stock por devolución.',
                ]);
            }
        });

        return redirect()
            ->route('products.history', $purchaseOrder->product_id)
            ->with('success', 'Devolución registrada correctamente.');
    }
}