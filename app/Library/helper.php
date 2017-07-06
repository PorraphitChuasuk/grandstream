<?php


use Carbon\Carbon;


function log_to_file($filename, $content) {
    $date = Carbon::now('Asia/Bangkok')->format('Y-m-d H:i:s');
    file_put_contents($filename, '['.$date.'] '.$content."\r\n", FILE_APPEND);
}


function create_file($name = "") {
    $now = Carbon::now('Asia/Bangkok');

    $directory = base_path()."/storage/logs/cron/".$now->format('Y-m-d');
    if (!is_dir($directory)) mkdir($directory);

    $log_file = $directory."/".$now->format('Y-m-d H-i-s')." $name.txt";
    $file = fopen($log_file, "w");
    chmod($log_file, 0666);
    return $log_file;
}


function get_post_pipedrive_log_filename() {
    return "Posting Recordfile Pipedrive";
}


/*
Relies on log File and the line that contain "Posted"
For ex: [2017-07-05 18:11:59] [thailand] Posted auto-1499250085-1010-8053705444.wav by operator: 1010 with person number: 053705444 and person id: 36831 and org id: 33120 and deal id: 54150
*/
function get_pushed_count() {
    $day = Carbon::now('Asia/Bangkok')->format('Y-m-d');
    $log_folder = base_path().'/storage/logs/cron/'.$day;
    $result = [];
    if (is_dir($log_folder)) {
        $files = scandir($log_folder);
        foreach($files as $file) {
            if(strpos($file, get_post_pipedrive_log_filename()) !== false) {
                $lines = explode("\r\n", file_get_contents($log_folder.'/'.$file));
                foreach($lines as $line) {
                    if(strpos($line, "Posted") !== false) {
                        $operator_nr = explode(" ", $line)[7];
                        if(!isset($result[$operator_nr])) {
                            $result[$operator_nr] = 0;
                        }
                        $result[$operator_nr] += 1;
                    }
                }
            }
        }
    }
    return $result;
}
