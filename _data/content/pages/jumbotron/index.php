<script>
	setInterval('updateStoryForJumbotron()', 2500);
</script>
<h1>Live Text Adventures</h1>
<h2>BTHub3-2QZ3 - d4744a57c7</h2>
<table id="jumbotron">
	<tbody>
		<tr>
			<?php
				foreach ($this->data['stories'] as $story) {
					?>
						<td>
							<div id="messageBox<?php echo $story->id;?>" class="messageBox">
							</div>
						</td>
					<?php
				}
			?>
		</tr>
	</tbody>
</table>
