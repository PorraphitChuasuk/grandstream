<?php

use Carbon\Carbon;

/*
Changing the token requires changes in update_person_deal , post_content, get_person_info
Changing the username and password of cdrapi requires changes in update_recordfiles, post_content, find_actual_operator
*/


/*
Testing function shouldn't be run before modification to 3 main function at the bottom of this function
1) Create current session log file
2) Retreive all the recordfile's name from the grandstream
3) Update the mapping of each phone number to their latest deal
4) Post recordfile to pipedrive
*/
function entry() {
    /*
    Sometimes while retrieving all deals information max_execution_time error appear
    Setting it to 300 seconds instead of 30 seems to solve the problem
    */
    ini_set('max_execution_time', 300);

    cron_update_grandstream_recordfiles();
    cron_update_mapping_pipedrive();
    cron_post_pipedrive();
}


/*
A cron that map the phone number to their latest deal on pipedrive by running from the offset 0
*/
function cron_reset_mapping_table() {
    ini_set('max_execution_time', 300);
    $log_file = create_file('cron-reset-mapping-pipedrive');
    update_person_deal($log_file, 0);
}


/*
A cron that post recording files to pipedrive
*/
function cron_post_pipedrive() {
    ini_set('max_execution_time', 300);
    $log_file = create_file('cron-post-pipedrive');
    post_pipedrive($log_file);
}


/*
A cron that update the mapping of phone number to latest pipedrive deal
*/
function cron_update_mapping_pipedrive() {
    ini_set('max_execution_time', 300);
    $log_file = create_file('cron-update-mapping-pipedrive');
    update_person_deal($log_file);
}


/*
A cron that update the grandstream recordfiles into the database.
*/
function cron_update_grandstream_recordfiles() {
    ini_set('max_execution_time', 300);
    $log_file = create_file('cron-update-grandstream-recordfiles');
    update_recordfiles($log_file);
}


/*
A function that create file and return filename
*/
function create_file($name = "") {
    $now = Carbon::now('Asia/Bangkok');
    $directory = "/var/www/grandstream/storage/logs/cron/".$now->format('Y-m-d');
    if (!is_dir($directory)) {
        mkdir($directory, 0777);
    }
    $log_file = $directory."/".$now->format('Y-m-d-H-i-s')."-$name.txt";
    $file = fopen($log_file, "w");
    chmod($log_file, 0666);

    return $log_file;
}


/*
A Function that read all the recordfiles from the grandstream and update the newer ones to the Database
*/
function update_recordfiles($log_file) {
    $username = 'cdrapi';
    $password = 'cdrapi123';
    $files_dir = "monitor_local@monitor";
    $url = "https://10.110.10.20:8443/recapi?filedir=".$files_dir;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    curl_setopt($ch, CURLOPT_USERPWD, $username.":".$password);

    $csv_output = curl_exec($ch);
    curl_close($ch);

    $array_output = str_getcsv($csv_output);
    $length = count($array_output);

    for($i = 2;$i < $length;$i++) {
        $filename = extract_filename($array_output[$i]);
        $state = App\recordingfile::firstOrCreate(['filename' => $filename]);
        if ($state->wasRecentlyCreated) {
            file_put_contents($log_file, "Added Recording File -> $filename\r\n", FILE_APPEND);
        }
    }
}


/*
A Function that extract recording filename from the converted csv_output
and return filename
*/
function extract_filename($name) {
    $temp = explode("\n", $name);
    return $temp[0];
}


/*
A Function that takes a filename of the audio For ex- auto-1496639037-021528797-6598.wav
and return caller => 021528797 and callee => 6598 as associative array
and also determines the whether the customer is caller or callee and return customer => caller or custormer => callee
or customer => null incase the call is internal
*/
function extract_recordfiles_user($filename) {
    $result = array();

    $filename_parts = explode("-", $filename);
    $dot_pos = strpos($filename_parts[3], ".");
    $result["caller"] = $filename_parts[2];
    $result["callee"] = substr($filename_parts[3], 0, $dot_pos);

    /* check if the call is internal */
    if (strlen($result["caller"]) < 5 and strlen($result["callee"]) < 5) {
        $result["customer"] = null;
    } else {
        /* If operator is the caller */
        if (strlen($result["caller"]) < 5) {
            $result["customer"] = "callee";
            $result["operator"] = "caller";
            /* Delete the leading 9 or 7 that operator used to dial */
            $result["callee"] = substr($result["callee"], 1, 20);
        } else {
            $result["customer"] = "caller";
            $result["operator"] = "callee";
        }
    }

    return $result;
}


