<div class="section">
	<div class="content">
		<div class="columns is-centered">
			<div class="box column is-half">
			<h1><?php echo $success_msg?>.</h1>
			<?php if (isset($success_back_url)):?>
				<div class="level">
					<div class="level-item">
						<a href="<?php echo $success_back_url?>" class="button is-info">Back</a>
					</div>
				</div>
			<?php endif;?>
			</div>
		</div>
	</div>
</div>