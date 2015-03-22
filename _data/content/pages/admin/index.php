<form method="post" class="admin">
	<?php
		foreach ($this->data['links'] as $name => $url) {
			?>
				<p>
					<?php echo $name;?>]: 
					<a href="<?php echo $url;?>"><?php echo $url;?></a>
				</p>
			<?php
		}
	
		foreach ($this->data['writers'] as $writer) {
			?>
				<p>Name: <input type="test" name="players[<?php echo $writer->writer_ID;?>]" /></p>
			<?php
		}
	?>
	<p><input type="submit" /></p>
</form>