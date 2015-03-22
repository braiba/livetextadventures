<?php

	$writerId = $this->data['writerId'];
	$storyId = $this->data['storyId'];
	
?>
<script>
	var storyId = <?php echo $storyId;?>;
	var writerId = <?php echo $writerId;?>;
	setInterval('updateStoryForType(\'writer\', writerId, storyId)', 2500);
</script>
<div id="messageBox">
	
</div>

<form data-type="writer">
	<input type="text" class="writer" name="message" autocomplete="off" />
</form>