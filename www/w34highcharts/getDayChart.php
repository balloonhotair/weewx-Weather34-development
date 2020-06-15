<?php 
    include('../w34CombinedData.php');
    $plot_info = explode(",",$_GET['plot_info']);
    $filenames = explode(":", $plot_info[1]);
    if (isset($weather['weewx_ip'])){
      $data = explode(":", $weather['weewx_ip']);
      $weewxserver_address = trim($data[0]);
      putenv("PYTHONPATH=".trim($data[1]));
    }
    if (!isset($weewxserver_port)) 
      $weewxserver_port = 25252;
    for ($i = 0; $i < sizeof($filenames); $i++){ 
      if (isset($weewxserver_address) and isset($weewxserver_port)){
        try {
          $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
          socket_connect($socket, $weewxserver_address, $weewxserver_port);
        }catch(Exception $e){
          $weewxserver_port = NULL;
          $weewxserver_address = NULL;
        }
      }
      $filename = $plot_info[0].$filenames[$i];
      if (file_exists($filename))
        unlink($filename);
      $cmd = $plot_info[2]." ".$_GET['epoch']." ".$filename.".tmpl ".getcwd();
      //error_log($cmd);
      if (isset($_GET['epoch1'])){
        $s_file1 = explode(".", $filename)[0]."1.json";
        if (file_exists($s_file1))
          unlink($s_file1);
        if (isset($weewxserver_address) and isset($weewxserver_port)) {
          try {
            socket_write($socket, $cmd, strlen($cmd));
            @socket_read($socket, 1, PHP_NORMAL_READ);
            @socket_close($socket);
          }catch(Exception $e){
            shell_exec(escapeshellcmd($cmd));
          }
        }
        else
          shell_exec(escapeshellcmd($cmd));
        rename($filename, $s_file1);
        $cmd = $plot_info[2]." ".$_GET['epoch1']." ".$filename.".tmpl ".getcwd();
        //error_log($cmd);
        if (isset($weewxserver_address) and isset($weewxserver_port)) {
          try {
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            socket_connect($socket, $weewxserver_address, $weewxserver_port);
            socket_write($socket, $cmd, strlen($cmd));
            @socket_read($socket, 1, PHP_NORMAL_READ);
            @socket_close($socket);
          }catch(Exception $e){
            shell_exec(escapeshellcmd($cmd));
          }
        }
        else
          shell_exec(escapeshellcmd($cmd));
      }
      else {
        if (isset($weewxserver_address) and isset($weewxserver_port)){
          try {
            socket_write($socket, $cmd, strlen($cmd));
            @socket_read($socket, 1, PHP_NORMAL_READ);
            @socket_close($socket);
          }catch(Exception $e){
            shell_exec(escapeshellcmd($cmd));
          }
        }
        else
          shell_exec(escapeshellcmd($cmd));
      }
    }
?> 

