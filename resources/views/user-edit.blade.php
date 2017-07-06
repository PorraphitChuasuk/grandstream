@extends('base')

@section('body')
<div class="row justify-content-center">
    <div class="col-sm-6">
        <h1 class="text-center" style="margin:40px;">Editing Sales Person</h1>
        @include('errors')
        <form action="/user/{{ $user->id }}/edit" method="post" style="margin-bottom:20px;">
            {{ csrf_field() }}
            <div class="form-group row">
                <label class="col-sm-4 col-form-label" for="extension_nr">Extension:</label>
                <input class="form-control col-sm-8" type="number" name="extension_nr" value="{{ $user->extension_nr }}" required>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label" for="name">Name:</label>
                <input class="form-control col-sm-8" type="text" name="name" value="{{ $user->name }}" required>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label" for="pipedrive_id">Pipedrive Id:</label>
                <input class="form-control col-sm-8" type="number" name="pipedrive_id" value="{{ $user->pipedrive_id }}" required>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label" for="country_code_id">Pipedrive Country:</label>
                <select class="form-control col-sm-8" name="country_code_id" required>
                    @foreach($countries as $country)
                    <option value="{{ $country->id }}"
                        @if($user->country_code_id == $country->id)
                            selected
                        @endif
                        >
                        {{ \App\country_code::get_country_name($country->id) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-check row">
                <label class="col-sm-4 col-form-label" for="is_enable">Activated:</label>
                <input type="checkbox" name="is_enable" value="1"
                @if($user->is_enable)
                checked
                @endif
                >
            </div>
            <p>
                <em>NOTE: Once activated after being deactivated, all the calls happening during the deactivated period will be pushed.</em>
            </p>
            <button type="sumbit" class="btn btn-info">Submit</button>
        </form>
        <form action="/user/{{ $user->id }}/delete" method="post">
            {{ csrf_field() }}
            <button type="submit" class="btn btn-danger">Delete User</button>
        </form>
    </div>
</div>
@endsection
