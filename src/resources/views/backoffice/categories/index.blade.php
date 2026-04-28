@extends('backoffice.layout')

@section('title', 'Categorías')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Categorías</h1>
        <a href="{{ route('categories.create') }}" class="btn btn-primary">Crear una nueva categoría</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            @if($categories->count())
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Padre</th>
                                <th>Descripción</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categories as $category)
                                <tr>
                                    <td>{{ $category->id }}</td>
                                    <td>{{ $category->code }}</td>
                                    <td>{{ $category->name }}</td>
                                    <td>{{ $category->parent?->name ?? '-' }}</td>
                                    <td>{{ $category->description ?: '-' }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('categories.edit', $category) }}" class="btn btn-sm btn-warning">
                                            Editar
                                        </a>

                                        <form action="{{ route('categories.destroy', $category) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Vas a eliminar la categoría ¿Seguro?')"
                                            >
                                                Borrar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $categories->links() }}
                </div>
            @else
                <p class="mb-0">Todavía no existe ninguna categoría.</p>
            @endif
        </div>
    </div>
@endsection