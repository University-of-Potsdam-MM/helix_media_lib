<?php

/**
 * View HML resource
 *
 * @since 2.0
 * @package    repository
 * @subpackage helix_media_lib
 * @copyright  2011 Streaming LTD
 * @author     Tim Williams <tmw@autotrain.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once("helixlib.php");
require_once("../lib.php");

$mid=required_param("mid", PARAM_TEXT);
$rid=required_param("rid", PARAM_INT);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php print_string("pluginname", "repository_helix_media_lib");?></title>
<?php
global $USER, $PAGE, $OUTPUT;

/***$COURSE will always give 1 at this point, so read the users current course from the $USER object instead***/
$cid=1;
$cid=array_keys($USER->currentcourseaccess);
if(array_key_exists(0, $cid))
 $cid=$cid[0];
else
 $cid=1;

require_login($cid);
echo $OUTPUT->standard_head_html();
?>
<style type="text/css">
 #mediaplayer_wrapper
 {
  margin-left:auto;
  margin-right:auto;
 }

 #mediaplayer
 {
  margin-left:auto;
  margin-right:auto;
 }
</style>
</head>
<body>
<div><br /></div>
<?php

$repo=repository::get_instance($rid);
$course_context=get_context_instance(CONTEXT_COURSE, $cid);
$PAGE->set_context($course_context);

if ($repo==null)
{
 echo '<div class="errorbox"><p class="error">'.get_string('repo_not_found', 'repository_helix_media_lib').'</p></div></body></html>';
 die;
}

if ($course_context==null)
{
 echo '<div class="errorbox"><p class="error">'.get_string('course_not_found', 'repository_helix_media_lib').'</p></div></body></html>';
 die;
}

if (!has_capability('mod/url:view', $course_context))
{
 echo '<div class="errorbox"><p class="error">'.get_string('no_permission', 'repository_helix_media_lib').'</p></div></body></html>';
 die;
}

$response=$repo->hml_soap->get_media_with_token($mid, $_SERVER['REMOTE_ADDR']);

if ($response->details["videoid"]==0)
{
 echo '<div class="errorbox"><p class="error">'.get_string('vid_not_found', 'repository_helix_media_lib').'</p></div></body></html>';
 die;
} 

$width=$repo->get_option('v_width');
$height=$repo->get_option('v_height');

if ($response==null)
{
 echo "</body></html>";
 die;
}

?>

<script type='text/javascript' src='<?php echo $repo->get_option('http_host');?>/jwplayer.js'></script>
<div id="cont"><div id='mediaplayer'></div></div>
<script type="text/javascript">
// <!--

 setTimeout(function() {fixFrame(<?php echo $height+40;?>); }, 1000 );
 
 var astream='<?php echo $repo->get_option('astream_url')."/"; ?>';
 var file='<?php
  echo $response->details['filename']; ?>?token=(uniquePlayerReference=<?php echo $response->token["UniquePlayerRef"]; ?>||videoId=<?php echo $mid;
 ?>)';

 jwplayer('mediaplayer').setup({
    'id': 'playerID',
    'width': '<?php echo $width;?>',
    'height': '<?php echo $height;?>',
    <?php
     if ($response->details["mediatype"]=="video")
     {
    ?>
    'provider': 'rtmp',
    'streamer': '<?php echo $repo->get_option('vstream_url'); ?>',
    <?php 
     }
    ?>
    'file': <?php if ($response->details["mediatype"]=="audio") echo "astream+"; ?>file,
    'image': '<?php echo $response->details['thumbnail']; ?>',
    'modes': [
        {type: 'flash', src: '<?php echo $repo->get_option('http_host'); ?>/player58.swf'},
        {
          type: 'html5',
          config: {
           'file': astream+file,
           'provider': 'video'
          }
        },
        {
          type: 'download',
          config: {
           'file': astream+file,
           'provider': 'video'
          }
        }
    ]
  }); 

 //alert(document.getElementById("cont").innerHTML);

 function fixFrame(height)
 {
  if (typeof(window.parent)!="undefined")
  {
   var res=window.parent.document.getElementById("resourceobject");

   if (typeof(res)!="undefined" && res!=null)
   {
    res.frameBorder=0;
    res.style.borderWidth="0px";
    res.style.height=height+"px";
   }
  }
 }
// -->
</script>
<noscript>
<?php

$flash_vars="height=".$height."&amp;width=".$width."&amp;";

if ($response->details['mediatype']=="video")
 $flash_vars.="streamer=".$repo->get_option('vstream_url')."&amp;file=".$response->details['filename'];
else
 $flash_vars.="file=".$repo->get_option('astream_url')."/".$response->details['filename'];

$flash_vars.="?token=(uniquePlayerReference=".$response->token["UniquePlayerRef"]."||videoId=".$mid.")&amp;".
     "videoId=".$mid."&amp;".
     "searchbar=false&amp;autostart=false&amp;".
     "image=".$response->details['thumbnail'];

?>
<div style="text-align:center;">
<object type="application/x-shockwave-flash" data="<?php echo $repo->get_option('http_host'); ?>/player58.swf"
  width="<?php echo $width;?>" height="<?php echo $height;?>" id="jwplayer">
 <param name="movie" value="<?php echo $repo->get_option('http_host'); ?>/player58.swf" />
 <param name="allowScriptAccess" value="always" />
 <param name="allowFullScreen" value="true" />
 <param name="quality" value="high" />
 <param name="flashvars" value="<?php echo $flash_vars; ?>" />
</object>
</div>
</noscript>
</body>
</html>
