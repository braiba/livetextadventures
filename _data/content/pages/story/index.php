<?php

	$stories = $this->data['stories'];
	
?>
<table>
	<thead>
		<tr>
			<th>Writer</th>
			<th>Player</th>
			<th>Link</th>
		</tr>
	</thead>
	<tbody>
		<?php
			foreach ($stories as $story) {
				/* @var $story Story */
				?>
					<tr>
						<td><?php echo $story->Writer->name;?></td>
						<td><?php echo $story->Player->name;?></td>
						<td>
							<a href="story/view/<?php echo $story->story_ID;?>">link</a>
						</td>
					</tr>
				<?php 		
			}
		?>
	</tbody>
</table>
