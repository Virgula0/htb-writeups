<?php
$psw_raw = hash("sha256","notKnownSecurePassword");

$password = $_POST["password"];

echo $passwod . "\n";
echo hash("sha256",$password) . "\n";

if(strcmp($psw_raw, hash("sha256",$password)) == 0){
	die("True! Password are equals");
}

die ("Try harder");

?>
