<?php

namespace App\Library;

class Grandstream {

    const INTERNAL = 'internal';
    const EXTERNAL = 'external';

    private $api_username = 'cdrapi';
    private $api_password = 'cdrapi123';
    private $files_dir = 'monitor';
    private $internal_ip = '10.110.100.20';
    private $external_ip = 'gogoprintoffice.ddns.net';
    private $port = '8443';
    private $extension_length = 4; // Length of extension must be lesser than 7
    private $ring_group = '6'; // The first number of the extension of ring group

    public function add_recordfile() {
        $url = 'https://'.$this->internal_ip.':'.$this->port.'/recapi?filedir='.$this->files_dir;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_USERPWD, $this->api_username.':'.$this->api_password);

        $csv_output = curl_exec($ch);
        curl_close($ch);

        $log_file = create_file('Add Recording Files');
        $array_output = str_getcsv($csv_output, "\n");
        $array_output_count = count($array_output);
        for($i = 1;$i < $array_output_count;$i++) {
            $filename =  explode(',', $array_output[$i])[1];
            $state = \App\recordingfile::firstOrCreate(['filename' => $filename]);
            if ($state->wasRecentlyCreated) {
                log_to_file($log_file, 'Added Recording File -> '.$filename);
            }
        }
    }

    public function extract_info($filename) {
        $result = array();

        $filename_parts = explode("-", $filename);

        $unix_time = $filename_parts[1];
        $result["time"] = date('d M Y H:i:s', $unix_time);

        $dot_pos = strpos($filename_parts[3], ".");
        $result["callee"] = substr($filename_parts[3], 0, $dot_pos);
        $result["caller"] = $filename_parts[2];

        /* check if the call is internal */
        if (strlen($result["caller"]) <= $this->extension_length and strlen($result["callee"]) <= $this->extension_length) {
            $result["type"] = self::INTERNAL;
            $result["customer"] = null;
            $result["operator"] = null;
        } else {
            /* If operator is the caller */
            if (strlen($result["caller"]) <= $this->extension_length) {
                $result["customer"] = "callee";
                $result["operator"] = "caller";
                /* Delete the leading 9 or 7 that operator used to dial */
                $result["callee"] = substr($result["callee"], 1, 20);
            } else {
                $result["customer"] = "caller";
                $result["operator"] = "callee";
                /* If incoming call has ring-group instead of operator extension */
                if ($result["callee"][0] == $this->ring_group) {
                    $operators = $this->_get_operator($filename, $result["caller"]);
                    /* Incase the record isn't found in cdr table */
                    if (count($operators) > 0) {
                        /* Operator who talked the last is the owner of the call */
                        $result["callee"] = $operators[count($operators) - 1];
                    }
                }
            }
            $result["type"] = self::EXTERNAL;
        }
        return $result;
    }

    public function get_download_url($filename) {
        return "https://$this->api_username:$this->api_password@$this->external_ip:$this->port/recapi?filename=$filename";
    }

    private function _get_operator($filename, $caller_nr) {
        $url = 'https://'.$this->internal_ip.':'.$this->port.'/cdrapi?format=JSON&numrecords=1000&caller='.$caller_nr.'&offset=';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_USERPWD, $this->api_username.':'.$this->api_password);

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
                    return $this->_extract_callee($val);
                }
            }
            $offset += 1000;
        }
        curl_close($ch);
        return $result;
    }

    private function _extract_callee($cdr) {
        $result = array();
        if (array_key_exists("main_cdr", $cdr)) {
            /* Not accepting ring group as callee */
            if ($cdr["main_cdr"]["dstanswer"] != "" and $cdr["main_cdr"]["dstanswer"][0] != $this->ring_group) {
                $result[] = $cdr["main_cdr"]["dstanswer"];
            }
            $cdr_count = count($cdr);
            for ($i = 1;$i <= $cdr_count - 2;$i++) {
                if ($cdr["sub_cdr_$i"]["dstanswer"] != "" and $cdr["sub_cdr_$i"]["dstanswer"][0] != $this->ring_group) {
                    $result[] = $cdr["sub_cdr_$i"]["dstanswer"];
                }
            }
        } else {
            if ($cdr["dstanswer"] != "" and $cdr["dstanswer"][0] != $this->ring_group) {
                $result[] = $cdr["dstanswer"];
            }
        }
        return $result;
    }

    public function update_cdr() {
        $url = 'https://'.$this->internal_ip.':'.$this->port.'/cdrapi?format=JSON&numrecords=1000&offset=';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_USERPWD, $this->api_username.':'.$this->api_password);

        $offset = 0;
        $log_file = create_file('Update CDR Table');

        while (true) {
            curl_setopt($ch, CURLOPT_URL, $url.$offset);

            $output = curl_exec($ch);
            $output = json_decode($output, true);

            if (count($output["cdr_root"]) == 0) {
                break;
            }

            log_to_file($log_file, "Currently at $offset");

            foreach($output["cdr_root"] as $val) {
                /* This info isn't available within main_cdr and sub_cdr */
                $cdr = $val["cdr"];
                /* Check if it has sub cdr */
                if (array_key_exists("main_cdr", $val)) {
                    $this->_update_cdr_table($cdr, $val["main_cdr"], $log_file);
                    /* Looping through all sub cdr */
                    $cdr_count = count($val);
                    for ($i = 1;$i <= $cdr_count - 2;$i++) {
                    $this->_update_cdr_table($cdr, $val["sub_cdr_$i"], $log_file);
                    }
                } else {
                    $this->_update_cdr_table($cdr, $val, $log_file);
                }
            }
            $offset += 1000;
        }
        curl_close($ch);
    }

    private function _update_cdr_table($cdr, $record, $log_file) {
        $data = [
            'cdr' => $cdr,
            'acctid' => $record['AcctId'],
            'session' => $record['session'],
            'accountcode' => $record['accountcode'],
            'src' => $record['src'],
            'dst' => $record['dst'],
            'dcontext' => $record['dcontext'],
            'clid' => $record['clid'],
            'channel' => $record['channel'],
            'dstchannel' => $record['dstchannel'],
            'lastapp' => $record['lastapp'],
            'lastdata' => $record['lastdata'],
            'start' => $record['start'],
            'answer' => $record['answer'],
            'end' => $record['end'],
            'duration' => $record['duration'],
            'billsec' => $record['billsec'],
            'disposition' => $record['disposition'],
            'amaflags' => $record['amaflags'],
            'uniqueid' => $record['uniqueid'],
            'userfield' => $record['userfield'],
            'channel_ext' => $record['channel_ext'],
            'dstchannel_ext' => $record['dstchannel_ext'],
            'service' => $record['service'],
            'caller_name' => $record['caller_name'],
            'recordfiles' => $record['recordfiles'],
            'dstanswer' => $record['dstanswer'],
            'chanext' => $record['chanext'],
            'dstchanext' => $record['dstchanext'],
            'action_owner' => $record['action_owner'],
            'action_type' => $record['action_type'],
            'src_trunk_name' => $record['src_trunk_name'],
            'dst_trunk_name' => $record['dst_trunk_name']
        ];

        $check = \App\local_cdr::where([
            ['acctid', '=', $record['AcctId']],
            ['session', '=', $record['session']]
        ])->first();

        if ($check == null) {
            \App\cdr::create($data);
            \App\local_cdr::create($data);
            log_to_file($log_file,
            'Added New Record -> acctid: '.$record['AcctId'].' session: '.$record['session']);
        }
    }
}
