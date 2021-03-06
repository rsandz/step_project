<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once('Investigate_base.php');

/**
 * Incident Builder Library
 * =============================
 * @author Ryan Sandoval
 * @version 1.0
 * @package Investigation
 *
 * This library contains the investigation
 * builder methods.
 * ---------------------------------------
 *
 * Creating incidents is similar to Code Igniter's
 * database query builder.
 * The following methods allow the incident's properties
 * to be set:
 *  - name 				Name of incident
 *  - date 				Date of the incident
 *  - time 				Time of the incident
 *  - desc 				Description of the incident
 *  - auto 				Was this incident created automatically? Or by user?
 *  					Default: TRUE
 *  - user 				The user that created the incident
 * To actually create the incident, simlply call create().
 *
 * Example:
 * ```
 * $this->load->library('investigation_builder');
 * $this->investigation_builder
 * 	->name('Incident 1')
 * 	->date('now')
 * 	->desc('Incident 1 caused something. Plz help!')
 * 	->auto(TRUE)
 * 	->user(1)
 */
class Incident_builder extends Investigate_base
{
	protected $IL_name = NULL;
	protected $IL_date = NULL;
	protected $IL_time = NULL;
	protected $IL_desc = '';
	protected $IL_auto = TRUE;
	protected $IL_user = NULL;

	/**
	 * Set to True to prevent emails from being sent
	 * @var boolean
	 */
	protected $is_silent = FALSE;

	/** 
	 * User ID for creating new incidents and logging.
	 * @var int
	 */
	protected $user_id;

	/**
	 * Initializes the Investigation Library
	 * @param int  $user_id The User ID used for logging and creating new incidents.
	 * @param boolean $logging Set to TRUE to enable logging functionalities.
	 */
	public function __construct($user_id = NULL, $logging = TRUE)
	{
		parent::__construct();
        //The library will check to see if user_id was passed. Otherwise, use the one
        //set in the session.
		$this->user_id = $user_id ?: $this->CI->session->user_id;
	}

	/**
	 * Sets the name of the incident
	 * @param  string $name The name
	 * @return Investigation       Method Chaining
	 */
	public function name($name)
	{
		if(empty($name))
		{
			$this->error('Name Field received an empty string.');
			return $this;
		}

		$this->IL_name = $name;
		return $this;
	}

	/**
	 * Sets the date of the incident
	 * @param  string $date The Date
	 * @return Investigation       Method Chaining
	 */
	public function date($date)
	{
		if (empty($date))
		{
			$this->error('Date Field received an empty string');
			return $this;
		}
		if ($date == 'now')
		{
			$this->IL_date = date('Y-m-d');
			$this->IL_time = date('H:i:s');
			return $this;
		}

		//PHP auto-format
		$date = strtotime($date);
		$date = date('Y-m-d', $date);

		$this->IL_date = $date;
		return $this;
	}

	/**
	 * Sets the time of the incident
	 * @param  string $time Time
	 * @return Investigation      Method Chaining
	 */
	public function time($time)
	{
		if (empty($time))
		{
			$this->error('Time Field received an empty string');
			return $this;
		}
		if ($time == 'now')
		{
			$this->IL_date = date('Y-m-d');
			$this->IL_time = date('H:i:s');
			return $this;
		}

		//PHP auto-format
		$time = strtotime($time);
		$time = date('H:i:s', $time);

		$this->IL_time = $time;
		return $this;
	}

	/**
	 * Sets the incident description
	 * @param  string $desc The description
	 * @return Investigation    Method Chaining
	 */
	public function desc($desc)
	{
		$this->IL_desc .= $desc;
		return $this;
	}

	/**
	 * Sets whether log was automated or not
	 * @param  boolean $value Whether it was automated or not
	 * @return Investigation        Method chaining
	 */
	public function auto($value)
	{
		$this->IL_auto = filter_var($value, FILTER_VALIDATE_BOOLEAN);
		if (!isset($this->IL_auto))
		{
			$this->error('Automated Field did received incorrect data');
		}
		return $this;
	}

	/**
	 * Sets the user
	 * @param  string $users User ID
	 * @return Investigation        Method Chaining
	 */
	public function user($user)
	{
		if (empty($users))
		{
			$this->error('User Field reveived an empty string');
			return $this;
		}
		
		$this->IL_user = $user;
		return $this;
	}


	/**
	 * Use to create a new incident/
	 * Formats the data then passes it to the investigation model.
	 * @param  array $insert_data The data array to insert
	 * @return boolean            True if successful
	 */
	public function create()
	{
		//Assume it was automated if not explicitly declared
		if (!isset($this->IL_auto))
		{
			$this->IL_auto  = TRUE;
		}

		//If no user id is set in 'created_by' key,
		//and it was not automated, then the current user
		//will be set.
		if (!isset($this->IL_user) && !$this->IL_auto)
		{
			$this->IL_user =  $this->CI->session->user_id;
		}

		//Other info for the incident. TODO

		//Create the array
		$insert_data = array(
			'incident_name' => $this->IL_name,
			'incident_date' => $this->IL_date,
			'incident_time' => $this->IL_time,
			'incident_desc' => $this->IL_desc,
			'was_automated' => $this->IL_auto,
			'created_by'	=> $this->IL_user
		);

		//Put into Database
		$this->CI->investigation_model->insert_incident($insert_data);

		//Notify
		if (!$this->is_silent) 
		{
			//email
			$this->CI->load->library('Investigation/email_notifier');
			$this->CI->email_notifier
				->incident($this->CI->investigation_model->insert_id)
				->new_incident_notify();

			//sms
			$this->CI->load->library('Investigation/sms_sender');
			$this->CI->sms_sender
				->incident($this->CI->investigation_model->insert_id)
				->notify_incident();
		}

		$this->reset();

		return TRUE;

	}

	/**
	 * Resets the stored incident data to their defaults
	 * @return void
	 */
	public function reset()
	{
		$to_reset = array(
			'IL_name' => NULL,
			'IL_date' => NULL,
			'IL_time' => NULL,
			'IL_desc' => '',
			'IL_auto' => TRUE,
			'IL_user' => NULL,
			'user_id' => NULL,
		);

		foreach ($to_reset as $item => $default)
		{
			$this->{$item} = $default;
		}
	}
}

/* End of file Investigation_builder.php */
/* Location: ./application/libraries/Investigation/Investigation_builder.php */
