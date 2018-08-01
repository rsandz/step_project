<nav class="navbar is-dark">
	<div class="navbar-brand">
		<a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false">
		  <span aria-hidden="true" class="has-background-white"></span>
		  <span aria-hidden="true" class="has-background-white"></span>
		  <span aria-hidden="true" class="has-background-white"></span>
		</a>
	</div>
	<div class="navbar-menu">
		<div class="navbar-start">
			<?php echo anchor('Dashboard', 'Dashboard', 'class="navbar-item"'); ?>
			<?php echo anchor('Logging', 'Log an Activity', 'class="navbar-item"'); ?>
			<div class="navbar-item has-dropdown is-hoverable">
				<?php echo anchor('Create', 'Create', 'class="navbar-link"'); ?>
				<div class="navbar-dropdown">
					<?php echo anchor('Create/action', 'Action', 'class="navbar-item"')?>
					<!-- Not Available to Normal Users-->
					<?php if($this->authentication->check_admin()):?>
						<?php echo anchor('Create/action_type', 'Action Type', 'class="navbar-item"');?>
						<?php echo anchor('Create/project', 'Project', 'class="navbar-item"');?>
						<?php echo anchor('Create/team', 'Team', 'class="navbar-item"');?>
						<?php echo anchor('Create/user', 'User', 'class="navbar-item"');?>
					<?php endif;?>
				</div>				
			</div>
			<?php echo anchor('manage_teams', 'Manage', 'class="navbar-item"'); ?>
			<?php echo anchor('Stats', 'Statistics', 'class="navbar-item"'); ?>
			<?php if ($this->session->privileges == 'admin') echo anchor('Admin', 'Admin', 'class="navbar-item"'); ?>
		</div>
		<div class="navbar-end">
			<?php echo anchor('Search', 'Search', 'class="navbar-item"'); ?>
			<div class="navbar-item has-dropdown is-hoverable">
				<?php echo anchor('Account', 'Account', 'class="navbar-link"');?>
				<div class="navbar-dropdown">
					<?php echo anchor('Account/settings', 'Settings', 'class="navbar-item"')?>
					<?php echo anchor('Account/admin-settings', 'Admin Settings', 'class="navbar-item"')?>
				</div>
			</div>
			<?php echo anchor('logout', 'Logout', 'class="navbar-item"'); ?>
		</div>
	</div>
	<?php echo script_tag('js/menu.js')?>
</nav>

