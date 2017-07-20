@extends('service.layouts.app')
@section('content')

<div id="container">
    @include('service.includes.sidebar')
    <div id="main-content">

        <h1>Add a batch of voucher codes</h1>

        <form role="form" method="POST" action="{{ route('vouchers.storebatch') }}">
            {!! csrf_field() !!}

            <div class="select">
                <label for="sponsor_id" class="required">Sponsor</label><br>
                <select name="sponsor_id" id="sponsor_id" class="{{ $errors->has('sponsor_id') ? 'has-error' : '' }}">
                    <option value="">Please select a sponsor</option>
                    @foreach ($sponsors as $sponsor)
                    <option value="{{ $sponsor->id }}">{{ $sponsor->name }}</option>
                    @endforeach
                </select>
            </div>

            @if ($errors->has('sponsor_id'))
            <p class="error">{{ $errors->first('sponsor_id') }}</p>
            @endif

            <label for="start" class="required">Starting voucher code</label>
            <input type="text" id="start" name="start" class="{{ $errors->has('start') ? 'error' : '' }}" required>

            @if ($errors->has('start'))
            <p class="error">{!! $errors->first('start') !!}</p>
            @endif

            <label for="end">Ending voucher code</label>
            <input type="text" id="end" name="end" class="{{ $errors->has('end') ? 'error' : '' }}">

            @if ($errors->has('end'))
            <p class="error">{{ $errors->first('end') }}</p>
            @endif

            <button type="submit">Create vouchers</button>

        </form>

    </div>
</div>
@endsection
