<?php 
    $plot_info = explode(",",$_GET['plot_info']);
    $units = explode(",",$_GET['units']);
    $filenames = explode(":", $plot_info[1]);
    for ($i = 0; $i < sizeof($filenames); $i++){ 
      if (isset($weexserver_address) and isset($weexserver_port)){
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_connect($socket, $weexserver_address, $weexserver_port);
      }
      else
        putenv("PYTHONPATH=".$_GET['weewxpathbin']);
      $filename = $plot_info[0].$filenames[$i];
      if (file_exists($filename))
        unlink($filename);
      $cmd = $plot_info[2]." ".$_GET['epoch']." ".$filename.".tmpl ".getcwd();
      #print($cmd);
      if (isset($_GET['epoch1'])){
        $s_file1 = explode(".", $filename)[0]."1.json";
        if (file_exists($s_file1))
          unlink($s_file1);
        if (isset($weexserver_address) and isset($weexserver_port)) {
          socket_write($socket, $cmd, strlen($cmd));
          @socket_read($socket, 1, PHP_NORMAL_READ);
          socket_close($socket);
        }
        else
          $output = shell_exec(escapeshellcmd($cmd));
        rename($filename, $s_file1);
        $cmd = $plot_info[2]." ".$_GET['epoch1']." ".$filename.".tmpl ".getcwd();
        #print($cmd);
        if (isset($weexserver_address) and isset($weexserver_port)) {
          $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
          socket_connect($socket, $weexserver_address, $weexserver_port);
          socket_write($socket, $cmd, strlen($cmd));
          @socket_read($socket, 1, PHP_NORMAL_READ);
          socket_close($socket);
        }
        else
          $output = shell_exec(escapeshellcmd($cmd));
      }
      else {
        if (isset($weexserver_address) and isset($weexserver_port)){
          socket_write($socket, $cmd, strlen($cmd));
          @socket_read($socket, 1, PHP_NORMAL_READ);
          socket_close($socket);
        }
        else
          $output = shell_exec(escapeshellcmd($cmd));
      }
    }
?> 

