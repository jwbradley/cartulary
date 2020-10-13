<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_page_init.php" ?>
<?
//Get this user's totp seed value
$utps16 = get_totp_seed_from_uid($uid);

$section = "Prefs";
$tree_location = "Preferences";
?>

<? include "$confroot/$templates/$template_html_prehead" ?>
<head>
    <? include "$confroot/$templates/$template_html_meta" ?>
    <title><? echo $tree_location ?></title>
    <? include "$confroot/$templates/$template_html_styles" ?>
    <? include "$confroot/$templates/$template_html_scripts" ?>
    <script>
        <?include "$confroot/$scripts/prefs.js"?>
    </script>
</head>
<? include "$confroot/$templates/$template_html_posthead" ?>

<? //--- The body tag and anything else needed ---?>
<? include "$confroot/$templates/$template_html_bodystart" ?>

<? //--- Include the logo and menu bar html fragments --?>
<? include "$confroot/$templates/$template_html_logotop" ?>
<? include "$confroot/$templates/$template_html_menubar" ?>

<? //--- Stuff between the title and content --?>
<? include "$confroot/$templates/$template_html_precontent" ?>

<div class="row" id="divPrefs">
    <div id="divPrefsInner">
        <? if (!empty($cg_paypal_button) && $cg_paypal_enabled && $prefs['payment_made'] == 0) { ?>
            <div id="divPayments">
                <h3>Pitch In!</h3>

                <p>This server operator has payments enabled. That's their way of asking nicely for you to pitch in and
                    help cover
                    the monthly costs of running this server. Please consider doing so. Thanks!</p>
                <? echo str_replace('[$$PAYPAL_BUTTON_ID$$]', $cg_paypal_button_id, $cg_paypal_button) ?>
            </div>
        <? } ?>

        <div id="divBookmarklets">
            <h3>Bookmarklets</h3>
            <ul>
                <li id="liCartBookmarklet"><a id="aCartBookmarklet"
                                              href="<? echo trim(sprintf($bookmarklet_url, $system_url)) ?>">Cartulize</a>
                </li>
                <li id="liBlogBookmarklet"><a id="aBlogBookmarklet"
                                              href="<? echo trim(sprintf($bookmarklet_blog_url, $system_url)) ?>">Microblog</a>
                </li>
                <li id="liSubscribeBookmarklet"><a id="aSubscribeBookmarklet"
                                                   href="<? echo trim(sprintf($bookmarklet_subscribe, $system_url)) ?>">Subscribe</a>
                    [beta]
                </li>
            </ul>
        </div>


        <div id="divSetpw" class="control-group">
            <form name="setpw" id="frmSetpw" method="POST" action="/cgi/auth/setpassword">
                <fieldset>
                    <h3>Change Password</h3>
                    <ul>
                        <li>Old: <input name="password0" class="urlinput" type="password" value=""/></li>
                        <li>New: <input name="password1" class="urlinput" type="password" value=""/></li>
                        <li>Confirm: <input name="password2" class="urlinput" type="password" value=""/></li>
                        <div class="buttonline">
                            <img class="imgSpinner imgSpinnerPwd" src="/images/spinner.gif"/>
                            <button id="btnPwdSubmit" class="btn" type="submit">Change</button>
                        </div>
                    </ul>
                </fieldset>
            </form>
        </div>

        <form name="prefs" id="frmPrefs" method="POST" action="/cgi/auth/setprefs">
            <fieldset>
                <div id="divPref" class="control-group">

                    <div class="divPrefPane">
                        <div class="divPrefSubmit pull-right divPrefMe"><img class="imgSpinner imgSpinnerSub"
                                                                             src="/images/spinner.gif"/><span
                                    class="message"></span>
                            <button id="btnPrefSubmit" class="btn btn-success" type="submit">Save</button>
                        </div>
                        <h3>Me</h3>
                        <ul>
                            <div class="control-group">
                                <li>My name is: <input name="myname" class="urlinput" type="text"
                                                       value="<? echo $username ?>"/></li>
                                <li>My email address is: <input name="myemail" class="urlinput" type="text"
                                                                value="<? echo get_email_from_uid($uid) ?>"/></li>
                                <li>My homepage url is: <input name="homepagelink" class="urlinput" type="text"
                                                               value="<? echo $prefs['homepagelink'] ?>"/></li>
                                <li>The url of my avatar pic is: <input name="avatarurl" class="urlinput" type="text"
                                                                        value="<? echo $prefs['avatarurl'] ?>"/>
                                    <? if ($device != "ipad" && $device != "iphone" && $device != "wphone") { ?><br/>
                                    or
                                    <button id="btnUploadAvatar" type="button" class="btn">Upload One</button>
                                    <img class="imgSpinner imgSpinnerAvatar" src="/images/spinner.gif"/></li>
                                <? } ?>
                                <li style="padding-top:10px;">My timezone is:
                                    <select name="timezone" class="tzinput">
                                        <?
                                        $tzlist = timezone_identifiers_list();
                                        foreach ($tzlist as $tz) {
                                            ?>
                                            <option
                                            value="<? echo $tz ?>" <? if ($prefs['timezone'] == $tz) echo 'selected="selected"'; ?>><? echo $tz ?></option><?
                                        }
                                        ?>
                                    </select>
                                </li>
                                <li class="privacy"><label class="checkbox inline"><input name="hideme"
                                                                                          type="checkbox" <? if ($prefs['hideme'] == 1) echo "checked "; ?>/>
                                        I don't want to be found in directory searches.</label></li>
                                <li class="privacy"><label class="checkbox inline"><input name="ipinfotracker"
                                                                                          type="checkbox" <? if ($prefs['ipinfotracker'] == 1) echo "checked "; ?>/>
                                        I want to see my public IP address <a target="_blank" href="https://ip.nf/<?echo $_SERVER['REMOTE_ADDR']?>.json">geolocation data</a> in real-time.</label></li>
                                <li class="privacy"><label class="checkbox inline"><input name="sessioncookies"
                                                                                          type="checkbox" <? if ($prefs['sessioncookies'] == 1) echo "checked "; ?>/>
                                        Re-prompt for login every time I open the browser.</label></li>
                                <li class="privacy"><label class="checkbox inline"><input name="usetotp"
                                                                                          type="checkbox" <? if ($prefs['usetotp'] == 1) echo "checked "; ?>/>
                                        I want to use two-factor authentication to log in.</label></li>
                                <? if ($prefs['usetotp'] == 1 && !empty($utps16)) {
                                    $b32 = new Base32;
                                    $utps32 = $b32->encode($utps16, true); ?>
                                    <br/>Google Authenticator:
                                    <br/><img src="/cgi/out/qrcode.totp?ts=" .time()/> <? echo $utps32 ?>
                                    <br/>Current code:
                                    <large><? echo calculate_totp($utps16) ?></large>
                                <? } ?>
                        </ul>
                    </div>
                </div>

                <div class="divPrefPane">
                    <div class="divPrefSubmit pull-right divPrefAmazon"><img class="imgSpinner imgSpinnerSub"
                                                                             src="/images/spinner.gif"/><span
                                class="message"></span>
                        <button id="btnPrefSubmit" class="btn btn-success" type="submit">Save</button>
                    </div>
                    <h3>S3 Primary Storage <small>Holds HTML, OPML and other framework files.</small></h3>
                    <ul>
                        <div class="control-group">
                            <li>My Amazon S3 key is <input name="s3key" type="text" value="<? echo $prefs['s3key'] ?>"/>
                            </li>
                        </div>
                        <div class="control-group">
                            <li>My Amazon S3 secret is <input name="s3secret" type="text"
                                                              value="<? echo $prefs['s3secret'] ?>"/></li>
                        </div>
                        <div class="control-group">
                            <li>I want to store my feeds in an Amazon S3 bucket named: <input id="s3bucket"
                                                                                              name="s3bucket"
                                                                                              type="text"
                                                                                              value="<? echo $prefs['s3bucket'] ?>"/>
                            </li>
                            <li>with a DNS cname of: <input id="s3cname" name="s3cname" type="text"
                                                            value="<? echo $prefs['s3cname'] ?>"/></li>
                            <ul id="s3feednames">
                                <li>Article feed will be: <a id="aS3curlA" href="#"><span id="s3curlA"> </span></a></li>
                                <li>Article opml will be: <a id="aS3curlC" href="#"><span id="s3curlC"> </span></a></li>
                                <li>Microblog feed will be: <a id="aS3curlB" href="#"><span id="s3curlB"> </span></a>
                                </li>
                                <li>Microblog opml will be: <a id="aS3curlD" href="#"><span id="s3curlD"> </span></a>
                                </li>
                            </ul>
                        </div>
                    </ul>
                </div>

                <div class="divPrefPane">
                    <div class="divPrefSubmit pull-right divPrefAmazon"><img class="imgSpinner imgSpinnerSub"
                                                                             src="/images/spinner.gif"/><span
                                class="message"></span>
                        <button id="btnPrefSubmit" class="btn btn-success" type="submit">Save</button>
                    </div>
                    <h3>S3 Asset Storage <small>Holds assets like images, audio, video, etc.</small></h3>
                    <ul>
                        <div class="control-group">
                            <li>API Key: <input name="s3key_assets" type="text" value="<? echo $prefs['s3key_assets'] ?>"/>
                            </li>
                        </div>
                        <div class="control-group">
                            <li>API Secret: <input name="s3secret_assets" type="text"
                                                              value="<? echo $prefs['s3secret_assets'] ?>"/></li>
                        </div>
                        <div class="control-group">
                            <li>Bucket for storing assets: <input id="s3bucket_assets" name="s3bucket_assets"
                                                                                              type="text"
                                                                                              value="<? echo $prefs['s3bucket_assets'] ?>"/>
                            </li>
                            <li>Use a DNS cname of: <input id="s3cname_assets" name="s3cname_assets" type="text"
                                                            value="<? echo $prefs['s3cname_assets'] ?>"/></li>
                        </div>
                        <div class="control-group">
                            <li>API Endpoint: <input name="s3endpoint_assets" type="text" placeholder="http://s3.wasabisys.com or blank for Amazon" class="urlinput"
                                                   value="<? echo $prefs['s3endpoint_assets'] ?>"/></li>
                        </div>
                        <div class="control-group">
                            <li>API Region: <input name="s3region_assets" type="text" placeholder="us-east-1 or blank for Amazon"
                                                     value="<? echo $prefs['s3region_assets'] ?>"/></li>
                        </div>
                    </ul>
                </div>

                <div class="divPrefPane">
                    <div class="divPrefSubmit pull-right divPrefRiver"><img class="imgSpinner imgSpinnerSub"
                                                                            src="/images/spinner.gif"/><span
                                class="message"></span>
                        <button id="btnPrefSubmit" class="btn btn-success" type="submit">Save</button>
                    </div>
                    <h3>Subscriptions</h3>
                    <ul>
                        <li class="privacy"><label class="checkbox inline"><input name="hidesublist"
                                                                                  type="checkbox" <? if ($prefs['hidesublist'] == 1) echo "checked "; ?>/>
                                I don't want my subscription list in my social outline.</label></li>
                        <li class="privacy"><label class="checkbox inline"><input id="chkOpenSubs" name="opensubs"
                                                                                  type="checkbox" <? if ($prefs['opensubs'] == 1) echo "checked "; ?>/>
                                Allow open
                                subscriptions. <? if ($prefs['opensubs'] == 1) echo "<span>(Use <a href='$system_url.$opensubscribepage?u=$g_uid'> this</a> url for open subs page.)</span>"; ?>
                            </label></li>
                    </ul>
                </div>

                <div class="divPrefPane">
                    <div class="divPrefSubmit pull-right divPrefRiver"><img class="imgSpinner imgSpinnerSub"
                                                                            src="/images/spinner.gif"/><span
                                class="message"></span>
                        <button id="btnPrefSubmit" class="btn btn-success" type="submit">Save</button>
                    </div>
                    <h3>River</h3>
                    <ul>
                        <li>I want: <input id="rivercolumns" class="hourinput" name="rivercolumns" type="text"
                                           value="<? echo $prefs['rivercolumns'] ?>"/> columns on my river page. [0 for
                            auto]
                        </li>
                        <li>I want: <input id="riverhours" class="hourinput" name="riverhours" type="text"
                                           value="<? echo $prefs['riverhours'] ?>"/> hours worth of items listed in my
                            river.
                        </li>
                        <li>But, never show more than: <input class="hourinput" name="maxriversize" type="text"
                                                              value="<? echo $prefs['maxriversize'] ?>"/> items at a
                            time.
                        </li>
                        <li>And, never show more than: <input class="hourinput" name="maxriversizemobile" type="text"
                                                              value="<? echo $prefs['maxriversizemobile'] ?>"/> items
                            while on mobile.
                        </li>
                        <li><label class="checkbox inline"><input name="riverheadlinecart"
                                                                  type="checkbox" <? if ($prefs['riverheadlinecart'] == 1) echo "checked "; ?>/>
                                I want headlines to cartulize by default in the river.</label></li>
                        <li><label class="checkbox inline"><input name="cartinriver"
                                                                  type="checkbox" <? if ($prefs['cartinriver'] == 1) echo "checked "; ?>/>
                                Don't use in-river cartulizing while on mobile.</label></li>
                        <li><label class="checkbox inline"><input name="mobilehidebigpics"
                                                                  type="checkbox" <? if ($prefs['mobilehidebigpics'] == 1) echo "checked "; ?>/>
                                Hide large image/video enclosures in the river when on mobile.</label></li>
                        <li><label class="checkbox inline"><input name="mobilehidepics"
                                                                  type="checkbox" <? if ($prefs['mobilehidepics'] == 1) echo "checked "; ?>/>
                                Hide body images/videos in the river when on mobile.</label></li>
                        <li><label class="checkbox inline"><input name="fulltextriver"
                                                                  type="checkbox" <? if ($prefs['fulltextriver'] == 1) echo "checked "; ?>/>
                                I want to see the full text of each item.</label></li>
                        <li><label class="checkbox inline"><input name="collapseriver"
                                                                  type="checkbox" <? if ($prefs['collapseriver'] == 1) echo "checked "; ?>/>
                                Collapse similar items into a threaded view.</label></li>
                        <li class="privacy"><label class="checkbox inline"><input id="chkPubriver" name="publicriver"
                                                                                  type="checkbox" <? if ($prefs['publicriver'] == 1) echo "checked "; ?>/>
                                I want a public river.</label></li>
                        <div id="divPubriver" class="hide">
                            <li>My public river's file name should be: <input id="pubriverfile" name="pubriverfile"
                                                                              type="text"
                                                                              value="<? echo $prefs['pubriverfile'] ?>"/>
                            </li>
                            <li>My custom public river template url is: <input id="pubrivertemplate" class="urlinput"
                                                                               name="pubrivertemplate" type="text"
                                                                               value="<? echo $prefs['pubrivertemplate'] ?>"/>
                            </li>
                            <li>My public river title should be: <input id="pubrivertitle" class="urlinput"
                                                                        name="pubrivertitle" type="text"
                                                                        value="<? echo $prefs['pubrivertitle'] ?>"/>
                            </li>
                        </div>
                    </ul>
                </div>

                <div class="divPrefPane">
                    <div class="divPrefSubmit pull-right divPrefMicroblog"><img class="imgSpinner imgSpinnerSub"
                                                                                src="/images/spinner.gif"/><span
                                class="message"></span>
                        <button id="btnPrefSubmit" class="btn btn-success" type="submit">Save</button>
                    </div>
                    <h3>Microblog</h3>
                    <ul>
                        <li>My microblog title should be: <input name="microblogtitle" class="urlinput" type="text"
                                                                 value="<? echo $prefs['microblogtitle'] ?>"/></li>
                        <li>I want my microblog filename to be: <input id="mbfilename" name="mbfilename" type="text"
                                                                       value="<? echo $prefs['mbfilename'] ?>"/></li>
                        <li>Microblog archives should use the stylesheet located at: <input name="mbarchivecss"
                                                                                            class="urlinput" type="text"
                                                                                            value="<? echo $prefs['mbarchivecss'] ?>"/>
                            (url)
                        </li>
                        <div class="control-group">
                            <li>I'd rather use an external linkblog located at: <input name="linkblog" class="urlinput"
                                                                                       type="text"
                                                                                       value="<? echo $prefs['linkblog'] ?>"/>
                                (url)
                            </li>
                        </div>
                        <li><label class="checkbox inline"><input name="mblinkhome"
                                                                  type="checkbox" <? if ($prefs['mblinkhome'] == 1) echo "checked "; ?>/>
                                I want my microblog RSS link element to point to my homepage.</label></li>
                        <li class="privacy"><label class="checkbox inline"><input name="publicrss"
                                                                                  type="checkbox" <? if ($prefs['publicrss'] == 1) echo "checked "; ?>/>
                                I want my microblog RSS feed to be private.</label></li>
                        <li><label class="checkbox inline"><input name="mbreturnhome"
                                                                  type="checkbox" <? if ($prefs['mbreturnhome'] == 1) echo "checked "; ?>/>
                                I want to go back to the home page after posting.</label></li>
                    </ul>
                </div>

                <div class="divPrefPane">
                    <div class="divPrefSubmit pull-right divPrefArticles"><img class="imgSpinner imgSpinnerSub"
                                                                               src="/images/spinner.gif"/><span
                                class="message"></span>
                        <button id="btnPrefSubmit" class="btn btn-success" type="submit">Save</button>
                    </div>
                    <h3>Articles</h3>
                    <ul>
                        <li>My article archive title should be: <input name="cartularytitle" class="urlinput"
                                                                       type="text"
                                                                       value="<? echo $prefs['cartularytitle'] ?>"/>
                        </li>
                        <li>I want my article archive feed filename to be: <input id="cartfilename" name="cartfilename"
                                                                                  type="text"
                                                                                  value="<? echo $prefs['cartfilename'] ?>"/>
                        </li>
                        <li class="privacy">My private article saving token is: <b style="color:black"><? echo $prefs['carttoken'] ?></b>
                            <button id='newCartToken'>New Token</button>
                        <li><label class="checkbox inline"><input name="tweetcart"
                                                                  type="checkbox" <? if ($prefs['tweetcart'] == 1) echo "checked "; ?>/>
                                I want the articles I save to be tweeted.</label></li>
                        <li><label class="checkbox inline"><input name="shortcart"
                                                                  type="checkbox" <? if ($prefs['shortcart'] == 1) echo "checked "; ?>/>
                                I want articles that I cartulize to have their url's shortened also.</label></li>
                        <li class="privacy"><input name="publicdefault"
                                                   type="checkbox" <? if ($prefs['publicdefault'] == 1) echo "checked "; ?>/>
                            I want my article archive RSS feed to be private.</label></li>
                        <li class="privacy"><input name="publicopml"
                                                   type="checkbox" <? if ($prefs['publicopml'] == 1) echo "checked "; ?>/>
                            I want my article archive OPML file to be private.</label></li>
                        <li><label class="checkbox inline"><input name="staticarticles"
                                                                  type="checkbox" <? if ($prefs['staticarticles'] == 1) echo "checked "; ?>/>
                                I want a static version of each article stored in S3.</label></li>
                        </li>
                    </ul>
                </div>

                <div class="divPrefPane">
                    <div class="divPrefSubmit pull-right divPrefMicroblog"><img class="imgSpinner imgSpinnerSub"
                                                                                src="/images/spinner.gif"/><span
                                class="message"></span>
                        <button id="btnPrefSubmit" class="btn btn-success" type="submit">Save</button>
                    </div>
                    <h3>Editor</h3>
                    <ul>
                        <li>My disqus shortname is: <input name="disqus_shortname" class="urlinput" type="text"
                                                           value="<? echo $prefs['disqus_shortname'] ?>"/></li>
                        <li>I want editor files saved in this bucket: <input name="editorbucket" class="urlinput"
                                                                             type="text"
                                                                             value="<? echo $prefs['editorbucket'] ?>"/>
                        </li>
                    </ul>
                </div>

                <div class="divPrefPane">
                    <div class="divPrefSubmit pull-right divPrefEmail"><img class="imgSpinner imgSpinnerSub"
                                                                            src="/images/spinner.gif"/><span
                                class="message"></span>
                        <button id="btnPrefSubmit" class="btn btn-success" type="submit">Save</button>
                    </div>
                    <h3>Email</h3>
                    <ul>
                        <li>My IMAP email address is: <input name="imap_email" class="urlinput" type="text"
                                                             value="<? echo $prefs['imap_email'] ?>"/></li>
                        <li>My IMAP server address is: <input name="imap_server" id="txtImapServer" class="urlinput"
                                                              type="text" value="<? echo $prefs['imap_server'] ?>"/>
                        </li>
                        <li class="privacy"><label class="checkbox inline"><input name="imap_secure" id="chkImapSecure"
                                                                                  type="checkbox" <? if ($prefs['imap_secure'] == 1) echo "checked "; ?>/>
                                My IMAP server uses SSL/TLS.</label></li>
                        <li>My IMAP server port is: <input name="imap_port" id="txtImapPort" class="urlinput"
                                                           type="text" value="<? if (empty($prefs['imap_port'])) {
                                if ($prefs['imap_secure'] == 1) {
                                    echo "993";
                                } else {
                                    echo "143";
                                }
                            } else {
                                echo $prefs['imap_port'];
                            } ?>"/></li>
                        <li>My IMAP username is: <input name="imap_username" class="urlinput" type="text"
                                                        value="<? echo $prefs['imap_username'] ?>"/></li>
                        <li>My IMAP password is: <input name="imap_password" class="urlinput" type="text"
                                                        value="<? echo $prefs['imap_password'] ?>"/></li>
                        <li>The IMAP folder I want to import from is: <input name="imap_folder" class="urlinput"
                                                                             type="text"
                                                                             value="<? echo $prefs['imap_folder'] ?>"/>
                        </li>
                        <hr>
                        <li>My SMTP server address is: <input name="smtp_server" id="txtSmtpServer" class="urlinput"
                                                              type="text" value="<? echo $prefs['smtp_server'] ?>"/>
                        </li>
                        <li class="privacy"><label class="checkbox inline"><input name="smtp_secure" id="chkSmtpSecure"
                                                                                  type="checkbox" <? if ($prefs['smtp_secure'] == 1) echo "checked "; ?>/>
                                My SMTP server uses SSL/TLS.</label></li>
                        <li>My SMTP server port is: <input name="smtp_port" id="txtSmtpPort" class="urlinput"
                                                           type="text" value="<? if (empty($prefs['smtp_port'])) {
                                if ($prefs['smtp_secure'] == 1) {
                                    echo "465";
                                } else {
                                    echo "25";
                                }
                            } else {
                                echo $prefs['smtp_port'];
                            } ?>"/></li>
                    </ul>
                </div>

                <div class="divPrefPane">
                    <div class="divPrefSubmit pull-right divPrefLinks"><img class="imgSpinner imgSpinnerSub"
                                                                            src="/images/spinner.gif"/><span
                                class="message"></span>
                        <button id="btnPrefSubmit" class="btn btn-success" type="submit">Save</button>
                    </div>
                    <h3>Links</h3>
                    <ul>
                        <li>I want analytics code on my pages and links:<br/><textarea name="analyticscode"
                                                                                       style="width:600px;height:200px;font-family:monospace;"
                                                                                       wrap="off"><? echo $prefs['analyticscode'] ?></textarea>
                        </li>
                        <li><label class="checkbox inline"><input name="sourceurlrt"
                                                                  type="checkbox" <? if ($prefs['sourceurlrt'] == 1) echo "checked "; ?>/>
                                I want RT links to use the original source url.</label></li>
                        <li><label class="checkbox inline"><input name="sourceurlrss"
                                                                  type="checkbox" <? if ($prefs['sourceurlrss'] == 1) echo "checked "; ?>/>
                                I want RSS feed links to point to the original source url.</label></li>
                    </ul>
                </div>

                <div class="divPrefPane">
                    <div class="divPrefSubmit pull-right divPrefStyle"><img class="imgSpinner imgSpinnerSub"
                                                                            src="/images/spinner.gif"/><span
                                class="message"></span>
                        <button id="btnPrefSubmit" class="btn btn-success" type="submit">Save</button>
                    </div>
                    <h3>Style</h3>
                    <ul>
                        <div class="control-group">
                            <li>I want to override the default style with the stylesheet located at this url: <input
                                        name="stylesheet" type="text" value="<? echo $prefs['stylesheet'] ?>"/></li>
                        </div>
                        <div class="control-group">
                            <li>I want a maximum of <input name="maxlist" type="text"
                                                           value="<? echo $prefs['maxlist'] ?>"/> items to be listed in
                                feeds and on pages by default.
                            </li>
                        </div>
                        <div class="control-group">
                            <li><label class="checkbox inline"><input name="darkmode"
                                                                      type="checkbox" <? if ($prefs['darkmode'] == 1) echo "checked "; ?>/>
                                    I want to use the dark mode theme.</label></li>
                        </div>
                    </ul>
                </div>

                <div class="divPrefPane">
                    <div class="divPrefSubmit pull-right divPrefTwitter"><img class="imgSpinner imgSpinnerSub"
                                                                              src="/images/spinner.gif"/><span
                                class="message"></span>
                        <button id="btnPrefSubmit" class="btn btn-success btnPrefSubmit" type="submit">Save</button>
                    </div>
                    <h3>Twitter
                        <small>(Create app <a href="https://dev.twitter.com/apps/new">here</a>. Instructions <a
                                    href="https://groups.google.com/d/msg/sopml/fbMd0cWPzRk/84mMoDM4BEsJ">here</a>.)
                        </small>
                    </h3>
                    <ul>
                        <div class="control-group">
                            <li>My Twitter oAuth consumer key is: <input name="twitterkey" type="text"
                                                                         value="<? echo $prefs['twitterkey'] ?>"/></li>
                        </div>
                        <div class="control-group">
                            <li>My Twitter oAuth consumer secret is: <input name="twittersecret" type="text"
                                                                            value="<? echo $prefs['twittersecret'] ?>"/>
                            </li>
                        </div>
                        <div class="control-group">
                            <li>My Twitter oAuth access token is: <input name="twittertoken" type="text"
                                                                         value="<? echo $prefs['twittertoken'] ?>"/>
                            </li>
                        </div>
                        <div class="control-group">
                            <li>My Twitter oAuth access secret is: <input name="twittertokensecret" type="text"
                                                                          value="<? echo $prefs['twittertokensecret'] ?>"/>
                            </li>
                        </div>
                    </ul>

                    <h3 id="mastodon">Mastodon</h3>
                    <ul>
                        <div class="control-group">
                            <li>Instance url: <input name="mastodon_url"
                                                     placeholder="<? echo $cg_mastodon_placeholder_url ?>" type="text"
                                                     value="<? echo $prefs['mastodon_url'] ?>"/>
                            </li>
                            <div style="margin-left:30px;">
                                <? if (mastodon_is_enabled($uid)) echo "<li>App token: <span style='color:#900'>" . $prefs['mastodon_access_token'] . "</span><br><button id='resetMastodon'>Re-register</button></li>" ?>

                            </div>
                        </div>
                        <? if (!mastodon_is_enabled($uid)) { ?>
                            <div style="margin-left:30px;">
                                <p>Just fill out the username and password you used to register on the mastodon server
                                    and click the save button. The username
                                    and password isn't saved in the FC database. It's just used temporarily in the
                                    registration process to get an access token. You'll
                                    have to temporarily disable 2-factor authentication for the app registration to
                                    work. After, you can re-enable it.</p>
                                <input type="hidden" name="mastodon_register_app" value="TRUE"/>
                                <div class="control-group">
                                    <li>Server email: <input name="mastodon_server_email" type="text"
                                                             placeholder="john@doe.com"/></li>
                                </div>
                                <div class="control-group">
                                    <li>Server password: <input name="mastodon_server_password" type="text"
                                                                placeholder="password"/>
                                    </li>
                                </div>
                            </div>
                        <? } ?>
                        <div class="control-group">
                            <li>Filter String: <input name="mastodon_filter_string" type="text"
                                                      placeholder="Only pull toots that have this string"
                                                      value="<? echo $prefs['mastodon_filter_string'] ?>"/>
                                <br>Timeline Feed: <a href="/cgi/out/mastodon.timeline.xml?u=<?echo $uid?>"><?echo $cg_system_url?>/cgi/out/mastodon.timeline.xml?u=<?echo $g_uid?></a>
                            </li>

                        </div>
                    </ul>

                    <h3 id="micropub">Micropub (alpha - not working yet)</h3>
                    <ul>
                        <div class="control-group">
                            <li>Hostname: <input name="micropub_url"
                                                 placeholder="<? echo $cg_micropub_placeholder_url ?>" type="text"
                                                 value="<? echo $prefs['micropub_url'] ?>"/></li>
                            <li>API Token: <input name="micropub_token"
                                                  placeholder="<? echo $cg_micropub_placeholder_url ?>" type="text"
                                                  value="<? echo $prefs['micropub_url'] ?>"/></li>
                        </div>
                    </ul>
                </div>

                <div class="divPrefPane">
                    <div class="divPrefSubmit pull-right divPrefShortener"><img class="imgSpinner imgSpinnerSub"
                                                                                src="/images/spinner.gif"/><span
                                class="message"></span>
                        <button id="btnPrefSubmit" class="btn btn-success" type="submit">Save</button>
                    </div>
                    <h3>Url Shortener</h3>
                    <ul>
                        <div class="control-group">
                            <li>My shortening service is called with this url: <input name="urlshortener" type="text"
                                                                                      value="<? echo $prefs['urlshortener'] ?>"/>
                                (use %@ for the url variable)
                            </li>
                            <li>I'd rather shorten my urls in an S3 bucket named: <input id="s3shortbucket"
                                                                                         name="s3shortbucket"
                                                                                         type="text"
                                                                                         value="<? echo $prefs['s3shortbucket'] ?>"/>
                            </li>
                            <li>The last short url code used on my short domain was: <input id="lastshortcode"
                                                                                            name="lastshortcode"
                                                                                            type="text"
                                                                                            value="<? echo $prefs['lastshortcode'] ?>"/>
                            </li>
                        </div>
                    </ul>

                </div>
    </div>
    </fieldset>
    </form>

    <div style="display:none;">
        <form id="frmUploadAvatar" name="uploadAvatar" action="/cgi/auth/setavatar" method="POST"
              enctype="multipart/form-data">
            <input id="fileAvatar" name="avatarFile" type="file"/>
            <input id="btnAvatarUpload" name="avatarSubmit" type="submit" value="Post File"/>
        </form>
    </div>

</div>
</div>

<? //--- Include the footer bar html fragments -----------?>
<? include "$confroot/$templates/$template_html_footerbar" ?>
</body>

<? include "$confroot/$templates/$template_html_postbody" ?>
</html>
