<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class extensionController extends Controller
{
    public function index(Request $request) {
        $users = \App\extension::all();
        return view('user-index', compact('users'));
    }


    public function create(Request $request) {
        return view('user-add');
    }


    public function store(Request $request) {
        $this->validate($request, [
            'extension_nr' => 'required|unique:extensions',
            'name' => 'required',
            'pipedrive_id' => 'required|unique:extensions'
        ]);

        $extension = $request->input('extension_nr');
        $name = $request->input('name');
        $pipedrive = $request->input('pipedrive_id');

        \App\extension::firstOrCreate(['extension_nr' => $extension], ['name' => $name, 'pipedrive_id' => $pipedrive]);

        return redirect('/user');
    }


    public function edit_view(Request $request, $id) {
        $user = \App\extension::find($id);
        return view('user-edit', compact('user'));
    }


    public function edit(Request $request, $id) {
        $this->validate($request, [
            'extension_nr' => 'required',
            'name' => 'required',
            'pipedrive_id' => 'required'
        ]);
        $extension = $request->input('extension_nr');
        $name = $request->input('name');
        $pipedrive = $request->input('pipedrive_id');

        $check = \App\extension::where("id","!=", $id)
                               ->where(function ($query) use ($extension, $pipedrive){
                                                $query->where("extension_nr", "=", $extension)
                                                      ->orWhere("pipedrive_id", "=", $pipedrive);
                                            })
                               ->get();

        if (count($check) == 0) {
            $user = \App\extension::find($id);
            $user->extension_nr = $extension;
            $user->name = $name;
            $user->pipedrive_id = $pipedrive;
            $user->save();
        } else {
            return Redirect::back()->withErrors(['message' => "Extension or Pipedrive id already exists."]);
        }

        return redirect('/user');
    }


    public function delete(Request $request, $id) {
        $user = \App\extension::find($id);
        $user->delete();
        return redirect('/user');
    }


    public function log(Request $request) {
        $date = \Carbon\Carbon::now('Asia/Bangkok')->format('Y-m-d');
        $dir = '/var/www/grandstream/storage/logs/cron/'.$date;
        $logs = array();
        if(is_dir($dir)) {
            $files = scandir($dir);
            $logs_all = "";
            foreach($files as $file) {
                if (strpos($file, $date) !== false and strpos($file, 'cron-post-pipedrive') !== false) {
                    $logs_all .= file_get_contents("$dir/$file");
                }
            }
            $logs_all = explode("\r\n", $logs_all);
            foreach($logs_all as $log) {
                if (strpos($log, "not on pipedrive") !== false or strpos($log, "internal") !== false) {
                    continue;
                }
                $logs[] = $log;
            }
        }
        return view('user-log', compact('logs'));
    }
}
