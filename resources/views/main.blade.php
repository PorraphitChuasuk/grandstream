<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title></title>
    </head>
    <body>
        <form method="post" action="/">
            {{ csrf_field() }}
            <button type="submit">Testing</button>
        </form>
    </body>
</html>
