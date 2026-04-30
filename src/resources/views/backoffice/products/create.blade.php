@extends('backoffice.layout')

@section('title', 'Nuevo producto')

@section('content')
    {{-- Alta de producto; también sirve como destino de duplicados. --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h3 mb-4">Nuevo producto</h1>

            <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- Valores por defecto que el partial necesita al crear o duplicar. --}}
                @php($product = $product ?? null)
                @php($selectedCategories = $selectedCategories ?? [])
                @php($rates = $rates ?? [])
                @php($existingImages = $existingImages ?? [])
                @php($sourceImages = $sourceImages ?? [])
                @php($duplicateSourceProductId = $duplicateSourceProductId ?? null)

                @include('backoffice.products._form')
            </form>
        </div>
    </div>
@endsection
