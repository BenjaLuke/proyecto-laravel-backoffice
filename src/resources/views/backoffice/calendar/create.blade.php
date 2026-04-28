@extends('backoffice.layout')

@section('title', 'Nuevo pedido')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h3 mb-4">Nuevo pedido</h1>

            <form action="{{ route('calendar.store') }}" method="POST">
                @csrf
                @php($purchaseOrder = $purchaseOrder ?? null)
                @include('backoffice.calendar._form')
            </form>
        </div>
    </div>
@endsection