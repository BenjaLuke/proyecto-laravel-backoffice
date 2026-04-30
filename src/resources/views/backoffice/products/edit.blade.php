@extends('backoffice.layout')

@section('title', 'Editar producto')

@section('content')
    {{-- Edición completa de producto, categorías, tarifas e imágenes. --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h3 mb-4">Editar producto</h1>

            <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data">
                @csrf
                {{-- Simula método PUT para actualizar el producto. --}}
                @method('PUT')
                @include('backoffice.products._form')
            </form>
        </div>
    </div>
@endsection
