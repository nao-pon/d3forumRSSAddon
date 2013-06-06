<?php

//error_reporting(E_ALL);
//$xoopsErrorHandler =& XoopsErrorHandler::getInstance();
//$xoopsErrorHandler->activate(true);

$cache_min = 5;
$cat_ids = array();

require_once $mytrustdirpath.'/include/rss_functions.php';

$forum = (!empty($_GET['forum_id']))? intval($_GET['forum_id']) : 0;
if (! $forum ) $forum = (!empty($_GET['forum']))? intval($_GET['forum']) : 0;

if ($forum) {
	$cat = '0';
} else {
	if (empty($_GET['cat_id'])) {
		$_GET['cat_id'] = (empty($_GET['cat_ids']))? '' : $_GET['cat_ids'];
	}
	$cat = (!empty($_GET['cat_id']))? $_GET['cat_id'] : '0';
	if ($cat) {
		$cat = preg_replace('/[^0-9,]+/', '', $cat);

		$cat_ids = array() ;
		foreach( explode( ',' , $cat ) as $_id ) {
			if( $_id > 0 ) {
				$cat_ids[] = intval( $_id ) ;
			}
		}
		$cat_ids = array_unique($cat_ids);
		sort($cat_ids);
		$cat = ($cat_ids)? join(',', $cat_ids) : '0';
	}
}

$e = (!empty($_GET['e']))? $_GET['e'] : '';

if ($e === 'sjis') {
	$encode = 'SJIS';
	$encoding = 'Shift-JIS';
} else {
	$encode = $encoding = 'UTF-8';
}

$c_file = XOOPS_ROOT_PATH . '/cache/' . $mydirname . '_' . $cat . '_' . $forum . '.rss';

if (file_exists($c_file) && (filemtime($c_file) + $cache_min * 60) > time()) {
	$data = unserialize(join('',file($c_file)));
	$b_time = filemtime($c_file);
} else {
	$data =  d3forum_get_rssdata ( $mydirname , 15 , 0 , $forum , $cat_ids );
	if ($fp = @fopen($c_file, 'wb')) {
		fputs($fp,serialize($data));
		fclose($fp);
	}
	$b_time = time();
}

if (sizeof($cat_ids) > 1) {
	$_titles = array();
	foreach($data as $item) {
		$_titles[] = $item['cat_title'];
	}
	$cat_title = join(', ', array_unique($_titles));
} else {
	$cat_title = $data[0]['cat_title'];
}

$title = htmlspecialchars($xoopsConfig['sitename']). ' - ' . htmlspecialchars($xoopsModule->getVar('name'));
if ($cat || $forum) $title .= ' - ' . htmlspecialchars($cat_title) ;
if ($forum) $title .= ' - [ ' . htmlspecialchars($data[0]['forum_title']) . ' ]';
$top_link = ($forum)? 'index.php?forum_id='.$forum : '';
$top_link = ($cat)? 'index.php?cat_id='.$cat : $top_link;
$top_link = XOOPS_URL.'/modules/'.$mydirname.'/'.$top_link;

// RSS Build
$out = '<?xml version="1.0" encoding="'.$encoding.'"?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
<channel>
  <title>'.$title.'</title>
  <link>'.$top_link.'</link>
  <description></description>
  <lastBuildDate>'.date('r', $b_time).'</lastBuildDate>
  <docs>http://backend.userland.com/rss/</docs>
';
foreach($data as $item) {
	$subtitles = array();
	if ((!$cat || sizeof($cat_ids) > 1) && !$forum) $subtitles[] = $item['cat_title'];
	if (!$forum) $subtitles[] = $item['forum_title'];
	$subtitle = ($subtitles)? '[' . join('/',$subtitles) . '] ' : '';
	$cat1 = '<category>'.htmlspecialchars($item['cat_title']).'</category>';
	$cat2 = '<category>'.htmlspecialchars($item['forum_title']).'</category>';
	$out .='  <item>
    <title>'.htmlspecialchars($subtitle . $item['subject']).'</title>
    <link>'.$item['link'].'</link>
    <guid>'.$item['link'].'</guid>
    <description>'.htmlspecialchars(d3forum_make_context(strip_tags($item['description']))).'</description>
    <pubDate>'.date('r', $item['post_time']).'</pubDate>
    '.$cat1.'
    '.$cat2.'
    <content:encoded><![CDATA[' . $item['description'] . ']]></content:encoded>
  </item>
';
}
$out .='</channel>
</rss>';

$out = str_replace("\0", '', mb_convert_encoding($out, $encode, _CHARSET));

header('Content-type: application/xml');
echo $out;
exit();

?>