<?php

use function GuzzleHttp\Promise\each;
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * AJAX Controller
 * ===============
 * @author Ryan Sandoval, June 2018
 *
 * All AJAX requests should be sent to this controller. Java script can access this controller's adress by 
 * using the data attribute in the 'ajax-link' hidden input located in the header of every webpage.
 *
 * This controller is mostly used for getting descriptions, and updated field values based on user selection.
 */
class Ajax extends MY_Controller 
{

	/**
	 * Constructor for the AJAX Controller
	 *
	 * Loads all necessary resources.
	 * Also setes the PHP default timezone as per the configuration in config/appconfig.php
	 */
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Searching/search_model');
		$this->load->model('Stats/statistics_model');
		$this->load->model('Form_get_model');
		$this->load->helper('form');
		if ($_SERVER['REQUEST_METHOD'] !== 'GET') //If not acessed by post, redirect away	
		{	
			redirect('home','refresh');	
			show_error('No access allowed', 403);	
		}

	}

	/**
	 * Main page for the Ajax Controller
	 * This will redirect the user away if it is not acessed by a script's post request.
	 * @return [type] [description]
	 */
	public function index() 
	{
		redirect('home','refresh');
		show_error('No access allowed', 403);
	}

	/**
	 * Gets the Description and/or action_type for fields in certain Tables.
	 * 		i.e. action table and project table both have descriptions and these will grab them
	 * For use with $.ajax(). Uses $_Get Array to get information
	 * @param $_Get string table
	 * @param $_Get string table_id
	 * 
	 */
	public function get_info()
	{
		$table = $this->input->get('table', TRUE);
		$id = $this->input->get('id', TRUE);

		$data = $this->Form_get_model->get_item_info($table, $id);

		echo json_encode($data);
	}

	/**
	 * Gets the data to send back to the select2 action dropbox.
	 *
	 * @param  $_Get string type_id
	 * @param  $_Get string project_id
	 * @param  $_Get string term
	 * 
	 */
	public function get_action_items()
	{
		$type_id = $this->input->get('type_id', TRUE);
		$project_id = $this->input->get('project_id', TRUE);
		$term = $this->input->get('term', TRUE);

		$actions = $this->Form_get_model->get_active_actions($type_id, $project_id, $term);
		echo json_encode($actions);
	}

	/**
	 * Gets the data for user.
	 * Used in the mystats charts
	 *
	 * The get array must contain the following:
	 * 	 string interval_type The interval type of the data ('daily', 'weekly', monthly', 'yearly')
	 * 	 string from_date
	 * 	 string to_date
	 * @param string $type Metric to get (i.e. logs)
	 */
	public function user_stats($type)
	{
		$data = $this->statistics_model
			->metrics($type)
			->interval_type($this->input->get('interval_type', TRUE))
			->from_date($this->input->get('from_date', TRUE))
			->to_date($this->input->get('to_date', TRUE))
			->user_lock(TRUE)
			->labels("User {$type} Statistics")
			->get();
		//Give the data a name. Used for the graph legend
		echo json_encode($data);
	}

	/**
	 * Gets the data for project log frequency.
	 * Used in the projectstats charts
	 *
	 * Get Array Must contain
	 * 	string interval_type The interval type of the data ('daily', 'weekly', monthly', 'yearly')
	 * 	string from_date
	 * 	string to_date
	 * @param int $project_id
	 * @param string $type Metric to get
	 */
	public function project_stats($project_id, $type)
	{
		$this->statistics_model->null_projects(FALSE);
		$this->statistics_model->null_teams(FALSE);
		$this->statistics_model->projects($project_id);

		$data = $this->statistics_model
			->metrics($type)
			->interval_type($this->input->get('interval_type', TRUE))
			->from_date($this->input->get('from_date', TRUE))
			->to_date($this->input->get('to_date', TRUE))
			->labels("Project {$type} Statistics")
			->get();

		//Give the data a name. Used for the graph legend
		echo json_encode($data);
	}

	/**
	 * Gets the data for team log stats
	 *
	 * Get Array Must contain
	 * 	string interval_type The interval type of the data ('daily', 'weekly', monthly', 'yearly')
	 * 	string from_date
	 * 	string to_date
	 * @param int $team_id
	 * @param string $type Metric to get
	 */
	public function team_stats($team_id, $type)
	{
		$this->statistics_model->null_projects(FALSE);
		$this->statistics_model->null_teams(FALSE);
		$this->statistics_model->teams($team_id);

		$data = $this->statistics_model
			->metrics($type)
			->interval_type($this->input->get('interval_type', TRUE))
			->from_date($this->input->get('from_date', TRUE))
			->to_date($this->input->get('to_date', TRUE))
			->labels("Team {$type} Statistics")
			->get();

		//Give the data a name. Used for the graph legend
		echo json_encode($data);
	}

	/**
	 * Gets the data for custom log frequency.
	 *
	 * @param $_Get string interval_type The interval type of the data ('daily', 'weekly', monthly', 'yearly')
	 *                     				 @see $this->statistics_model->get_custom_log_frequency() for more info
	 */
	public function custom_stats($index, $type)
	{
		$indexed_query = $this->session->{'query_'.$index};
		if (empty($indexed_query))
		{
			//i.e. Get empty data set
			$this->statistics_model->from_date('0000-00-00');
			$this->statistics_model->to_date('0000-00-00');
		}
		else
		{
			$this->statistics_model->import_query($this->session->{'query_'.$index});
			$this->statistics_model
				->from_date($this->input->get('from_date', TRUE))
				->to_date($this->input->get('to_date', TRUE));
		}
		$data = $this->statistics_model
			->metrics($type)
			->interval_type($this->input->get('interval_type', TRUE))
			->labels("Custom Stats #{$index} {$type} Statistics")
			->get();

		//Give the data a name. Used for the graph legend
		$data['label'] = "Custom Stats {$index} Log Freq";
		echo json_encode($data);
	}

	public function compare_stats($type)
	{
		$indexed_query1 = $this->session->query_1;
		$indexed_query2 = $this->session->query_2;

		//Get Data 1
		$this->statistics_model->import_query($indexed_query1);
		$data1 = $this->statistics_model
			->metrics($type)
			->interval_type($this->input->get('interval_type', TRUE))
			->from_date($this->input->get('from_date', TRUE))
			->to_date($this->input->get('to_date', TRUE))
			->labels("Custom Stats 1 {$type} Statistics")
			->get();

		//Get Data 2
		$this->statistics_model->import_query($indexed_query2);
		$data2 = $this->statistics_model
			->metrics($type)
			->interval_type($this->input->get('interval_type', TRUE))
			->from_date($this->input->get('from_date', TRUE))
			->to_date($this->input->get('to_date', TRUE))
			->labels("Custom Stats 2 {$type} Statistics")
			->get();
		
		//Merge Data Sets
		$data1['dataSets'][] = $data2['dataSets'][0];
		echo json_encode($data1);
	}

	/**
	 * Sets the sort values for a certain idenifier
	 * @param string The form to set the search for
	 */
	public function set_sort($identifier = 'default')
	{
		$sort_field = $this->input->get('sort_field', TRUE);
		$sort_dir = $this->input->get('sort_dir', TRUE);
		//Specific routine for search sort
		if ($identifier == 'search')
		{
			$this->load->helper('search');
			set_search_sort($sort_field, $sort_dir);

			$data['msg'] = 'Search Sort Changed';
			$data['new_sort_by'] = get_search_sort();
			echo json_encode($data);
		}
		else
		{
			$this->load->helper('sort');
			set_sort($identifier, $sort_field, $sort_dir);

			$data['msg'] = "Sort for '{$identifier}' Changed";
			$data['new_sort_by'] = get_sort($identifier);
			echo json_encode($data);
		}
	}
}


/* End of file ajax.php */
/* Location: ./application/controllers/ajax.php */