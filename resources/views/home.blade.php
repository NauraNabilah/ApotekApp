@extends('layouts.template')

@section('content')
    <div class="jumbotron py-4 px-5">
    @if (Session::get('canAccess'))
    <div class="alert alert-danger">{{ Session::get('canAccess') }}</div>  
    @endif
        <h1 class="display-4">
            Selamat Datang {{ Auth::user()->role }}!
        </h1>
        <hr class="my-4">
        <p>Aplikasi ini digunakan hanya oleh pegawai administrator APOTEK. Digunakan untuk mengelola data obat, penyetokan,
            juga pembelian (kasir).</p>
    </div>
@endsection

