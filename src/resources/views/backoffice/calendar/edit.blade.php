@extends('backoffice.layout')

@section('title', 'Editar pedido')

@section('content')
    {{-- Pantalla de edición de pedido; comparte campos con el alta. --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h3 mb-4">Editar pedido</h1>

            <form action="{{ route('calendar.update', $purchaseOrder) }}" method="POST">
                @csrf
                {{-- Laravel recibe PUT aunque el formulario HTML solo pueda enviar POST. --}}
                @method('PUT')
                @include('backoffice.calendar._form')
            </form>
        </div>
    </div>
@endsection
