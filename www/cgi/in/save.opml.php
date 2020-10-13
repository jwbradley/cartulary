<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");

// Globals
$jsondata = array();

//debug request
//loggit(3, "DEBUG: ".print_r($_REQUEST, TRUE));

//Check that s3 is enabled
if( !s3_is_enabled($uid) && !sys_s3_is_enabled() ) {
    //Log it
    loggit(2,"User didn't have s3 enabled for opml save: [$uid].");
    $jsondata['status'] = "false";
    $jsondata['description'] = "Configure s3 in the prefs to enable saving.";
    echo json_encode($jsondata);
    exit(1);
}

//Get the title
$title = "";
if ( isset($_REQUEST['title']) ) {
    $title = $_REQUEST['title'];
}

//Opml type
if(isset($_REQUEST['type']) && is_numeric($_REQUEST['type'])) {
    $type = $_REQUEST['type'];
    if( $type == 1 ) loggit(3, "DEBUG: RSS file from editor.");
} else {
    $type = 0;
}

//Render the title?
$rendertitle = TRUE;
if ( isset($_REQUEST['rendertitle']) && $_REQUEST['rendertitle'] == "false" ) {
    $rendertitle = FALSE;
}
loggit(3, "DEBUG: [".$_REQUEST['rendertitle']."]");

//Get the redirect source
$rhost = "";
if ( isset($_REQUEST['redirect']) && !empty($_REQUEST['redirect']) ) {
    $rhost = $_REQUEST['redirect'];
    if($rhost == $system_fqdn) {
        //Log it
        loggit(2,"User tried to set a document redirect to the system FQDN: [$uid|$rhost].");
        $jsondata['status'] = "false";
        $jsondata['description'] = "You can't use that host name as a redirect.";
        echo json_encode($jsondata);
        exit(1);
    }
}

//Get disqus bool
$disqus = FALSE;
if ( isset($_REQUEST['disqus']) && $_REQUEST['disqus'] == "true" ) {
    $disqus = TRUE;
}

//Get wysiwyg bool
$wysiwyg = FALSE;
if ( isset($_REQUEST['wysiwyg']) && $_REQUEST['wysiwyg'] == "true" ) {
    $wysiwyg = TRUE;
}

//Is this an article that was edited
$aid = "";
if ( isset($_REQUEST['aid']) && $_REQUEST['aid'] != "false" ) {
    $aid = $_REQUEST['aid'];
}

//Do we need to overwrite the existing article
$articleoverwrite = "";
if ( isset($_REQUEST['articleoverwrite']) && $_REQUEST['articleoverwrite'] != "false" ) {
    $articleoverwrite = $_REQUEST['articleoverwrite'];
}

//Get watched bool
$watched = FALSE;
if ( isset($_REQUEST['watched']) && $_REQUEST['watched'] == "true" ) {
    $watched = TRUE;
}

//Get locked bool
$locked = FALSE;
if ( isset($_REQUEST['locked']) && $_REQUEST['locked'] == "true" ) {
    $locked = TRUE;
}

//Get private bool
$private = FALSE;
if ( isset($_REQUEST['private']) && $_REQUEST['private'] == "true" ) {
    $private = TRUE;
}

//Get private token
$privtoken = "";
if ( isset($_REQUEST['privtoken']) && !empty($_REQUEST['privtoken']) ) {
    $privtoken = $_REQUEST['privtoken'];
}

//Get variables if any
$variables = [];
if ( isset($_REQUEST['variables']) && !empty($_REQUEST['variables'])) {
    $variables = $_REQUEST['variables'];
    loggit(3, print_r($variables, TRUE));
}
$templateid = "";
if ( isset($_REQUEST['templateid']) && !empty($_REQUEST['templateid'])) {
    $templateid = $_REQUEST['templateid'];
    loggit(3, "Template id: [$templateid]");
}

//Get a template name if set
$templatename = "";
if ( isset($_REQUEST['templatename']) && !empty($_REQUEST['templatename']) ) {
    $templatename = $_REQUEST['templatename'];
    loggit(3, "Template name: [$templatename]");
}


//If the outline is private make sure we have a token
if( $private && empty($privtoken) ) {
    $privtoken = time().random_gen(64);
}

