<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Carbon\Carbon;
/**
 * MODIFY MODEL
 * ============
 * @author Ryan Sandoval, July 03, 2018
 *
 * This model handles database interactions/logic that requires modification
 * of inserted data. Examples the required operations on inserted data include:
 * 	- Direct Table modification via the admin modify table
 * 	- Team Addition/Removal
 */
class Modify_model extends MY_Model {

	/** @var int Rows of the last modify table, before pagination */
	protected $total_rows;
	/** @var array The headings for the last table */
	protected $headings = array();

	/**
	 * Constructor for the modify model.
	 *
	 * Loads the resources and libraries that are used in the model
	 */
	public function __construct()
	{
		parent:: __construct();
		$this->load->database(); //load database
		$this->load->library('table');

		$this->load->model('get_model');
		
		
	}

	/**
	* Creates the table (as HTML string) that the user should see when they are modifying a database table.
	*
	* Example: Providing `actions` as the table as angument will result in this function returning
	* 			a table with the columns in the `action` table along with a column that says edit.
	* @param  string  $table  The name of the table of which to modify
	* @param  integer $offset Offset for pagination. If '10' was the argument, then this table's first item
	*                         will be the 10th item in the database.
	* @return array          An associative array containing:
	*                           'primary_key' => The name of the primary key of the table
	*                           'table'       => The HTML string for the table
	*                           'num_rows'    => Number of rows in the table
	*                           'table_name'  => Humanized version of the table name
	*/
	public function get_modify_table($table, $offset = 0)
	{
		$per_page  = $this->config->item('per_page');

		//Conditions and data formatting for certain tables
		//Get Table DATA
		$data['primary_key'] = $this->get_primary_key_name($table);
		
		$this->db->from($table);

		//Get Total Rows without pagination
		$this->total_rows = $this->db->count_all_results('', FALSE);
		$this->db->limit($per_page, $offset);

		$query = $this->db->get();

		
		foreach ($query->result() as &$row)
		{
			//Censoring Password Hashes - See configuration 'appconfig' for disabling this
			if (!$this->config->item('show_hashes') && $this->db->field_exists('password', $table)) 
			{
				$row->password = '***********';
			}

			//Push Edit button to each row
			$row->edit_button = anchor("Modify/{$table}/{$row->{$data['primary_key']}}", 'Edit');
		}

		//Make Table headings
		$this->headings = array_map(function($x) {return humanize($x);}, $query->list_fields());
		array_push($this->headings, ''); //Add a column for edit button

		//Create The table
		$this->load->library('table');
		$data['table'] = $this->table->my_generate($query, $this->headings);
		$data['num_rows'] = $this->total_rows;
		$data['table_name'] = humanize($table);

		return $data;
	}

