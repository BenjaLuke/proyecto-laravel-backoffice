@extends('backoffice.layout')

@section('title', 'Papelera de productos')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Papelera de productos</h1>
            <p class="text-muted mb-0">Aquí puedes restaurar productos borrados.</p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('products.index') }}" class="btn btn-secondary">
                Volver a productos
            </a>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('products.trash') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Buscar</label>
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Nombre, código o descripción"
                            value="{{ $search }}"
                        >
                    </div>

                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary">Buscar</button>
                    </div>

                    <div class="col-md-auto">
                        <a href="{{ route('products.trash') }}" class="btn btn-outline-secondary">Limpiar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            @if($products->count())
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Imagen</th>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Fecha borrado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                                <tr>
                                    <td>{{ $product->id }}</td>

                                    <td>
                                        @if($product->images->count())
                                            <img
                                                src="{{ asset('storage/' . $product->images->first()->path) }}"
                                                alt="{{ $product->name }}"
                                                style="width: 70px; height: 70px; object-fit: cover; border-radius: 8px;"
                                            >
                                        @else
                                            <span class="badge text-bg-warning">Sin imagen</span>
                                        @endif
                                    </td>

                                    <td>{{ $product->code }}</td>
                                    <td>{{ $product->name }}</td>
                                    <td>{{ $product->deleted_at?->format('d/m/Y H:i') }}</td>

                                    <td class="text-end">
                                        <div class="d-flex flex-wrap justify-content-end gap-2">
                                            <a href="{{ route('activity.products.history', $product->id) }}" class="btn btn-sm btn-outline-primary">
                                                Historial archivado
                                            </a>

                                            <form method="POST" action="{{ route('products.restore', $product->id) }}">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-success"
                                                    onclick="return confirm('¿Quieres restaurar este producto?')"
                                                >
                                                    Restaurar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $products->links() }}
                </div>
            @else
                <p class="mb-0">No hay productos en la papelera.</p>
            @endif
        </div>
    </div>
@endsection