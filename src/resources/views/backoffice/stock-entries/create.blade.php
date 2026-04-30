@extends('backoffice.layout')

@section('title', 'Nueva entrada de stock')

@section('content')
    {{-- Alta manual de stock que incrementa unidades disponibles del producto. --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h3 mb-4">Nueva entrada de stock</h1>

            <form action="{{ route('stock-entries.store') }}" method="POST">
                @csrf

                {{-- Producto que recibirá la entrada de stock. --}}
                <div class="mb-3">
                    <label for="product_id" class="form-label">Producto</label>
                    <select name="product_id" id="product_id" class="form-select" required>
                        <option value="">Selecciona un producto</option>
                        @foreach($products as $product)
                            <option
                                value="{{ $product->id }}"
                                @selected((string) old('product_id', $selectedProductId) === (string) $product->id)
                            >
                                {{ $product->name }} ({{ $product->code }}) · Stock actual: {{ $product->current_stock }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Datos cuantitativos y económicos de la entrada. --}}
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="entry_date" class="form-label">Fecha de entrada</label>
                        <input
                            type="date"
                            name="entry_date"
                            id="entry_date"
                            class="form-control"
                            value="{{ old('entry_date', now()->format('Y-m-d')) }}"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label for="units" class="form-label">Unidades</label>
                        <input
                            type="number"
                            name="units"
                            id="units"
                            class="form-control"
                            min="1"
                            step="1"
                            value="{{ old('units') }}"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label for="unit_cost" class="form-label">Coste unitario</label>
                        <input
                            type="number"
                            name="unit_cost"
                            id="unit_cost"
                            class="form-control"
                            min="0"
                            step="0.01"
                            value="{{ old('unit_cost') }}"
                        >
                    </div>
                </div>

                {{-- Notas internas sobre proveedor, lote o motivo de la entrada. --}}
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
                    <button type="submit" class="btn btn-primary">Guardar entrada</button>
                    <a href="{{ route('stock-entries.index') }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