	/**
	 * Creates the form (as HTML string) that the user should see when editing a row.
	 *
	 * The method will automatically create the proper input type for each column
	 * @param  string $table The name of the table that you want a form for.
	 * @param  int $key   The primary key of the row you want to edit. Used for populating the input fields
	 *                    with the current values
	 * @return Array     An array of objects containing the field data.
	 *                      Each of the objects in the array contains the properties:
	 *                      	name - The name of the field/column. 
	 *                      	form - The HTML string for the input field
	 */
	public function get_modify_form($table, $key)
	{
		//Load resources
		$this->load->config('modify_config');

		//First get column data. This will be used to format the form.
		$columns = $this->get_field_data($table, TRUE);

		//Get primary key name
		$primary_key = $this->get_primary_key_name($table);

		//Get the current values of the row.
		//Note: To get the current value of a cloumn in the selected row, use $query->$column_name
		$query = $this->db->where($primary_key, $key)->get($table)->row();

		foreach ($columns as $index => $column)
		{
			$fields[$index]= new stdClass(); //A Class will be used to hold the data of a field
			$field =& $fields[$index];

			$field->name = $column->name;

			//Is field required? If so, add required to input attributes
			$table_rules = $this->config->item('modify_rules');
			if (isset($table_rules[$table][$column->name]) && preg_match('/required/i', $table_rules[$table][$column->name]))
			{
				$required = 'required';
			}
			else
			{
				$required = NULL;
			}

			//Now create the correct form
			
			//Conditions for certain fields. i.e. Passwords, relational ids (e.g. team_id)
			switch ($column->name)
			{
				case $primary_key: //Prevent changing of primary key
				case 'password': //Prevent Direct edit of password
					$field->form = form_input($column->name, $query->{$column->name}, 'class="input is-light" readonly'); 
					continue(2);
				default:
					continue;
			}

			//If the current field is a foreign key, we will replace it with 
			//a dropdown selection containing the humanized name as configured in the config
			if (isset($this->config->item('foreign_keys')[$column->name])) 
			{
				//Get the config for this foreign_key
				$config = $this->config->item('foreign_keys')[$column->name];

				//Get Primary key of the reference table
				$FK_primary_name = $this->get_primary_key_name($config['FK_table']);

				//Get the reference table
				$foreign_data = $this->db
					//Value to be seen by user
					->select($config['display_column'].' AS `display_name`') 
					//Value of the item in the dropdown
					->select($FK_primary_name.' AS `value`')
					->get($config['FK_table'])->result();

				//Create the form html string
				$options = array();
				foreach ($foreign_data as $item)
				{
					$options[$item->value] = $item->display_name;
				}
				//Add a null option
				$options[NULL] = 'None';
				$field->form = '<div class="select">';
				$field->form .= form_dropdown($column->name, $options, $query->{$column->name}, 'class="init-select2"');
				$field->form .= '</div>';
				continue;
			}

			switch ($column->type) 
			{
				case 'varchar':
					$field->form = form_input($column->name, $query->{$column->name}, 'class="input" '.$required);
					break;
				case 'binary':
					$field->form = '<div class="field is-horizontal">';
					$field->form .= '<label class="radio">';
					$field->form .= form_radio($column->name, 1,  $query->{$column->name} == 1, 'class="radio" '.$required);
					$field->form .= 'True</label>';
					$field->form .= '<label class="radio">';
					$field->form .= form_radio($column->name, 0,  $query->{$column->name} != 1, 'class="radio" '.$required);
					$field->form .= 'False</label>';
					$field->form .= '</div>';

					break;
				case 'enum': 
					//This mess of a code just makes every enumerate value in a field display as  radio buttons
					$field->form = '<div class="field is-horizontal">';
					foreach ($column->enum_vals as $enum_val)
					{
						$field->form .= '<label class="radio">';
						$field->form .= form_radio($column->name, $enum_val, $query->{$column->name} == $enum_val,'class="radio" '.$required);
						$field->form .= humanize($enum_val).'</label>';
					}
					$field->form .= '</div>';
					break;
				case 'smallint':
				case 'int':
					$field->form = "<input class='input' value='{$query->{$column->name}}' type='number' name='$column->name' {$required}>";
					break;
				case 'date':
					$field->form = "<input class='input' value='{$query->{$column->name}}' type='date' name='$column->name' {$required}>";
					break;
				case 'time':
					$field->form = "<input class='input' value='{$query->{$column->name}}' type='time' name='$column->name' {$required}>";
					break;
				case 'timestamp': //User shouldn't be able to edit this
					$datetime = new Carbon($query->{$column->name});
					$field->form = "<div class='field is-grouped'>";
					$field->form .= "<input class='input' value='{$datetime->format('Y-m-d')}' type='Date' name='{$column->name}-date' readonly>";
					$field->form .= "<input class='input' value='{$datetime->format('H:i')}' type='Time' name='{$column->name}-time' readonly>";
					$field->form .= "</div>";
					break;	
				default:
					$field->form = form_input($column->name, 'error', 'class="input"');
					break;
			}
		}

		return $fields;
	}

	/**
	 * Updates the table based on the given arguments
	 * @param  string $table The name of the table to update
	 * @param  array $data  Associative array of data
	 * @param  int $key   The primary key integer. The method will automatically get
	 *                    the primary key name using get_primary_key_name()
	 * @return boolean        True if Sucessfule
	 */
	public function update($table, $data, $key)
	{
		$primary_key = $this->get_primary_key_name($table);
		//Update the Table
		return $this->db->where($primary_key, $key)
			->update($table, $data);
	}

	/**
	 * Add the user_id along with the team_id to the user_teams table,
	 * thus adding the user to the team.
	 * @param int $team_id The team id that the user will be added to.
	 * @param int $user_id The user id that will be added to the team.
	 *
	 * @return boolean True if sucessful. False if not
	 */
	public function add_to_team($team_id, $user_id)
	{
		//Get user and team name
		$query = $this->db->where('team_id', $team_id)->get('teams');

		$query = $this->get_model->get_users($user_id);

		//Add to the user to team
		$insert_data = array(
			'user_id' => $user_id,
			'team_id' => $team_id
		);
		//Check Data Exists first
		if (!$this->data_exists('user_teams', $insert_data))
		{
			$query = $this->db->insert('user_teams', $insert_data);
			if (!$query)
			{
				return FALSE;
			}
		}

		return TRUE; //If all ran well
	}

	/**
	 * Removes the user from the selected team by removing them from 
	 * the user_teams table in the database.
	 * @param  int $team_id Team id where the user is to be removed
	 * @param  int $user_id User id to remove frm the team
	 * @return boolean          True if sucessful. False if not
	 */
	public function remove_from_team($team_id, $user_id)
	{
		//Get user and team name
		$query = $this->db->where('team_id', $team_id)->get('teams');

		$query = $this->db->where('user_id', $user_id)->get('users');

		//Remove the user from the team
		$delete_data = array(
			'user_id' => $user_id,
			'team_id' => $team_id
		);

		//Check Data Exists first
		if ($this->data_exists('user_teams', $delete_data))
		{
			$query =  $this->db->delete('user_teams', $delete_data);
			if (!$query)
			{
				return FALSE;
			}
		}
		
		return TRUE; //If all ran well
	}
}

/* End of file Modify_model.php */
/* Location: ./application/models/Modify_model.php */