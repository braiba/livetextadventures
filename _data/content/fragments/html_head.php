<?php	
	$page_title = htmlentities($this->getTitle()) ?: '';
	$description = htmlentities($this->getMetaDescription()) ?: 'Geeky Cross Stitch Packs - everything from Mario to My Little Pony, Dr Who to Transformers and Batman to Supernatural';
	$keywords = $this->getMetaKeywords();
	$feeds = $this->getFeeds();
?>
<title>
	<?php 
		echo 'Live Text Adventures';
		if ($page_title) {
			echo '&nbsp;|&nbsp;' . $page_title;
		}
	?>
</title>
<base href="<?php echo 'http://'.$_SERVER['HTTP_HOST'].SITE_PATH.'/';?>" />
<meta content="text/html; charset=UTF-8" http-equiv="Content-Type">
<meta name="generator" content="Hanabi Bespoke Framework">
<meta name="Description" content="<?php echo $description;?>" />
<meta name="Keywords" content="<?php echo implode(', ',$keywords);?>" />
<meta name="google-site-verification" content="T-Ud3OxdeLiBnwH_tAqe-cuiuVFpAJEHsyOVmV-6ax4" />
<link rel="index" href="<?php echo HTMLUtils::absolute('./');?>" title="Hanabi">
<link rel="stylesheet" type="text/css" href="./fingerprinted/2012_06_10/style.css">
<script src="./fingerprinted/v1.5.2/js/jQuery.js" language="javascript" type="text/javascript"></script>
<script src="./fingerprinted/2015-03-18/js/script.js" language="javascript" type="text/javascript"></script>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<?php
	if (defined('INCLUDE_ANALYTICS') && INCLUDE_ANALYTICS){
?>
	<script type="text/javascript">

		var _gaq = _gaq || [];
		_gaq.push(['_setAccount', 'UA-32123265-1']);
		_gaq.push(['_trackPageview']);

		(function() {
			var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		})();

	</script>
<?php
	}
?>