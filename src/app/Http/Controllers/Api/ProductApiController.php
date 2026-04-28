<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ProductApiController extends Controller
{
    public function index()
    {
        $products = Product::with(['categories', 'rates', 'images'])
            ->orderBy('name')
            ->get();

        return ProductResource::collection($products);
    }

    public function show(Product $product)
    {
        $product->load(['categories', 'rates', 'images']);

        return new ProductResource($product);
    }

    public function store(Request $request)
    {
        $data = $this->validateProduct($request);

        $product = DB::transaction(function () use ($data) {
            $product = Product::create([
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            $product->categories()->sync($data['categories']);

            foreach ($data['rates'] as $rate) {
                $product->rates()->create([
                    'start_date' => $rate['start_date'],
                    'end_date' => $rate['end_date'] ?: null,
                    'price' => $rate['price'],
                ]);
            }

            return $product->load(['categories', 'rates', 'images']);
        });

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validateProduct($request, $product);

        $product = DB::transaction(function () use ($data, $product) {
            $product->update([
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            $product->categories()->sync($data['categories']);
            $product->rates()->delete();

            foreach ($data['rates'] as $rate) {
                $product->rates()->create([
                    'start_date' => $rate['start_date'],
                    'end_date' => $rate['end_date'] ?: null,
                    'price' => $rate['price'],
                ]);
            }

            return $product->load(['categories', 'rates', 'images']);
        });

        return new ProductResource($product);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'message' => 'Producto eliminado correctamente.',
        ]);
    }

    private function validateProduct(Request $request, ?Product $product = null): array
    {
        $validator = Validator::make($request->all(), [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'code')->ignore($product?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'rates' => ['required', 'array', 'min:1'],
            'rates.*.start_date' => ['required', 'date'],
            'rates.*.end_date' => ['nullable', 'date'],
            'rates.*.price' => ['required', 'numeric', 'min:0'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $rates = $request->input('rates', []);
            $normalized = [];

            foreach ($rates as $index => $rate) {
                $start = $rate['start_date'] ?? null;
                $end = $rate['end_date'] ?? null;
                $price = $rate['price'] ?? null;

                if (!$start || $price === null || $price === '') {
                    continue;
                }

                $startTs = strtotime($start);
                $endTs = $end ? strtotime($end) : strtotime('9999-12-31');

                if ($end && $endTs < $startTs) {
                    $validator->errors()->add("rates.$index.end_date", 'La fecha fin no puede ser anterior a la fecha inicio.');
                    continue;
                }

                $normalized[] = [
                    'original_index' => $index,
                    'start_ts' => $startTs,
                    'end_ts' => $endTs,
                ];
            }

            usort($normalized, fn ($a, $b) => $a['start_ts'] <=> $b['start_ts']);

            for ($i = 1; $i < count($normalized); $i++) {
                $previous = $normalized[$i - 1];
                $current = $normalized[$i];

                if ($current['start_ts'] <= $previous['end_ts']) {
                    $validator->errors()->add(
                        "rates.{$current['original_index']}.start_date",
                        'Esta tarifa se solapa con otra tarifa del mismo producto.'
                    );
                }
            }
        });

        return $validator->validate();
    }
}