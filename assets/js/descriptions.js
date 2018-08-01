$(function() 
{
	getActions();
	setProjectInfo();
	$('#project-selector').change(setProjectInfo).change(getActions);
	$('#type-selector').change(getActions);
});

function setActionInfo() 
{
	if ($('#action-selector').val() !== 'NULL')
	{
		$.get(
				$('#ajax-link').attr('data')+"/get_info",
				{	
					id : $('#action-selector').val(),  
					table : 'actions'
				},
				function(data) 
				{
					data = $.parseJSON(data);
					$('#action-desc').html("<strong>Action Description</strong>: </br>"+data.desc);
				})
			.fail(promptError);
	}
	else
	{
		$('#action-desc').html("<strong>No Description</strong>");
	}
}

function setProjectInfo() 
{
	$.get(
			$('#ajax-link').attr('data')+"/get_info",
			{id : $('#project-selector').val(), table : 'projects'},
			function(data) 
			{
				data = $.parseJSON(data);
				$('#project-desc').html("<strong>Project Description</strong>: </br>"+data.desc);
			})
	.fail(promptError);
}

function getActions()
{
	selectedProject_id = $('#project-selector').val();
	$.get(
		 $('#ajax-link').attr('data')+'/get_action_items',
		 {
		 	project_id : selectedProject_id,
		  	type_id : $('#type-selector').val()
		 }, 
		 function(data)
		 {
		 	data = $.parseJSON(data);
		 	console.log('items' + data);
		 	$('#action-div').html(data);

		 	$('#action-selector').change(setActionInfo); //Re-add the even listener
			setActionInfo();
		 })
		.fail(promptError);
}

function promptError()
{
	if ($('#errors').length > 0)
	{
		$('#errors').html('<div class="notification is-danger">An AJAX Error has occured. Please consult an administrator.</div>');
	}
	else
	{
		alert('An AJAX Error has occured. Please consult an administrator.');
	}
}