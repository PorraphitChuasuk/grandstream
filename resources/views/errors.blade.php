@if (count($errors) > 0)
    <div class="row">
        <div class="col">
            @foreach ($errors->all() as $error)
            <div class="alert alert-danger" role="alert" style="margin-bottom:10px;">
              {{ $error }}
            </div>
            @endforeach
        </div>
    </div>
@endif
