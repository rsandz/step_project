<section class="section">
	<?php echo isset($errors) ? $errors : NULL?>
	<h2 class="title">Create a New Incident</h2>
	<hr>
	<!-- Form -->
	<?php echo form_open('Incidents/create');?>
		<div class="field">
			<div class="control">
				<label class="label">Name: <span class="has-text-danger">(Required)</span></label>
				<input type="text" class="input" name="incident_name" required>
			</div>
		</div>
		<div class="field is-grouped">
			<div class="control is-expanded">
				<label class="label">Date: <span class="has-text-danger">(Required)</span></label>
				<input type="date" class="input is-fullwidth" name="incident_date" id='date' required>
			</div>
			<div class="control is-expanded">
				<label class="label">Time: <span class="has-text-danger">(Required)</span></label>
				<input type="time" class="input is-fullwidth" id='time' name="incident_time" >
			</div>
			<div class="control" style="margin-top: auto">
				<a class="button is-info" id='now'>Now</a>
			</div>
		</div>
		<div class="field">
			<div class="control">
				<label class="label">Description:</label>
				<textarea class="textarea" placeholder="Description" name="incident_desc"></textarea>
				<p class="help">
					Supports HTML Markups. Click 
					<a href="https://github.com/rsandz/step_project/wiki/HTML-Markup">here</a>
					for more Information
				</p> 
			</div>
		</div>
		<div class="level">
			<div class="level-left">
				<?php echo anchor('Incidents', 'Cancel', 'class="button is-danger"')?>
			</div>
			<div class="level-right">
				<?php echo form_submit('Submit', 'Submit', 'class="button is-info"');?>
			</div>
		</div>
	</form>
</section>

<?php echo script_tag('js/moment.js')?>
<script>
	//Sets the date and time inputs to now
	$(function()
	{
		$('#now').click(function()
			{
				$('#date').val(moment().format('YYYY-MM-DD'));
				$('#time').val(moment().format('HH:mm'));
			});
	});
</script>