/*
A Function that map and update the person number on pipedrive to their latest deal
*/
function update_person_deal($log_file, $offset=null) {
    $token = "874ed683dc8d5f5d5767a506417ff5cc027fbf58";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 0);

    if($offset == null) {
        $offset = App\offset::first()->value('deals_offset');
    }

    $is_more_item = true;

    while($is_more_item) {

        $url = 'https://api.pipedrive.com/v1/deals?start='.$offset.'&limit=500&api_token='.$token;
        curl_setopt($ch, CURLOPT_URL, $url);

        $json = curl_exec($ch);
        $output = json_decode($json, true);

        $is_more_item = $output["additional_data"]["pagination"]["more_items_in_collection"];

        if (isset($output["additional_data"]["pagination"]["next_start"])) {
            $offset = $output["additional_data"]["pagination"]["next_start"];
        }

        foreach($output["data"] as $deal) {
            if($deal['person_id'] == null) continue;
            foreach($deal["person_id"]["phone"] as $phone) {
                if ($phone["value"] != "") {
                    $phone_nr = pretty_phone_nr($phone["value"]);
                    App\deal::updateOrCreate(["phone_nr" => $phone_nr], ["deal_id" => $deal["id"]]);
                    file_put_contents($log_file, "Mapped Phone nr -> $phone_nr to deal id -> ".$deal["id"]."\r\n", FILE_APPEND);
                }
            }
        }
    }
    curl_close($ch);
    /* Update offset to latest */
    $temp = App\offset::first();
    $temp->deals_offset = $offset;
    $temp->save();
}


/*
A Function that return proper phone number
*/
function pretty_phone_nr($phone_nr) {
    $phone_nr = preg_replace("/[^0-9]/", "", $phone_nr);

    /* Remove 66 in case it is there */
    if(substr($phone_nr,0,2) == "66") {
        $phone_nr = substr($phone_nr,2,20);
    }

    /* Prepend 0 to phone_nr incase there isn't any */
    if ($phone_nr != "" and $phone_nr[0] != "0") {
        $phone_nr = "0".$phone_nr;
    }

    $firsttwo = substr($phone_nr,0,2);
    /* Check if mobile nr and if yes reduce to 10 digits */
    if($firsttwo == "06" || $firsttwo == "08" || $firsttwo == "09") {
        $phone_nr = substr($phone_nr,0,10);
    }

    /* Check if fixed line nr and if yes reduce to 9 digits */
    if($firsttwo == "02" || $firsttwo == "03" || $firsttwo == "04" || $firsttwo == "05" || $firsttwo == "07") {
        $phone_nr = substr($phone_nr,0,9);
    }

    return $phone_nr;
}


/*
A Function that post all the new recordfiles to pipedrive deal
*/
function post_pipedrive($log_file) {
    App\recordingfile::where('is_processed','=','0')->chunk(500, function($recording_files) use ($log_file) {
        foreach($recording_files as $file) {
            $filename = $file->filename;
            $users = extract_recordfiles_user($filename);
            $customer = $users["customer"];
            /* Check internal call */
            if ($customer != null) {
                $customer_nr = pretty_phone_nr($users[$customer]);
                $record = App\deal::where("phone_nr","=","$customer_nr")->first();
                /* if phone_nr found to be related to pipedrive */
                if ($record != null) {
                    $deal_id = $record->deal_id;
                    $operator_nr = $users[$users["operator"]];
                    $result = post_content($log_file, $deal_id, $operator_nr, $customer_nr, $filename);
                    if ($result == true) {
                        $file->is_processed = true;
                        $file->save();
                    }
                } else {
                    file_put_contents($log_file, "Phone number is not on pipedrive for $customer_nr with filename $filename\r\n", FILE_APPEND);
                }
            } else {
                file_put_contents($log_file, "Didn't add internal call with filename $filename\r\n", FILE_APPEND);
                $file->is_processed = true;
                $file->save();
            }
        }
    });
}


function post_content($log_file, $deal_id, $operator_nr, $customer_nr, $filename) {
    $token = "874ed683dc8d5f5d5767a506417ff5cc027fbf58";
    $username = 'cdrapi';
    $password = 'cdrapi123';

    /* check if inbound call, the recording files record it as the ring group, find the actual operator */
    $actual_operator_nr = array();
    if ($operator_nr[0] != "1") {
        $actual_operator_nr = find_actual_operator($filename, $customer_nr);
        $count = count($actual_operator_nr);
        if ($count != 0) {
            /* Operator who talk the last is the owner of the call */
            $operator_nr = $actual_operator_nr[$count - 1];
        }
    }

    /* Testing certain operator extension */
    if ($operator_nr != "1034" and $operator_nr != "1007") {
        return false;
    }

    $person_info = get_person_info($deal_id);
    $person_id = $person_info["person_id"];
    $org_id = $person_info["org_id"];

    $detail = App\extension::where("extension_nr", "=", "$operator_nr")->first();

    $query_array = [
        "subject" => "Call Recording",
        "type" => "call",
        "done" => 1,
        "person_id" => $person_id,
        "org_id" => $org_id
    ];

    $calltime = extract_calltime($filename);

    $content = "<ul>".
                    "<li>Phone number: $customer_nr</li>".
                    "<li>Call Time: $calltime</li>".
                    "<li><a href=\"https://$username:$password@10.110.10.20:8443/recapi?filename=$filename\">Download</a></li>";

    $log = "";
    /* post using pipedrive id of the operator */
    if ($detail != null) {
        $query_array["user_id"] = $detail->pipedrive_id;
        $log .= "Posted $filename by operator: $operator_nr with person number: $customer_nr and person id: $person_id and org id: $org_id";
    } else {
        $content .= "<li>Unknown Extension: $operator_nr</li>";
        $log .= "Operator ($operator_nr) not found for $filename posted with person number: $customer_nr and person id: $person_id and org id: $org_id";
    }
    $content .= "</ul>";
    $query_array["note"] = $content;

    if ($person_info["open"]) {
        $query_array["deal_id"] = $deal_id;
        $log .= " to deal id : $deal_id";
    }
    $log .= "\r\n";

    $data = http_build_query($query_array);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,'https://api.pipedrive.com/v1/activities?api_token='.$token);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$json = curl_exec($ch);
	curl_close($ch);

	$output = json_decode($json, true);

    if($output["success"] == true) {
        file_put_contents($log_file, $log, FILE_APPEND);
        return true;
    } else {
        file_put_contents($log_file, "ERROR adding file $filename to pipedrive with operator $operator_nr and person number $customer_nr\r\n", FILE_APPEND);
        return false;
    }
}


