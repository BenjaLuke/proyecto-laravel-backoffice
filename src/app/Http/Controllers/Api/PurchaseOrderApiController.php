<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PurchaseOrderApiController extends Controller
{
    public function index()
    {
        $orders = PurchaseOrder::with('product')
            ->orderBy('order_date')
            ->get();

        return PurchaseOrderResource::collection($orders);
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load('product');

        return new PurchaseOrderResource($purchaseOrder);
    }

    public function store(Request $request)
    {
        $data = $this->validatePurchaseOrder($request);

        $order = DB::transaction(function () use ($data) {
            $status = $data['status'] ?? 'pendiente';

            $product = Product::with('rates')
                ->lockForUpdate()
                ->findOrFail($data['product_id']);

            $rate = $this->resolveRateForDate($product, $data['order_date']);

            $order = PurchaseOrder::create([
                'order_date' => $data['order_date'],
                'product_id' => $product->id,
                'units' => $data['units'],
                'unit_price' => $rate->price,
                'total_price' => bcmul((string) $rate->price, (string) $data['units'], 2),
                'status' => $status,
                'served_at' => $status === 'servido' ? now() : null,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($status === 'servido') {
                $this->decreaseProductStock(
                    $product,
                    (int) $data['units'],
                    'order_served',
                    $order->id
                );
            }

            return $order->load('product');
        });

        return (new PurchaseOrderResource($order))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $data = $this->validatePurchaseOrder($request);

        $purchaseOrder = DB::transaction(function () use ($data, $purchaseOrder) {
            $purchaseOrder = PurchaseOrder::lockForUpdate()->findOrFail($purchaseOrder->id);
            $oldStatus = $purchaseOrder->status;
            $newStatus = $data['status'] ?? $oldStatus ?? 'pendiente';

            if ($oldStatus === 'servido' && $newStatus !== 'servido') {
                throw ValidationException::withMessages([
                    'status' => 'Un pedido servido no puede pasar a pendiente ni a cancelado.',
                ]);
            }

            $productIds = collect([
                $purchaseOrder->product_id,
                $data['product_id'],
            ])->unique()->values()->all();

            $products = Product::with('rates')
                ->whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $oldProduct = $products->get((int) $purchaseOrder->product_id);
            $newProduct = $products->get((int) $data['product_id']);

            if (!$newProduct) {
                throw ValidationException::withMessages([
                    'product_id' => 'El producto seleccionado no existe.',
                ]);
            }

            $rate = $this->resolveRateForDate($newProduct, $data['order_date']);

            $servedStockDataChanged =
                $oldStatus === 'servido' &&
                (
                    (int) $purchaseOrder->product_id !== (int) $data['product_id'] ||
                    (int) $purchaseOrder->units !== (int) $data['units']
                );

            if ($servedStockDataChanged && $oldProduct) {
                $this->increaseProductStock(
                    $oldProduct,
                    (int) $purchaseOrder->units,
                    'adjustment_plus',
                    $purchaseOrder->id
                );
            }

            if ($newStatus === 'servido' && ($oldStatus !== 'servido' || $servedStockDataChanged)) {
                $this->decreaseProductStock(
                    $newProduct,
                    (int) $data['units'],
                    $oldStatus === 'servido' ? 'adjustment_minus' : 'order_served',
                    $purchaseOrder->id
                );
            }

            $purchaseOrder->update([
                'order_date' => $data['order_date'],
                'product_id' => $newProduct->id,
                'units' => $data['units'],
                'unit_price' => $rate->price,
                'total_price' => bcmul((string) $rate->price, (string) $data['units'], 2),
                'status' => $newStatus,
                'served_at' => $newStatus === 'servido'
                    ? ($oldStatus === 'servido' ? ($purchaseOrder->served_at ?? now()) : now())
                    : null,
                'notes' => $data['notes'] ?? null,
            ]);

            return $purchaseOrder->load('product');
        });

        return new PurchaseOrderResource($purchaseOrder);
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status === 'servido') {
            return response()->json([
                'message' => 'No se puede borrar un pedido servido. Debe gestionarse mediante devoluciones.',
            ], 422);
        }

        $purchaseOrder->delete();

        return response()->json([
            'message' => 'Pedido eliminado correctamente.',
        ]);
    }

    private function validatePurchaseOrder(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'order_date' => ['required', 'date'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'units' => ['required', 'integer', 'min:1'],
            'status' => ['sometimes', 'string', Rule::in(array_keys($this->statuses()))],
            'notes' => ['nullable', 'string'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $productId = $request->input('product_id');
            $orderDate = $request->input('order_date');

            if (!$productId || !$orderDate) {
                return;
            }

            $product = Product::with('rates')->find($productId);

            if (!$product) {
                return;
            }

            $rate = $this->resolveRateForDate($product, $orderDate);

            if (!$rate) {
                $validator->errors()->add(
                    'product_id',
                    'El producto no tiene una tarifa vigente para la fecha seleccionada.'
                );
            }
        });

        return $validator->validate();
    }

    private function resolveRateForDate(Product $product, string $orderDate)
    {
        return $product->rates
            ->filter(function ($rate) use ($orderDate) {
                $start = $rate->start_date?->format('Y-m-d');
                $end = $rate->end_date?->format('Y-m-d');

                return $start <= $orderDate && ($end === null || $end >= $orderDate);
            })
            ->sortByDesc('start_date')
            ->first();
    }

    private function statuses(): array
    {
        return [
            'pendiente' => 'Pendiente',
            'servido' => 'Servido',
            'cancelado' => 'Cancelado',
        ];
    }

    private function decreaseProductStock(Product $product, int $units, string $movementType, int $orderId): void
    {
        $stockBefore = (int) $product->current_stock;

        if ($stockBefore < $units) {
            throw ValidationException::withMessages([
                'status' => 'No hay stock suficiente para marcar este pedido como servido.',
            ]);
        }

        $stockAfter = $stockBefore - $units;

        $product->update([
            'current_stock' => $stockAfter,
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'movement_type' => $movementType,
            'quantity' => -$units,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'movement_date' => now(),
            'source_type' => 'purchase_order',
            'source_id' => $orderId,
            'notes' => 'Movimiento de stock por API de pedidos.',
        ]);
    }

    private function increaseProductStock(Product $product, int $units, string $movementType, int $orderId): void
    {
        $stockBefore = (int) $product->current_stock;
        $stockAfter = $stockBefore + $units;

        $product->update([
            'current_stock' => $stockAfter,
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'movement_type' => $movementType,
            'quantity' => $units,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'movement_date' => now(),
            'source_type' => 'purchase_order',
            'source_id' => $orderId,
            'notes' => 'Ajuste de stock por API de pedidos.',
        ]);
    }
}