//Make sure we have a filename to use
if ( isset($_REQUEST['filename']) ) {
    $filename = $_REQUEST['filename'];
} else {
    //Log it
    loggit(2,"No filename was set for this opml save.");
    $jsondata['status'] = "false";
    $jsondata['description'] = "No filename given.";
    echo json_encode($jsondata);
    exit(1);
};

//Do we have an old filename? If so, this is a file name change
$oldfilename = "";
if ( isset($_REQUEST['oldfilename']) ) {
    $oldfilename = $_REQUEST['oldfilename'];
};

//Get the opml data
if ( isset($_REQUEST['opml']) ) {
    $opml = $_REQUEST['opml'];
} else {
    //Log it
    loggit(2,"No opml data was set for this opml save.");
    $jsondata['status'] = "false";
    $jsondata['description'] = "No opml data given.";
    echo json_encode($jsondata);
    exit(1);
};


//We're going to need the S3 url of this file to continue
$s3url = get_s3_url($uid, "/opml/", $filename);

//Before we do anything we need to confirm that this user id has permissions to update this file.  If its a new
//file, we need to allow for that
if( !empty($templateid) ) {
    if( !user_can_edit_recent_file_by_id($uid, $templateid) ) {
        //Log it
        loggit(2,"User: [$uid] tried to save variables for a template id they don't own.");
        $jsondata['status'] = "false";
        $jsondata['description'] = "You don't have permission to work with this template.";
        echo json_encode($jsondata);
        exit(1);
    }
}

//Put the opml file in S3
$s3info = get_s3_info($uid);
if(!$private) {
    $s3res = putInS3(gzencode($opml), $filename, $s3info['bucket']."/opml", $s3info['key'], $s3info['secret'], array(
        'Content-Type'      => 'text/xml',
        'Content-Encoding'  => 'gzip'
    ), $private);
    if(!$s3res) {
        loggit(2, "Could not create S3 file: [$filename] for user: [$uid].");
        loggit(3, "Could not create S3 file: [$filename] for user: [$uid].");
        //Log it
        $jsondata['status'] = "false";
        $jsondata['description'] = "Error writing to S3.";
        echo json_encode($jsondata);
        exit(1);
    } else {
        loggit(1, "Wrote opml to S3 at url: [$s3url].");
    }
} else {
    //Delete the opml file from S3 if the outline is marked private since it's not even usable
    loggit(3, "Deleting private outline OPML in S3: [".$s3info['bucket']."/opml"." | ".$filename."]");
    $s3res = deleteFromS3($filename, $s3info['bucket']."/opml", $s3info['key'], $s3info['secret']);
}

//Put the opml content in IPFS
$opmlhash = add_content_to_ipfs($opml);
//loggit(3, "DEBUG: [".print_r($opmlhash, TRUE)."]");

//Assemble an old url if we had an old filename
$s3oldurl = "";
if ( !empty($oldfilename) ) {
    $s3oldurl = get_s3_url($uid, "/opml/", $oldfilename);
};

//Put the html file in S3
$htmlfilename = str_replace('.opml', '.html', $filename);
$s3htmlauthurl = "";
$s3htmlurl = get_s3_url($uid, "/html/", $htmlfilename);
$htmldata = process_opml_to_html($opml, $title, $uid, $disqus, $s3url, $rendertitle, $s3htmlurl);
$s3res = putInS3(gzencode($htmldata), $htmlfilename, $s3info['bucket']."/html", $s3info['key'], $s3info['secret'], array(
    'Content-Type'      => 'text/html',
    'Cache-Control'     => 'max-age=31556926',
    'Content-Encoding'  => 'gzip'
), $private);
if(!$s3res) {
    loggit(2, "Could not create S3 file: [$htmlfilename] for user: [$uid].");
    loggit(3, "Could not create S3 file: [$htmlfilename] for user: [$uid].");
    //Log it
    $jsondata['status'] = "false";
    $jsondata['description'] = "Error writing HTML to S3.";
    echo json_encode($jsondata);
    exit(1);
} else {
    $s3html = get_s3_url($uid, "/html/", $htmlfilename);
    loggit(1, "Wrote html to S3 at url: [$s3html].");

    //If the HTML file was private we need to create an authenticated url for accessing it that lasts for the maximum time of 7 days
    if($private) {
        //$s3htmlauthurl = get_s3_authenticated_url($s3info['key'], $s3info['secret'], $s3info['bucket'], "html/$htmlfilename", 10080);
    }

}

