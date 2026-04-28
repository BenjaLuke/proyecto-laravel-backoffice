<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

use App\Mail\PurchaseOrderCreated;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

use App\Exports\PurchaseOrdersExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): View
    {
        $month = $request->get('month', now()->format('Y-m'));
        $currentMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $isRangeMode = filled($dateFrom) || filled($dateTo);

        if ($isRangeMode) {
            $filterStart = filled($dateFrom)
                ? Carbon::parse($dateFrom)->startOfDay()
                : Carbon::parse($dateTo)->startOfDay();

            $filterEnd = filled($dateTo)
                ? Carbon::parse($dateTo)->endOfDay()
                : Carbon::parse($dateFrom)->endOfDay();

            if ($filterEnd->lt($filterStart)) {
                $swap = $filterStart->copy();
                $filterStart = $filterEnd->copy()->startOfDay();
                $filterEnd = $swap->copy()->endOfDay();
            }

            $calendarStart = $filterStart->copy()->startOfWeek(Carbon::MONDAY);
            $calendarEnd = $filterEnd->copy()->endOfWeek(Carbon::SUNDAY);

            $ordersStart = $filterStart->toDateString();
            $ordersEnd = $filterEnd->toDateString();

            $titleLabel = 'Del ' . $filterStart->format('d/m/Y') . ' al ' . $filterEnd->format('d/m/Y');

            $dateFrom = $filterStart->format('Y-m-d');
            $dateTo = $filterEnd->format('Y-m-d');
        } else {
            $calendarStart = $currentMonth->copy()->startOfWeek(Carbon::MONDAY);
            $calendarEnd = $currentMonth->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

            $ordersStart = $calendarStart->toDateString();
            $ordersEnd = $calendarEnd->toDateString();

            $titleLabel = $currentMonth->translatedFormat('F Y');
        }

        $orders = PurchaseOrder::with('product')
            ->whereBetween('order_date', [$ordersStart, $ordersEnd])
            ->orderBy('order_date')
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($order) => $order->order_date->format('Y-m-d'));

        $days = CarbonPeriod::create($calendarStart, $calendarEnd);

        return view('backoffice.calendar.index', [
            'currentMonth' => $currentMonth,
            'days' => $days,
            'orders' => $orders,
            'titleLabel' => $titleLabel,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'isRangeMode' => $isRangeMode,
        ]);
    }

    public function create(Request $request): View
    {
        $products = Product::orderBy('name')->get();
        $selectedDate = $request->get('date', now()->toDateString());
        $statuses = $this->statuses();

        return view('backoffice.calendar.create', compact('products', 'selectedDate', 'statuses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePurchaseOrder($request);

        $purchaseOrder = DB::transaction(function () use ($data) {
            $product = Product::with('rates')
                ->lockForUpdate()
                ->findOrFail($data['product_id']);

            $rate = $this->resolveRateForDate($product, $data['order_date']);

            $purchaseOrder = PurchaseOrder::create([
                'order_date' => $data['order_date'],
                'product_id' => $product->id,
                'units' => $data['units'],
                'unit_price' => $rate->price,
                'total_price' => bcmul((string) $rate->price, (string) $data['units'], 2),
                'status' => $data['status'],
                'served_at' => $data['status'] === 'servido' ? now() : null,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($data['status'] === 'servido') {
                $this->decreaseProductStock(
                    $product,
                    (int) $data['units'],
                    'order_served',
                    'purchase_order',
                    $purchaseOrder->id,
                    'Descuento de stock por pedido servido.'
                );
            }

            return $purchaseOrder;
        });

        $purchaseOrder->load('product');

        try {
            Mail::to(config('mail.order_notification_email'))->send(
                new PurchaseOrderCreated($purchaseOrder)
            );

            return redirect()
                ->route('calendar.index', ['month' => substr($data['order_date'], 0, 7)])
                ->with('success', 'Pedido guardado correctamente y correo enviado correctamente.');
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('calendar.index', ['month' => substr($data['order_date'], 0, 7)])
                ->with('warning', 'Pedido guardado correctamente, pero no se pudo enviar el correo.');
        }
    }

    public function edit(PurchaseOrder $purchaseOrder): View
    {
        $products = Product::orderBy('name')->get();
        $statuses = $this->statuses();

        return view('backoffice.calendar.edit', compact('purchaseOrder', 'products', 'statuses'));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $data = $this->validatePurchaseOrder($request);

        DB::transaction(function () use ($data, $purchaseOrder) {
            $purchaseOrder = PurchaseOrder::lockForUpdate()->findOrFail($purchaseOrder->id);

            $oldStatus = $purchaseOrder->status;
            $newStatus = $data['status'];

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

            $newProduct = $products->get((int) $data['product_id']);
            $oldProduct = $products->get((int) $purchaseOrder->product_id);

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

            if ($servedStockDataChanged) {
                $this->increaseProductStock(
                    $oldProduct,
                    (int) $purchaseOrder->units,
                    'adjustment_plus',
                    'purchase_order',
                    $purchaseOrder->id,
                    'Reversión de stock por actualización de pedido servido.'
                );
            }

            if ($newStatus === 'servido' && ($oldStatus !== 'servido' || $servedStockDataChanged)) {
                $this->decreaseProductStock(
                    $newProduct,
                    (int) $data['units'],
                    $oldStatus === 'servido' ? 'adjustment_minus' : 'order_served',
                    'purchase_order',
                    $purchaseOrder->id,
                    $oldStatus === 'servido'
                        ? 'Nueva aplicación de stock por actualización de pedido servido.'
                        : 'Descuento de stock por pedido servido.'
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
        });

        return redirect()
            ->route('calendar.index', ['month' => substr($data['order_date'], 0, 7)])
            ->with('success', 'Pedido actualizado correctamente.');
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $month = $purchaseOrder->order_date->format('Y-m');

        if ($purchaseOrder->status === 'servido') {
            return redirect()
                ->route('calendar.index', ['month' => $month])
                ->with('error', 'No se puede borrar un pedido servido. Más adelante se gestionará mediante devoluciones.');
        }

        $purchaseOrder->delete();

        return redirect()
            ->route('calendar.index', ['month' => $month])
            ->with('success', 'Pedido eliminado correctamente.');
    }

    public function exportXls(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $filename = filled($dateFrom) || filled($dateTo)
            ? 'pedidos-rango.xlsx'
            : 'pedidos-' . $month . '.xlsx';

        return Excel::download(
            new PurchaseOrdersExport($month, $dateFrom, $dateTo),
            $filename
        );
    }

    public function exportPdf(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $hasRange = filled($dateFrom) || filled($dateTo);

        if ($hasRange) {
            $startDate = filled($dateFrom)
                ? Carbon::parse($dateFrom)->startOfDay()
                : Carbon::parse($dateTo)->startOfDay();

            $endDate = filled($dateTo)
                ? Carbon::parse($dateTo)->endOfDay()
                : Carbon::parse($dateFrom)->endOfDay();

            if ($endDate->lt($startDate)) {
                $swap = $startDate->copy();
                $startDate = $endDate->copy()->startOfDay();
                $endDate = $swap->copy()->endOfDay();
            }

            $titleLabel = 'Del ' . $startDate->format('d/m/Y') . ' al ' . $endDate->format('d/m/Y');
            $filename = 'pedidos-rango.pdf';
        } else {
            $currentMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $startDate = $currentMonth->copy()->startOfMonth();
            $endDate = $currentMonth->copy()->endOfMonth();
            $titleLabel = $currentMonth->translatedFormat('F Y');
            $filename = 'pedidos-' . $month . '.pdf';
        }

        $orders = PurchaseOrder::with('product')
            ->whereBetween('order_date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->orderBy('order_date')
            ->orderBy('id')
            ->get();

        $totalRevenue = (float) $orders->sum('total_price');
        $totalUnits = (int) $orders->sum('units');

        $pdf = Pdf::loadView('backoffice.calendar.pdf', [
            'orders' => $orders,
            'titleLabel' => $titleLabel,
            'totalRevenue' => $totalRevenue,
            'totalUnits' => $totalUnits,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream($filename);
    }

    private function validatePurchaseOrder(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'order_date' => ['required', 'date'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'units' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'string', Rule::in(array_keys($this->statuses()))],
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

    private function decreaseProductStock(
        Product $product,
        int $units,
        string $movementType,
        string $sourceType,
        ?int $sourceId,
        ?string $notes = null
    ): void {
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
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'notes' => $notes,
        ]);
    }

    private function increaseProductStock(
        Product $product,
        int $units,
        string $movementType,
        string $sourceType,
        ?int $sourceId,
        ?string $notes = null
    ): void {
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
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'notes' => $notes,
        ]);
    }

}
