<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init_noauth.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
if( !isset($_REQUEST['dig']) ) {
  header("Content-Type: application/json");
}
$jsondata = array();
$jsondata['fieldname'] = "";

//Validate the user id
if( !empty($_REQUEST['uid']) ) {
  $uid = $_REQUEST['uid'];
  if( !user_exist($uid) ) {
    //Log it
    loggit(2,"Invalid user. Can't add this feed.");
    $jsondata['status'] = "false";
    $jsondata['description'] = "Access denied.";
    echo json_encode($jsondata);
    exit(1);
  }

  $prefs = get_user_prefs($uid);
  if( $prefs['opensubs'] == 0 ) {
    //Log it
    loggit(2,"Invalid user. Can't add this feed.");
    $jsondata['status'] = "false";
    $jsondata['description'] = "Access denied.";
    echo json_encode($jsondata);
    exit(1);
  }
} else {
  //Log it
  loggit(2,"Invalid user. Can't add this feed.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Access denied.";
  echo json_encode($jsondata);
  exit(1);
}


//Get the url of the feed
$urltarget = '';
$jsondata['fieldname'] = "url";
if ( isset($_REQUEST['url']) && !empty($_REQUEST['url']) ) {
    $url = $_REQUEST['url'];
} else {
  //Log it
  loggit(2,"There was no url. Can't add this feed.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "No URL given.";
  echo json_encode($jsondata);
  exit(1);
};
//Make sure url is within limits
if( strlen($url) > 760 ) {
  //Log it
  loggit(2,"The url is too long: [$url]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Max url length is 760 characters.";
  echo json_encode($jsondata);
  exit(1);
}

//Let's clean up the URL a bit to try and help the user
$url = trim(clean_url($url));

//Is the feed even valid?
$content = fetchUrl($url);
if( $content == FALSE ) {
  //This feed wasn't good, so let's check for the right protocol specifier
  $oldurl = $url;
  $protloc = stripos($url, 'http');
  if( $protloc === FALSE || $protloc > 0 ) {
    $url = 'http://'.$url;
  }
  loggit(3, "Bad feed check for url: [$oldurl]. Trying again as: [$url].");
  $content = fetchUrl($url);
}
if( $content == FALSE ) {
  //This feed wasn't good, so let's append a default to the end and try again
  $oldurl = $url;
  $url = rtrim($url, '/')."/".$default_social_outline_file_name;
  loggit(3, "Bad feed check for url: [$oldurl]. Trying again as: [$url].");
  $content = fetchUrl($url);
}
if( $content == FALSE ) {
  //Log it
  loggit(2,"Getting this url failed: [$url]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Whoa there! That's a bad url.";
  echo json_encode($jsondata);
  exit(1);
}


//Is this an opml outline?
if(is_outline($content)) {
  //Log it
  loggit(1,"The file at url: [$url] is an outline.");
  //Let's see what kind of outline it is

  //Is it a social outline? -------------------------------------------
  if(is_social_outline($content)) {

    //Get a list of feeds this social outline publishes
    $feeds = get_pub_feeds_from_outline($content);
    $feedcount = count($feeds);
    if($feedcount <= 0) {
      //No feeds in this outline
      $jsondata['status'] = "false";
      $jsondata['description'] = "This social outline doesn't publish anything.";
      echo json_encode($jsondata);
      exit(1);
    }

    //Add this outline and link it to this user
    $oid = add_social_outline($url, $uid);

    //Update the feed title
    $otitle = get_title_from_outline($content);
    if($otitle == FALSE) {
      $otitle = "Untitled Social Outline";
    }
    update_outline_title($oid, $otitle);

    //Update the feed ownername if there is one
    $oname = get_ownername_from_outline($content);
    if($oname != FALSE) {
      update_outline_ownername($oid, $oname);
    }

    //Update the outline content
    update_outline_content($oid, $content);

    //Get an avatar for this outline if there is one
    $avatarurl = get_avatar_url_from_outline($content);
    if($avatarurl != FALSE) {
      update_outline_avatar($oid, $avatarurl);
    }

    //Add each feed from the social outline and tie it to this outline
    $count = 0;
    foreach($feeds as $feed) {
      $fid = add_feed($feed, $uid, FALSE, $oid);
      mark_feed_as_updated($fid);
      loggit(1, "Added feed: [$feed] from a social outline subscription.");
      $count++;
    }

    $jsondata['status'] = "true";
    $jsondata['description'] = "$otitle publishes $feedcount feeds.";
    echo json_encode($jsondata);
    exit(1);
  }
  //-------------------------------------------------------------------

  //Is it a reading list? ---------------------------------------------
  if(is_reading_list($content)) {
    $feeds = get_feeds_from_outline($content);
    $feedcount = count($feeds);
    if($feedcount <= 0) {
      //No feeds in this outline
      $jsondata['status'] = "false";
      $jsondata['description'] = "There are no feeds in this outline.";
      echo json_encode($jsondata);
      exit(1);
    }

    //Add this outline and link it to this user
    $oid = add_outline($url, $uid);

    //Update the outline title
    $otitle = get_title_from_outline($content);
    if($otitle == FALSE) {
      $otitle = "Untitled Outline";
    }
    update_outline_title($oid, $otitle);

    //Update the outline content
    update_outline_content($oid, $content);

    //Add each feed from the reading list and tie it to this outline
    $count = 0;
    foreach($feeds as $feed) {
      $fid = add_feed($feed, $uid, FALSE, $oid);
      mark_feed_as_updated($fid);
      loggit(1, "Added feed: [$feed] from a reading list subscription.");
      $count++;
    }

    //No feeds in this outline
    $jsondata['status'] = "true";
    $jsondata['description'] = "Added $feedcount feeds from reading list.";
    echo json_encode($jsondata);
    exit(1);
  }
  //-------------------------------------------------------------------

  //It must be a plain old outline ----------------------------------------
  //
  //Add this outline and link it to this user
  $oid = add_outline($url, $uid, "opml");

  //Update the outline title
  $otitle = get_title_from_outline($content);
  if($otitle == FALSE) {
    $otitle = "Untitled Outline";
  }
  update_outline_title($oid, $otitle);

  //Update the outline content
  update_outline_content($oid, $content);


  $jsondata['status'] = "true";
  $jsondata['description'] = "Subscribed to $otitle.";
  echo json_encode($jsondata);
  exit(1);
  //-------------------------------------------------------------------
}

//Test if the feed has a valid structure
if( !feed_is_valid($content) ) {
  //Log it
  loggit(2,"This feed doesn't look right: [$url]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Whoa there! That feed looks broken.";
  echo json_encode($jsondata);
  exit(1);
}

//Test if feed already exists and is linked to this user
if( feed_is_linked_by_url($url, $uid) ) {
  //Feed was already linked to this user
  loggit(2,"The feed: [$url] was already subscribed to by user: [$uid].");
  $jsondata['status'] = "false";
  $jsondata['description'] = "You already follow that feed.";
  echo json_encode($jsondata);
  exit(1);
}

//Add the feed for this user
$fid = add_feed($url, $uid, FALSE);
mark_feed_as_updated($fid);
loggit(1, "Added feed: [$url] to the database.");


//Log it
loggit(1,"User: [$uid] subscribed to a new feed: [$url].");
$jsondata['fid'] = $fid;

//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['description'] = "Subscribed!";
echo json_encode($jsondata);
return(0);

?>
