@extends('backoffice.layout')

@section('title', 'Editar categoría')

@section('content')
    {{-- Edición de categoría existente. --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h3 mb-4">Editar categoría</h1>

            <form action="{{ route('categories.update', $category) }}" method="POST">
                @csrf
                {{-- Simula método PUT para actualizar el recurso. --}}
                @method('PUT')
                @include('backoffice.categories._form')
            </form>
        </div>
    </div>
@endsection
