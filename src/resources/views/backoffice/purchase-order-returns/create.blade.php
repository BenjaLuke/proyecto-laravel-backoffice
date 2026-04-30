@extends('backoffice.layout')

@section('title', 'Nueva devolución')

@section('content')
    {{-- Alta de devolución sobre un pedido servido. --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h1 class="h3 mb-1">Nueva devolución</h1>
                    <p class="text-muted mb-0">
                        Pedido #{{ $purchaseOrder->id }} · {{ $purchaseOrder->product->name }}
                    </p>
                </div>

                <a href="{{ route('products.history', $purchaseOrder->product_id) }}" class="btn btn-secondary">
                    Volver al historial del producto
                </a>
            </div>

            {{-- Resumen de unidades para evitar devolver más de lo permitido. --}}
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted small">Unidades del pedido</div>
                            <div class="fs-4 fw-bold">{{ $purchaseOrder->units }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted small">Ya devueltas</div>
                            <div class="fs-4 fw-bold">{{ $alreadyReturnedUnits }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted small">Pendientes de devolución</div>
                            <div class="fs-4 fw-bold">{{ $remainingReturnableUnits }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Datos de la devolución y reparto entre stock recuperado y defectuoso. --}}
            <form action="{{ route('purchase-order-returns.store', $purchaseOrder) }}" method="POST">
                @csrf

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="return_date" class="form-label">Fecha de devolución</label>
                        <input
                            type="date"
                            name="return_date"
                            id="return_date"
                            class="form-control"
                            value="{{ old('return_date', now()->format('Y-m-d')) }}"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label for="returned_units" class="form-label">Unidades devueltas</label>
                        <input
                            type="number"
                            name="returned_units"
                            id="returned_units"
                            class="form-control"
                            min="1"
                            max="{{ $remainingReturnableUnits }}"
                            step="1"
                            value="{{ old('returned_units') }}"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label for="restocked_units" class="form-label">Unidades que vuelven a stock</label>
                        <input
                            type="number"
                            name="restocked_units"
                            id="restocked_units"
                            class="form-control"
                            min="0"
                            step="1"
                            value="{{ old('restocked_units', 0) }}"
                            required
                        >
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-4">
                        <label for="defective_units" class="form-label">Unidades defectuosas</label>
                        <input
                            type="number"
                            name="defective_units"
                            id="defective_units"
                            class="form-control"
                            min="0"
                            step="1"
                            value="{{ old('defective_units', 0) }}"
                            required
                        >
                    </div>
                </div>

                <div class="form-text mt-2">
                    La suma de unidades que vuelven a stock y unidades defectuosas debe coincidir con las unidades devueltas.
                </div>

                <div class="mt-3">
                    <label for="notes" class="form-label">Notas</label>
                    <textarea
                        name="notes"
                        id="notes"
                        class="form-control"
                        rows="4"
                    >{{ old('notes') }}</textarea>
                </div>

                {{-- Acciones finales del formulario. --}}
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">Guardar devolución</button>
                    <a href="{{ route('products.history', $purchaseOrder->product_id) }}" class="btn btn-secondary">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
