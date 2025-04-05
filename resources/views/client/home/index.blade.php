@extends('client.layouts.main')

@section('header')
    @include('client.templates.header')
@endsection

@section('content')
    <div class="latest-news">
        <div class="title">
            <h1>{{ $settings['site_name'] }}</h1>
        </div>
        <div class="news-list">
            {{-- Phần này Đức sử lý bằng JS nha --}}
        </div>
    </div>

    <div class="discover-news">
        <div class="title">
            <h2>Khám phá Hải Phòng</h2>
        </div>
        <div class="discover">
            <div class="review">
                {{-- Phần này sử lý trong JS --}}
            </div>
            <!-- Chỗ này để right content -->
            @include('client.templates.featured')
        </div>
    </div>
@endsection