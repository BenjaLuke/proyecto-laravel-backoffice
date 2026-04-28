@extends('backoffice.layout')

@section('title', 'Nuevo usuario')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h3 mb-4">Nuevo usuario</h1>

            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                @include('backoffice.users._form')
            </form>
        </div>
    </div>
@endsection