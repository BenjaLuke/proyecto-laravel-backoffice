@extends('backoffice.layout')

@section('title', 'Editar usuario')

@section('content')
    {{-- Edición de datos y permisos de usuario. --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h3 mb-4">Editar usuario</h1>

            <form action="{{ route('users.update', $user) }}" method="POST">
                @csrf
                {{-- Simula método PUT para actualizar el usuario. --}}
                @method('PUT')
                @include('backoffice.users._form')
            </form>
        </div>
    </div>
@endsection
