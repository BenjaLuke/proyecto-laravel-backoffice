@extends('backoffice.layout')

@section('title', 'Entradas de stock')

@section('content')
    {{-- Cabecera del registro de entradas de stock. --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Entradas de stock</h1>
            <p class="text-muted mb-0">Registro de mercancía entrada en almacén.</p>
        </div>

        @if(auth()->user()->hasPermission('products_manage'))
            <a href="{{ route('stock-entries.create') }}" class="btn btn-primary">
                Nueva entrada
            </a>
        @endif
    </div>

    {{-- Filtro opcional para revisar entradas de un producto concreto. --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('stock-entries.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Producto</label>
                        <select name="product_id" class="form-select">
                            <option value="">Todos</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" @selected((string) $productId === (string) $product->id)>
                                    {{ $product->name }} ({{ $product->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                    </div>

                    <div class="col-md-auto">
                        <a href="{{ route('stock-entries.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabla paginada de entradas registradas. --}}
    <div class="card shadow-sm">
        <div class="card-body">
            @if($entries->count())
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Producto</th>
                                <th>Unidades</th>
                                <th>Coste unitario</th>
                                <th>Notas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entries as $entry)
                                <tr>
                                    <td>{{ $entry->id }}</td>
                                    <td>{{ $entry->entry_date->format('d/m/Y') }}</td>
                                    <td>
                                        {{ $entry->product?->name ?? '-' }}
                                        @if($entry->product)
                                            <div class="text-muted small">{{ $entry->product->code }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $entry->units }}</td>
                                    <td>
                                        @if($entry->unit_cost !== null)
                                            {{ number_format((float) $entry->unit_cost, 2, ',', '.') }} €
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $entry->notes ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $entries->links() }}
                </div>
            @else
                <p class="mb-0">Todavía no hay entradas de stock registradas.</p>
            @endif
        </div>
    </div>
@endsection
