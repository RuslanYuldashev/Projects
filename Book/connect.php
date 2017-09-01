<?
$serverName = "DESKTOP-7NIJ3VC\BD"; //serverName\instanceName
$connectionInfo = array( "Database"=>"books", "CharacterSet" => "UTF-8");
$conn = sqlsrv_connect( $serverName, $connectionInfo);
//var_dump($conn);
if( $conn ) {
}else{
     echo "Connection could not be established.<br />";
     die( print_r( sqlsrv_errors(), true));
}
?>