@extends('base')

@section('body')
<div class="row justify-content-center">
    <div class="col-sm-6">
        <h1 class="text-center" style="margin:40px;">Adding New Sales Person</h1>
        @include('errors')
        <form action="/user/add" method="post">
            {{ csrf_field() }}
            <div class="form-group row">
                <label class="col-sm-4 col-form-label" for="extension_nr">Extension:</label>
                <input class="form-control col-sm-8" type="number" name="extension_nr" required>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label" for="name">Name:</label>
                <input class="form-control col-sm-8" type="text" name="name" required>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label" for="pipedrive_id">Pipedrive Id:</label>
                <input class="form-control col-sm-8" type="number" name="pipedrive_id" required>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label" for="country_code_id">Pipedrive Country:</label>
                <select class="form-control col-sm-8" name="country_code_id" required>
                    @foreach($countries as $country)
                    <option value="{{ $country->id }}">{{ \App\country_code::get_country_name($country->id) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="sumbit" class="btn btn-info">Submit</button>
        </form>
    </div>
</div>
@endsection
