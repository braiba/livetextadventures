<?php
	$messages = $this->data['messages'];
	$writer = $this->data['writer'];
	$player = $this->data['player'];
	
?>
<p><b>Writer:</b> <?php echo $writer;?>
<p><b>Player:</b> <?php echo $player;?>
<p>---</p>
<div id="messageBox">
	<?php
		foreach ($messages as $messageData) {
			?>
				<p class="<?php echo $messageData['source'];?>">
					<?php echo htmlentities($messageData['message']);?>
				</p>
			<?php
		}
	?>
</div>