//Put the html content in ipfs
$htmlhash = add_content_to_ipfs($htmldata);

//Is this an RSS file type?
$s3json = "";
if( $type == 1 ) {
    //Put the RSS in S3
    $rssfilename = str_replace('.opml', '.xml', $filename);
    $s3rssurl = get_s3_url($uid, "/rss/", $rssfilename);
    $rssdata = convert_opml_to_rss($opml, $uid);
    if($rssdata == FALSE || (is_numeric($rssdata) && $rssdata < 0)) {
        loggit(2, "RSS Error code: [$rssdata]");
        $jsondata['status'] = "false";
        if($rssdata == -5) {
            $jsondata['description'] = "One of the enclosures in this feed returned a bad response code. Make sure the url for the enclosure file is valid.";
        } else
        if($rssdata == -4) {
            $jsondata['description'] = "One of the items has both a blank title and description. At least one is required.";
        } else
        if($rssdata == -3) {
            $jsondata['description'] = "A non-specific exception occured while building the rss feed.";
        } else
        if($rssdata == -2) {
            $jsondata['description'] = "There were no 'item' nodes found while building the rss feed.";
        } else {
            $jsondata['description'] = "An unknown error occurred during OPML to RSS conversion.";
        }

        echo json_encode($jsondata);
        exit(1);
    }
    $s3res = putInS3(gzencode($rssdata), $rssfilename, $s3info['bucket']."/rss", $s3info['key'], $s3info['secret'], array(
        'Content-Type'      => 'application/rss+xml',
        'Content-Encoding'  => 'gzip'
    ), $private);
    if(!$s3res) {
        loggit(2, "Could not create S3 file: [$rssfilename] for user: [$uid].");
        loggit(3, "Could not create S3 file: [$rssfilename] for user: [$uid].");
        //Log it
        $jsondata['status'] = "false";
        $jsondata['description'] = "Error writing rss to S3.";
        echo json_encode($jsondata);
        exit(1);
    } else {
        $s3rss = get_s3_url($uid, "/rss/", $rssfilename);
        loggit(3, "Wrote rss to S3 at url: [$s3rss].");
        //set_s3_bucket_cors($s3info['key'], $s3info['secret'], $s3info['bucket']);
    }

}

//Get the current file details
if( $s3oldurl != $s3url && !empty($s3oldurl) && !empty($s3url) ) {
    $cfile = get_recent_file_by_url($uid, $s3oldurl, TRUE);
} else {
    $cfile = get_recent_file_by_url($uid, $s3url, TRUE);
}

//Update the recent file version table
$temp_opml = preg_replace('/\<dateModified\>.*\<\/dateModified\>/', '', $opml);
$temp_prevopml = preg_replace('/\<dateModified\>.*\<\/dateModified\>/', '', $cfile['content']);
if( $temp_opml != $temp_prevopml && !empty($cfile['content']) && !empty($opml) && !empty($temp_opml) && !empty($temp_prevopml) ) {
    loggit(3, "DEBUG: Editor file content changed. Saving old version in version table.");
    add_recent_file_version($uid, $s3url, $cfile['title'], $cfile['content'], $cfile['type'], $cfile['disqus'], $cfile['wysiwyg'], $cfile['watched'], $cfile['articleid'], $cfile['locked'], $cfile['ipfshash'], $private, "", $templatename);
} else {
    loggit(3, "DEBUG: Editor file content not changed.");
}

//Update timestamp on source template when generating a new file from it so that it doesn't get pushed way down
if( !empty($templateid) ) {
    touch_recent_file_by_id($templateid);
}

//Update recent file table
$rid = update_recent_file($uid, $s3url, $title, $opml, $type, $s3oldurl, $disqus, $wysiwyg, $watched, $aid, $locked, $opmlhash, $private, $privtoken, $templatename);
loggit(3, "DEBUG: Recent file id is [$rid].");

//Was this an edited article content request
if( $articleoverwrite && !empty($aid) ) {
    add_edited_content_to_article($aid, $uid, convert_opml_to_html($opml));
    set_article_title($aid, $title);
}

//Go ahead and put in the urls we saved
$jsondata['url'] = $s3url;
$jsondata['html'] = $s3html;
$jsondata['htmlauth'] = $s3htmlauthurl;
if(!empty($opmlhash)) {
    $jsondata['ipfs']['opml'] = $opmlhash;
}
if(!empty($htmlhash)) {
    $jsondata['ipfs']['html'] = $htmlhash;
}

