<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title></title>
    </head>
    <body>
        <ul>
            <li><a href="/user/add">Add New Sales Person</a></li>
            <li><a href="/user/log">Today's Log</a></li>
        </ul>
        <table>
            <thead>
                <tr>
                    <td>Extensions</td>
                    <td>Names</td>
                    <td>Pipedrive id</td>
                    <td>Edit</td>
                    <td>Delete</td>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                <tr>
                    <td>{{ $user->extension_nr }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->pipedrive_id }}</td>
                    <td><a href="/user/{{ $user->id }}/edit">Edit</a></td>
                    <td>
                        <form action="/user/{{ $user->id }}/delete" method="post">
                            {{ csrf_field() }}
                            <button type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </body>
</html>
