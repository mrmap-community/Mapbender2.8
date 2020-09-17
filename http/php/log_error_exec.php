<?php
function log_error_exec($cmd) {
	$piped_cmd = $cmd . " 2>&1";
	$ret_val = 0;
	$output = array();
	exec($piped_cmd, $output, $ret_val);
	if ($ret_val > 0) {
		new mb_exception("command: ".$piped_cmd."\noutput: ".implode("\n", $output));
		return FALSE;
	}
    else {
        new mb_notice("command: ".$piped_cmd);
    }
	return TRUE;
}