//Extract and add watched urls if this is a watched outline
remove_watched_urls_by_file_id($rid);
if($watched) {
    $includes = get_includes_from_outline($opml);
    foreach( $includes as $include ) {
        $u = get_watched_url_by_url($include);
        if( empty($u) ) {
            $u['lastmodified'] = "";
            $u['content'] = "";
        }
        add_watched_url($rid, $include, $u['lastmodified'], $u['content']);
    }
}

//Update the redirector table
if( !empty($rhost) ) {
    //Let's not clobber existing redirects
    $erurl = get_redirection_url_by_host_name($rhost);
    if( !empty($erurl) && $erurl != $s3htmlurl ) {
        $erurl = str_replace('.html', '.opml', $erurl);
        $erurl = str_replace('/html/', '/opml/', $erurl);

        //Log it
        loggit(2,"Attempted redirection hostname already exists: [$rhost].");
        $jsondata['status'] = "false";
        $jsondata['duration'] = 20;
        $jsondata['description'] = "Attempted redirection hostname already in use by <a target='_blank' href=\"/editor?url=$erurl\">this</a> outline.";
        echo json_encode($jsondata);
        exit(1);
    }

    //Update the redirection table
    update_redirection_host_name_by_url($s3html, $rhost, $uid);

    //Parse out the url to find the bucket and key names
    if( stripos($rhost, 'http') !== 0) {
        $rhost = 'http://'.$rhost;
    }
    $purl = parse_url($rhost);

    //See if the host of this url is a bucket
    $buckets = get_s3_buckets($s3info['key'], $s3info['secret']);
    $search_array = array_map('strtolower', $buckets);
    if( in_array(strtolower($purl['host']), $search_array) ) {
        //Create the index stub that will redirect via a meta-refresh
        $rfile = create_short_url_file($s3html, $uid);

        loggit(3, "DEBUG: [".print_r($purl, TRUE));
        loggit(3, "DEBUG:  ".print_r($search_array, TRUE));

        //Pull out the last part of the path to use as a file stub name
        if( !empty($purl['path']) ) {
            $pathFragments = explode('/', rtrim($purl['path'], '/'));
            loggit(3, "DEBUG: ".print_r($pathFragments, TRUE));
            $pend = trim(end($pathFragments), '/');
            array_shift($pathFragments);
            array_pop($pathFragments);
            $path = implode('/', $pathFragments);
            loggit(3, "DEBUG: path is [$path].");
        } else {
            $path = "";
            $pend = "index.html";
        }

        loggit(3, "DEBUG: ".print_r($pathFragments, TRUE));

        $s3path = rtrim($purl['host']."/".trim($path, '/'), '/');
        loggit(3, "DEBUG: s3path is [$s3path].");

        //Now put the index stub into s3
        $s3res = putInS3(gzencode($rfile), $pend, $s3path, $s3info['key'], $s3info['secret'], array(
            'Content-Type'      => 'text/html',
            'Content-Encoding'  => 'gzip'
        ));
        if(!$s3res) {
            loggit(2, "Could not create S3 file: [index.html] for user: [$uid].");
            loggit(3, "Could not create S3 file: [index.html] for user: [$uid].");
            //Log it
            $jsondata['status'] = "false";
            $jsondata['description'] = "Error writing redirection stub to S3.";
            echo json_encode($jsondata);
            exit(1);
        } else {
            $redhtml = 'http://'.$s3path."/".$pend;
            loggit(3, "DEBUG: Wrote html to S3 at url: [$redhtml].");
        }
    }
} else {
    remove_redirection_by_url($s3html, $uid);
}

//If variables were present, save them
if( $type == 6 && empty($templateid) && !empty($rid) ) {
    $templateid = $rid;
}
if( !empty($variables) && !empty($templateid) ) {
    foreach( $variables as $variable) {
        update_recent_file_variable($uid, $templateid, $variable['name'], $variable['value']);
    }
}

//Log it
loggit(3,"Saved: [$filename] to S3 for user: [$uid]. ");

//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['templateid'] = $templateid;
$jsondata['privtoken'] = $privtoken;
$jsondata['description'] = "File saved to S3.";
echo json_encode($jsondata);

return(0);