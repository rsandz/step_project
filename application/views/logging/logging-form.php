<?php echo script_tag('js/descriptions.js') ?>
<section class="section">
	<h1 class="title">Activity Log Form</h1>
	<hr>
	<?php echo form_open('logging/log'); ?>
		<div class="columns">
			<div class="column is-one-third">
				<div class="field">
					<label class="label" for='project-selector'>
						Project: <span class="has-text-danger">(Required)</span>
					</label>
					<!-- Creates the selection for Projects -->
					<div class="control">
						<div class="select">
							<select class="init-select2" name="project" id="project-selector">
								<?php 
								if (!empty($projects))
								{
									foreach ($projects as $project)
									{
										//Preserve Field on error
										$set_select = set_select('project', $project->project_id);
										//Create Option
										echo  "<option value='{$project->project_id}' {$set_select}>";
										echo $project->project_name;
										echo "</option>";
									}
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
				
			<!-- PROJECT DESCRIPTION -->
			<div class="column">
				<div class="content">
					<p id="project-desc"></p>
				</div>
			</div>
		</div>

		<!-- Creates the selection for Teams -->
		<div class="columns">
			<div class="column">
				<label class="label">Team:</label>
					<div class="field">
					<div class="control">
						<div class="select"> 
							<select class="init-select2" name="team">
								<?php 
									if (!empty($teams))
									{
										foreach ($teams as $team)
										{
											//Preserve Field on error
											$set_select = set_select('team', $team->team_id);
											//Create Option
											echo  "<option value='{$team->team_id}' {$set_select}>";
											echo $team->team_name;
											echo "</option>";
										}
									}
								?>
								<option value='null'>No Team</option>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>
			
		<!-- Action Type -->
		<div class="columns">
			<div class="column">
				<label class="label" for="type-selector">
					Action Type: <span class="has-text-danger">(Required)</span>
				</label>
				<div class="field">
					<div class="select">
						<select class="init-select2" name="action_type" id="type-selector" required>
							<?php 
								if (!empty($types))
								{
									foreach ($types as $type)
									{
										//Preserve Field on error
										$set_select = set_select('action_type', $type->type_id);
										//Create Option
										echo  "<option value='{$type->type_id}' {$set_select}>";
										echo $type->type_name;
										echo "</option>";
									}
								}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>

		<div class="columns">
			<!-- Action Selection -->
			<div class="column is-one-third">
				<div class="field">
					<label class="label" for="action">
						Actions: <span class="has-text-danger">(Required)</span>
					</label>
					<!-- Creates the selection for actions -->
					<div class="control">
						<div class="select">
							<select name="action" id="action" required>
							</select>
						</div>
					</div>
				</div>
			</div>
			
			
			<!-- ACTION DESCRIPTION -->
			<div class="column">
				<div class="content">
					<p id="action-desc"></p>
				</div>
			</div>
		</div>
		
		<!-- Date and Time-->
		<div class="columns">
			<div class="column">
				<div class="field">
					<label class="label "for="date">Date: <span class="has-text-danger">(Required)</span></label>
					<div class="control"> 
						<input class="input" type="date" name="date" value="<?php echo set_value('date', date('Y-m-d')); ?>">
					</div>
				</div>
			</div>
			<div class="column">
				<div class="field">
					<label class="label "for="date">Time: <span class="has-text-danger">(Required)</span></label>
					<div class="control">
						<input class="input" type="time" name="time" value = "<?php echo set_value( 'time', date('H:i')); ?>">
					</div>
				</div>
			</div>
			<div class="column is-2">
				<div class="field">
					<label class="label">Number of Hours: </label>
					<div class="control">
						<input type="number" class="input" value="<?php echo set_value('hours', 0)?>" name="hours">
					</div>
				</div>
			</div>
		</div>
		
		<div class="field">
			<label class="label">Description:</label>
			<div class="control">
				<textarea class="textarea" name="desc" ><?php echo set_value('desc')?></textarea>
			</div>
			<p class="help">
				Supports some HTML Markups. 
				Click <?php echo anchor('https://github.com/rsandz/step_project/wiki/HTML-Markup', 'here'); ?> to learn more.
			</p>
		</div>
			
		<hr>

		<div class="level">
			<div class="level-left">
					<div class="field is-grouped">
						<div class="control">
							<input type="submit" class = "button is-info is-medium" name="submit" value="New Log" />
						</div>
					</div>
			</div>
			<div class="level-right">
				<div class="level-item">
				</div>
			</div>
		</div>
	</form>


