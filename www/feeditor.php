<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_page_init.php"?>
<?require_once "$confroot/$includes/net.php";?>
<?

  // See if we have a valid article id or url to get source xml from
  $datestamp = date('YmdHis');
  $mode = "";
  $filename = "";
  $url = "";
  $redirect = "";
  $aid = "";
  $rhost = "";
  if(isset($_REQUEST['aid'])) {
      $aid = trim($_REQUEST['aid']);
  }

  if( !empty($aid) ) {
      $mode = "article";
      $opmldata = get_article_as_opml($aid, $g_uid);
  } else {
      //This wasn't an article edit request, so let's try and pull an external url
      if(isset($_REQUEST['url'])) {
          $url = trim($_REQUEST['url']);
      }
      if( !empty($url) ) {
          $filename = stripText(basename($url), FALSE, "\.");

          //Get opml data and clean it
          $protpos = stripos($url, 'http');
          if( $protpos <> 0 || $protpos === FALSE ) {
              $badurl = true;
          } else {
              /*
              $opmldata = fetchUrl(get_final_url($url));
              $opmldata = stripInvalidXml($opmldata);
              if( !is_outline($opmldata) ) {
                  $badurl = true;
              }
              */
              $opmldata = "";
          }

          //Get side info
          $seenfile = get_recent_file_by_url($g_uid, $url);

          //Set the redirect host for this document
          loggit(3, "DEBUG: Url to open - [".$url."]");
          $lookurl = str_replace('/opml/', '/html/', $url);
          $lookurl = str_replace('.opml', '.html', $lookurl);
          loggit(3, "DEBUG: Redirect url to look for - [".$lookurl."]");
          $rhost = get_redirection_host_name_by_url($lookurl);
          if( empty($rhost) && preg_match('/http.*\.opml/i', $url) ) {
              $nurl = preg_replace('/\.(opml)$/i', '.html', $url);
              $rhost = get_redirection_host_name_by_url($nurl);
              loggit(3, "DEBUG: $nurl");
          }
      }
  }

  //Clean opml
if( !empty($opmldata) ) {
    $opmldata = preg_replace("/\ +\n\n\ +/", "\n\n", $opmldata);
    $opmldata = preg_replace("/\n\ +\n/", "\n\n", $opmldata);
    $opmldata = preg_replace("/[\r\n]\n+/", "\n\n", $opmldata);
    $opmldata = preg_replace("/\r?\n/", "", $opmldata);
    $opmldata = preg_replace("/\n/", "", $opmldata);
    $opmldata = preg_replace("/\'/", "\\\'", $opmldata);
}

  $section = "Editor";
  $tree_location = "Edit Outline";
?>

<?include "$confroot/$templates/$template_html_prehead"?>
<head>
<?include "$confroot/$templates/$template_html_meta"?>
<title><?echo $tree_location?></title>
<?include "$confroot/$templates/$template_html_styles"?>
<?include "$confroot/$templates/$template_html_scripts"?>
<script src="/script/webaudio_tools.js"></script>
<script>
    //Globals
    var mode = '<?echo $mode?>';
    var url = '<?echo $url?>';
    <?if(!empty($aid)) { ?>var aid = '<?echo $aid?>';<?; } else { ?>var aid = false; <?}?>
    var htmlurl = "";
    var title = "";
    var redirect = '<?echo $rhost?>';
    var lasttitle = "";
    var filename = '<?echo $filename?>';
    var ownerName = '<?echo get_user_name_from_uid($g_uid)?>';
    var ownerEmail = '<?echo get_email_from_uid($g_uid)?>';
    var ownerId = '';
    var oldfilename = "";
    var bufilename = '<?echo time()."-".$default_opml_export_file_name;?>';
    var badurl = false;
    <?if( isset($opmldata) && !empty($aid) ) {?>
    var initialOpmlText = '<?echo $opmldata?>';
    <?} else {?>
    var initialOpmlText = initialOpmltext;
    <?}?>
    var includeDisqus = <?if(!isset($seenfile) || $seenfile[0]['disqus'] == 0) { echo "false"; } else { echo "true"; }?>;
    var wysiwygOn = <?if(!isset($seenfile) || $seenfile[0]['wysiwyg'] == 0) { echo "false"; } else { echo "true"; }?>;
    var watchedOutline = <?if(!isset($seenfile) || $seenfile[0]['watched'] == 0) { echo "false"; } else { echo "true"; }?>;
    var lockedOutline = <?if(!isset($seenfile) || $seenfile[0]['locked'] == 0) { echo "false"; } else { echo "true"; }?>;
    var wasLocked = <?if(!isset($seenfile) || $seenfile[0]['locked'] == 0) { echo "false"; } else { echo "true"; }?>;
    var redirectHits = <?if(empty($rhost)) { echo 0; } else { echo get_redirection_hit_count_by_host($rhost); }?>;
    <?if( isset($badurl) ) {?>
    badurl = true;
    <?}?>
    <?include "$confroot/$scripts/editor.js"?>
</script>
</head>
<?include "$confroot/$templates/$template_html_posthead"?>


<body id="bodyEditOutline">
<?//--- Include the logo and menu bar html fragments --?>
<?include "$confroot/$templates/$template_html_logotop"?>
<?include "$confroot/$templates/$template_html_menubar"?>

<?//--- Stuff between the title and content --?>
<?include "$confroot/$templates/$template_html_precontent"?>
<div id="divEditSheetOpen" class="sheet">
    <a class="sheetclose pull-right" href="#"> X </a>
    <div class="openbyurl"><a class="openbyurl" href="#">Open</a> by url or...</div>
    <div class="list-container pre-scrollable">
        <ul class="recentfilesopen"></ul>
    </div>
</div>

<div id="divEditSheetInclude" class="sheet">
    <a class="sheetclose pull-right" href="#"> X </a>
    <div class="openbyurl"><a class="openbyurl" href="#">Include</a> by url or...</div>
    <ul class="templateopen"></ul>
</div>

<div id="divEditSheetImport" class="sheet">
    <a class="sheetclose pull-right" href="#"> X </a>
    <div class="openbyurl"><a class="openbyurl" href="#">Import</a> by url or...</div>
    <ul class="templateopen"></ul>
</div>

<div class="row" id="divEditOutline">
<?if(s3_is_enabled($g_uid) || sys_s3_is_enabled()) {?>
    <div class="divOutlineTitle">
        <input class="rendertitle" checked="checked" type="checkbox" title="Render title and byline in the HTML?" /> <input class="title" placeholder="Title" type="text" />
    </div>
    <div class="loading" style="display:none;"><i class="fa fa-refresh fa-spin"></i> Loading...</div>
    <div class="outlineinfo pull-right"></div>

    <div class="divOutlinerContainer">
        <div id="outliner"></div>
    </div>
<?}else{?>
    <center>You must have S3 enabled on either your server or in your user <a href="<?echo $prefspage?>">prefs</a> to use the editor.</center>
<?}?>
</div>

<div id="divEditorEnclosures" class="dropzone hide">
    <input type="hidden" name="datestamp" class="datestamp" value="<?echo $datestamp?>" />
    <div id="editor_queue"><span id="spnEditorQueueText">Drop file(s) here or press 'esc' to dismiss...</span></div>
    <input type="file" name="file_upload" id="editor_upload" />
</div>

<?//--- Include the footer bar html fragments -----------?>
<?include "$confroot/$templates/$template_html_footerbar"?>
</body>

<?include "$confroot/$templates/$template_html_postbody"?>
</html>