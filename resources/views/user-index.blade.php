@extends('base')

@section('head')
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.15/css/dataTables.bootstrap4.min.css">
@endsection

@section('body')
<div class="row" style="margin:20px;">
    <div class="col-sm-12 text-center">
        <h1>Grandstream - Pipedrive</h1>
    </div>
</div>
<div class="row" style="margin:20px;">
    <div class="col-sm-4">
        <a class="btn btn-info" href="/user/add">Add New Sales</a>
    </div>
</div>
<table class="table table-bordered table-hover text-center" id="user-table">
    <thead class="thead-inverse">
        <tr>
            <th class="text-center">Extension</th>
            <th class="text-center">Name</th>
            <th class="text-center">Pipedrive Id</th>
            <th class="text-center">Pipedrive Account</th>
            <th class="text-center">Activated</th>
            <th class="text-center">Today's Call</th>
            <th class="text-center">Edit</th>
        </tr>
    </thead>
    <tbody class="table-striped">
        @foreach($users as $user)
        <tr>
            <td>{{ $user->extension_nr }}</td>
            <td>{{ $user->name }}</td>
            <td>{{ $user->pipedrive_id }}</td>
            <td>{{ \App\country_code::get_country_name($user->country_code_id) }}</td>
            @if ($user->is_enable)
            <td>&#10003;</td>
            @else
            <td>&#10060;</td>
            @endif
            @if(isset($user_call_count[$user->extension_nr]))
            <td>{{ $user_call_count[$user->extension_nr] }}</td>
            @else
            <td>0</td>
            @endif
            <td><a class="btn btn-info" href="/user/{{ $user->id }}/edit">Edit</a></td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection

@section('foot')
<script type="text/javascript" src="//code.jquery.com/jquery-1.12.4.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.10.15/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.10.15/js/dataTables.bootstrap4.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $("#user-table").DataTable({
            "paging":false
        });
    });
</script>
@endsection
