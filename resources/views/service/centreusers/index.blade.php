@extends('service.layouts.app')

@section('content')
<div id="container">

    @include('service.includes.sidebar')

    <div id="main-content">
        <h1>Workers</h1>
        @if (Session::get('message'))
        <div class="alert alert-success">
            {{ Session::get('message') }}
        </div>
        @endif
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>
                        Name
                        <span>@include('service.partials.sortableChevron', ['route' =>
                            'admin.centreusers.index', 'orderBy' => '', 'direction' => request('direction') ])</span>
                    </th>
                    <th>
                        Email Address
                        <span>@include('service.partials.sortableChevron', ['route' =>
                            'admin.centreusers.index', 'orderBy' => '', 'direction' => request('direction') ])</span>
                    </th>
                    <th>
                        Home Centre
                        <span>@include('service.partials.sortableChevron', ['route' =>
                            'admin.centreusers.index', 'orderBy' => '', 'direction' => request('direction') ])</span>
                    </th>
                    <th>
                        Alternative Centres
                        <span>@include('service.partials.sortableChevron', ['route' =>
                            'admin.centreusers.index', 'orderBy' => '', 'direction' => request('direction') ])</span>
                    </th>
                    <th>
                        Downloader
                        <span>@include('service.partials.sortableChevron', ['route' =>
                            'admin.centreusers.index', 'orderBy' => '', 'direction' => request('direction') ])</span>
                    </th>
                    <th>Edit</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($workers as $worker)
                <tr>
                    <td>{{ $worker->name }}</td>
                    <td>{{ $worker->email }}</td>
                    <td>{{ $worker->homeCentre->name }}</td>
                    <td>
                        <ul class="table-list">
                            @foreach ($worker->centres as $centre)
                            @if ($centre->id !== $worker->homeCentre->id)
                            <li>{{ $centre->name }}</li>
                            @endif
                            @endforeach
                        </ul>
                    </td>
                    <td>{{ $worker->downloader ? 'Yes' : 'No' }}</td>
                    <td>
                        <a href="{{ route('admin.centreusers.edit', ['id' => $worker->id ]) }}" class="link">
                            <div class="link-button link-button-small">
                                <i class="fa fa-pencil button-icon" aria-hidden="true"></i>Edit
                            </div>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>

@endsection