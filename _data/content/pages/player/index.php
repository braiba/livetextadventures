<?php

	$playerId = $this->data['playerId'];
	$storyId = $this->data['storyId'];
	
?>
<script>
	var storyId = <?php echo $storyId;?>;
	var playerId = <?php echo $playerId;?>;
	setInterval('updateStoryForType(\'player\', playerId, storyId)', 2500);
</script>
<div id="messageBox">
	
</div>

<form data-type="player">
	&gt; <input type="text" class="player" name="message" autocomplete="on" />
</form>