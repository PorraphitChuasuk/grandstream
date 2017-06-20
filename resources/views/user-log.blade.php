<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title></title>
    </head>
    <body>
        <a href="/user">Back</a>
        <ul>
            @foreach ($logs as $log)
            <li>{{ $log }}</li>
            @endforeach
        </ul>
    </body>
</html>
