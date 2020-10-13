<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init_noauth.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();
//--------------------------------------------------------------------------------


//Make sure we got a valid url request
if( isset($_REQUEST['url']) && !empty($_REQUEST['url']) && stripos($_REQUEST['url'], 'http') == 0) {
    $url = $_REQUEST['url'];
} else {
    $jsondata['status'] = "false";
    $jsondata['description'] = "Invalid url.";
    echo json_encode($jsondata);
    loggit(2,"Invalid url.");
    exit(0);
}

$datatype = 'opml';
$urldata = fetchUrl(get_final_url($url));
if(is_feed($urldata)) {
    $datatype = 'feed';
    $urldata = convert_feed_to_opml($urldata);
} else if (is_outline($urldata)) {
    $datatype = 'opml';
} else if (is_json($urldata)) {
    $datatype = 'json';
}

//--------------------------------------------------------------------------------
//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['data'] = $urldata;
$jsondata['type'] = $datatype;
$jsondata['description'] = "Url fetch successful.";
//loggit(3, "DEBUG(json): ".print_r($jsondata, TRUE));
echo json_encode($jsondata);

return(0);