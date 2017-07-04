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
        $countries = \App\country_code::all();
        return view('user-add', compact('countries'));
    }


    public function store(Request $request) {
        $this->validate($request, [
            'extension_nr' => 'required|unique:extensions',
            'name' => 'required',
            'pipedrive_id' => 'required|unique:extensions',
            'country_code_id' => 'required'
        ]);

        $extension_nr = $request->input('extension_nr');
        $name = $request->input('name');
        $pipedrive_id = $request->input('pipedrive_id');
        $country_code_id = $request->input('country_code_id');

        \App\extension::create([
                                    'extension_nr' => $extension_nr,
                                    'name' => $name,
                                    'pipedrive_id' => $pipedrive_id,
                                    'country_code_id' => $country_code_id
                                ]);
        return redirect('/user');
    }


    public function edit_view(Request $request, $id) {
        $user = \App\extension::find($id);
        $countries = \App\country_code::all();
        return view('user-edit', compact('user', 'countries'));
    }


    public function edit(Request $request, $id) {
        $this->validate($request, [
            'extension_nr' => 'required',
            'name' => 'required',
            'pipedrive_id' => 'required',
            'country_code_id' => 'required'
        ]);
        $extension_nr = $request->input('extension_nr');
        $name = $request->input('name');
        $pipedrive_id = $request->input('pipedrive_id');
        $country_code_id = $request->input('country_code_id');
        $is_enable = $request->input('is_enable');

        $check = \App\extension::where("id","!=", $id)
                               ->where(function ($query) use ($extension_nr, $pipedrive_id){
                                                $query->where("extension_nr", "=", $extension_nr)
                                                      ->orWhere("pipedrive_id", "=", $pipedrive_id);
                                            })
                               ->get();

        if (count($check) > 0) {
            return Redirect::back()->withErrors(['message' => "Extension or Pipedrive id already exists."]);
        }

        $user = \App\extension::find($id);
        $user->extension_nr = $extension_nr;
        $user->name = $name;
        $user->pipedrive_id = $pipedrive_id;
        $user->country_code_id = $country_code_id;
        if ($is_enable == null) {
            $user->is_enable = false;
        } else {
            $user->is_enable = true;
        }
        $user->save();
        return redirect('/user');
    }


    public function delete(Request $request, $id) {
        $user = \App\extension::find($id);
        $user->delete();
        return redirect('/user');
    }

    /* Obsolete Maybe
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
    */
}
