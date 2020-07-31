<?php
function exec_with_output($command) {
  try {
    $p = "COMPLETE :D";
    exec('mktemp',$filename);
    $filename = $filename[0];
    exec('(' . $command . '; echo ' . $p . ') > ' . $filename . ' &');
    $handle = fopen($filename, "r"); 
    $line = "";
    $last_size = 0;
    while (trim($line) != $p) {
      $line = fgets($handle); 
      $filesize = filesize($filename);
      if ( !$line ) {
        fseek($handle, $filesize-$last_size, SEEK_END);
        sleep(1);
      }
      $last_size = $filesize;
      echo $line;
    }
    fclose($handle);
    unlink($filename);
  } catch (Exception $e) {
    fclose($handle);
    unlink($filename);
  }
}

exec_with_output('ls -lah; sleep 2; pwd; echo "moi"; sleep 1; ls');
echo "Finished reading :)\n";
