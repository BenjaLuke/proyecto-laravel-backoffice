<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\Product;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
            $product = Product::with('rates')->findOrFail($data['product_id']);
            $rate = $this->resolveRateForDate($product, $data['order_date']);

            return PurchaseOrder::create([
                'order_date' => $data['order_date'],
                'product_id' => $product->id,
                'units' => $data['units'],
                'unit_price' => $rate->price,
                'total_price' => bcmul((string) $rate->price, (string) $data['units'], 2),
                'notes' => $data['notes'] ?? null,
            ])->load('product');
        });

        return (new PurchaseOrderResource($order))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $data = $this->validatePurchaseOrder($request);

        $purchaseOrder = DB::transaction(function () use ($data, $purchaseOrder) {
            $product = Product::with('rates')->findOrFail($data['product_id']);
            $rate = $this->resolveRateForDate($product, $data['order_date']);

            $purchaseOrder->update([
                'order_date' => $data['order_date'],
                'product_id' => $product->id,
                'units' => $data['units'],
                'unit_price' => $rate->price,
                'total_price' => bcmul((string) $rate->price, (string) $data['units'], 2),
                'notes' => $data['notes'] ?? null,
            ]);

            return $purchaseOrder->load('product');
        });

        return new PurchaseOrderResource($purchaseOrder);
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
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
}