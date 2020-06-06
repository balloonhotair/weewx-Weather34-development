<?php 
    include_once('../settings1.php');
?>
<!DOCTYPE html> 
<html> 
    <head> 
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://code.highcharts.com/stock/highstock.js"></script>
    <script src="https://code.highcharts.com/stock/highcharts-more.js"></script>
    <script src="https://code.highcharts.com/stock/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/windbarb.js"></script>
    <script src="https://code.highcharts.com/modules/boost.js"></script>    
    <script src="scripts/<?php echo $theme1;?>-theme.js" type="text/javascript"></script>
    <script src="scripts/plots_config.js" type="text/javascript"></script>
    <script src="scripts/plots.js" type="text/javascript"></script>
    <script src="scripts/convert_units.js" type="text/javascript"></script>
    <script src="../languages/translations.js" type="text/javascript"></script>
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/base/jquery-ui.css">
    </head> 
    <body> 
       <div style="width:auto;">  
       <div id="plot_div" style="width:100%; height:435px;"></div>
    </div>
    </body> 
</html>
<?php
    $plot_info = explode(",",$_GET['plot_type']);
    $units = explode(",",$_GET['units']);
    $filenames = explode(":", $plot_info[2]);
    for ($i = 0; $i < sizeof($filenames); $i++){ 
      if (isset($weexserver_address) and isset($weexserver_port)){
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_connect($socket, $weexserver_address, $weexserver_port);
      }
      else
        putenv("PYTHONPATH=".$_GET['weewxpathbin']);
      $filename = $plot_info[1].$filenames[$i];
      if (file_exists($filename))
        unlink($filename);
      $cmd = $plot_info[3]." ".$_GET['epoch']." ".$filename.".tmpl ".getcwd();
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
        $cmd = $plot_info[3]." ".$_GET['epoch1']." ".$filename.".tmpl ".getcwd();
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
    if (isset($_GET['epoch1']))
      echo "<script> display_chart({temp:"."'".$units[0]."',pressure:"."'".$units[1]."',wind:"."'".$units[2]."',rain:"."'".$units[3]."'},'".$plot_info[0]."','weekly',false,true,'".$plot_info[4]."',".$plot_info[5].");</script>";
    else
      echo "<script> display_chart({temp:"."'".$units[0]."',pressure:"."'".$units[1]."',wind:"."'".$units[2]."',rain:"."'".$units[3]."'},'".$plot_info[0]."','weekly',true,false,'".$plot_info[4]."',".$plot_info[5].");</script>";
?> 

