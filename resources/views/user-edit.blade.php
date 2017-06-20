<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title></title>
    </head>
    <body>
        <form action="/user/{{ $user->id }}/edit" method="post">
            {{ csrf_field() }}
            <table>
                <tbody>
                    <tr>
                        <td><label for="extension">Extension:</label></td>
                        <td><input type="number" name="extension_nr" required value="{{ $user->extension_nr }}"></td>
                    </tr>
                    <tr>
                        <td><label for="name">Name:</label></td>
                        <td><input type="text" name="name" required value="{{ $user->name }}"></td>
                    </tr>
                    <tr>
                        <td><label for="pipedrive">Pipedrive id:</label></td>
                        <td><input type="number" name="pipedrive_id" required value="{{ $user->pipedrive_id }}"></td>
                    </tr>
                </tbody>
            </table>
            <button type="submit">Edit</button>
        </form>
        @if (count($errors) > 0)
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
    </body>
</html>
