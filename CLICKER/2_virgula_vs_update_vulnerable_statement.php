<?php
function save_profile($player, $args) {
  	$params = ["player"=>$player];
	$setStr = "";
  	foreach ($args as $key => $value) {
    		$setStr .= $key . "=" . $value . ",";
	}
  	$setStr = rtrim($setStr, ",");
  	echo "UPDATE players SET $setStr WHERE username = $player";
  	die();
}


	$args = [];
	foreach($_GET as $key=>$value) {
		if (strtolower($key) === 'role') {
			// prevent malicious users to modify role
			header('Location: /index.php?err=Malicious activity detected!');
			die;
		}
		$args[$key] = $value;
	}
	save_profile('cannot_changed', $_GET);

?>