/*
Find the operator if the filename has ring-group in its name by scanning the cdr table
if record is found return the actual operator extension otherwise return empty array
*/
function find_actual_operator($filename, $customer_nr) {
    $username = 'cdrapi';
    $password = 'cdrapi123';
    $url = "https://10.110.10.20:8443/cdrapi?format=JSON&caller=$customer_nr&numrecords=1000&offset=";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    curl_setopt($ch, CURLOPT_USERPWD, $username.":".$password);

    $result = array();
    $offset = 0;
    $is_record_found = false;

    while (!$is_record_found) {
        curl_setopt($ch, CURLOPT_URL, $url.$offset);

        $output = curl_exec($ch);
        $output = json_decode($output, true);

        if (count($output["cdr_root"]) == 0) {
            break;
        }

        foreach($output["cdr_root"] as $val) {
            /* Check if it has sub cdr */
            if (array_key_exists("main_cdr", $val)) {
                /* Check whether filename correspond to which cdr */
                if (strpos($val["main_cdr"]["recordfiles"], $filename) !== false) {
                    $is_record_found = true;
                }
                /* Looping through all sub cdr */
                $cdr_count = count($val);
                for ($i = 1;$i <= $cdr_count - 2;$i++) {
                    if (strpos($val["sub_cdr_$i"]["recordfiles"], $filename) !== false) {
                        $is_record_found = true;
                    }
                }
            } else {
                if (strpos($val["recordfiles"], $filename) !== false) {
                    $is_record_found = true;
                }
            }
            if ($is_record_found) {
                $result = extract_callee($val);
                break;
            }
        }
        $offset += 1000;
    }
    curl_close($ch);
    return $result;
}


/*
A function that extract all the callee from a record and return it as an array of callee
*/
function extract_callee($cdr) {
    $result = array();
    if (array_key_exists("main_cdr", $cdr)) {
        /* Not accepting ring group as callee */
        if ($cdr["main_cdr"]["dstanswer"] != "" and $cdr["main_cdr"]["dstanswer"][0] == "1") {
            $result[] = $cdr["main_cdr"]["dstanswer"];
        }
        $cdr_count = count($cdr);
        for ($i = 1;$i <= $cdr_count - 2;$i++) {
            if ($cdr["sub_cdr_$i"]["dstanswer"] != "" and $cdr["sub_cdr_$i"]["dstanswer"][0] == "1") {
                $result[] = $cdr["sub_cdr_$i"]["dstanswer"];
            }
        }
    } else {
        if ($cdr["dstanswer"] != "" and $cdr["dstanswer"][0] == "1") {
            $result[] = $cdr["dstanswer"];
        }
    }
    return $result;
}


/*
A function that takes a filename and convert the unix time to readable time
*/
function extract_calltime($filename) {
    $temp = explode('-',$filename);
    $unix_time = $temp[1];
    $time = date('d M Y H:i:s', $unix_time);
    return $time;
}


/*
A Function that extracts person id and person organization id from a deal
Also check whether the status of the deal is open or not
*/
function get_person_info($deal_id) {
    $token = "874ed683dc8d5f5d5767a506417ff5cc027fbf58";
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, "https://api.pipedrive.com/v1/deals/$deal_id?api_token=$token");

    $json = curl_exec($ch);
    $output = json_decode($json, true);
    curl_close($ch);

    $result = array();
    $result["person_id"] = $output["data"]["person_id"]["value"];
    $result["org_id"] = $output["data"]["org_id"]["value"];

    if($output["data"]["status"] == "open") {
        $result["open"] = true;
    } else {
        $result["open"] = false;
    }

    return $result;
}
