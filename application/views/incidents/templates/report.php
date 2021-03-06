<div class="content" id="report">
	<?php echo $title?>
	<hr>
	<?php echo $summary?>
	<hr>
	<h2 class="subtitle">Relevant Logs</h2>	
	<?php echo $relevant_logs?>
	<hr>
	<div class="columns">
		<div class="column">
			<h2 class="subtitle">Recent Logs (Past 10 Logs)</h2>
			<?php echo $last_10_table?>
		</div>
		<div class="column">
			<?php echo $past_week_all_stats?>
		</div>
	</div>
	<h2 class="subtitle">Quick Searches</h2>
	<hr>
	<div class="columns">
		<div class="column">
			<?php echo $past_week_search?>
		</div>
		<div class="column">
			<?php echo $past_month_search?>
		</div>
		<div class="column">
			<?php echo $past_3days_search?>
		</div>
	</div>
</div>

<!-- Static chart JS-->
<?php echo script_tag('js/myChart.js')?>
<!-- Chart.js -->
<?php echo script_tag('js/Chart.js')?>
<?php echo script_tag('js/moment.js')?>