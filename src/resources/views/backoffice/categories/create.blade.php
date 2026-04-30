@extends('backoffice.layout')

@section('title', 'Nueva categoría')

@section('content')
    {{-- Alta de categoría; el partial se reutiliza también en edición. --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h3 mb-4">Nueva categoría</h1>

            <form action="{{ route('categories.store') }}" method="POST">
                @csrf
                {{-- En creación no existe categoría todavía. --}}
                @php($category = null)
                @include('backoffice.categories._form')
            </form>
        </div>
    </div>
@endsection
