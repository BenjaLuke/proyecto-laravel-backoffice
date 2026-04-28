@extends('backoffice.layout')

@section('title', 'Nueva categoría')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h3 mb-4">Nueva categoría</h1>

            <form action="{{ route('categories.store') }}" method="POST">
                @csrf
                @php($category = null)
                @include('backoffice.categories._form')
            </form>
        </div>
    </div>
@endsection