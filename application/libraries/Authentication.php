<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Authentication Library
 * ======================
 * @author Ryan Sandoval
 *
 * This library is used to authenticate the user and to handle any
 * methods regarding whether the user is logged in or not.
 *
 */
class Authentication
{
	
	/**
	 * Code Igniter Instance
	 * @var object
	 */
	protected $CI;

	/**
	 * The last error
	 * @var string
	 */
	private $error;

	/**
	 * Whether to return a specific error in login failure
	 * @var boolean
	 */
	private $specific_errors;

	/**
	 * Error messages
	 * @var array
	 */
	private $messages;

	/**
	 * The session data set by logging in must contain these values
	 * @var array
	 */
	private $session_data_base = array(
		'email', 'name', 'user_id', 'privileges', 'logged_in'
	);


	/**
	 * Constructor for the Authentication Library
	 *
	 * Loads the CI instance and various other resources.
	 */
	public function __construct()
	{
        $this->CI =& get_instance();

        //Load the user authentication model
        $this->CI->load->model('authentication/user_model');

        //Load Configuration and Set the properties
        $this->CI->load->config('authentication', TRUE);

        $this->specific_errors = $this->CI->config->item('specific_errors', 'authentication');
        $this->messages = $this->CI->config->item('messages', 'authentication');
        $this->salt = $this->CI->config->item('salt');

	}

	/**
	 * Use to login the user
	 * @param  string $email    The user Email
	 * @param  string $password The password that the user entered
	 * @return boolean           True if sucessful. False otherwise
	 */
	public function login_user($email, $password)
	{
		//Validate email first
		$user = $this->CI->user_model->get_user($email);

		if (empty($user))
		{
			//Invalid User
			$this->error = 'invalid_email';
			return FALSE;
		}

		//Validate the Password
		$stored_pass = $user->password;
		if ($stored_pass === crypt($password, $stored_pass))
		{
			//User is valid, so login
			$this->set_session_data($user);
			return TRUE;
		}
		else
		{
			//Incorect Password
			$this->error = 'invalid_pass';
			return FALSE;
		}
	}

	/**
	 * Logs the user out of the site
	 * @return boolean TRUE if successful
	 */
	public function logout_user()
	{
		//First clear the session data
		$this->unset_session_data();

		//Destroy Session
		$this->CI->session->sess_destroy();
		return TRUE;
	}

	/**
	 * Sets the session to include the user's data
	 * 
	 * @param object $user The user object taken stright from the database.
	 * @return void
	 */
	public function set_session_data($user)
	{
		$sess_data = array(
			'email' => $user->email,
			'name' 	=> $user->name,
			'user_id' => $user->user_id,
			'privileges' => $user->privileges,
			'logged_in' => TRUE
		);
		$this->validate_sess_data($sess_data);
		$this->CI->session->set_userdata($sess_data);
	}

	/**
	 * Unsets the user data set by logging in
	 * @return boolean TRUE if successful
	 */
	public function unset_session_data()
	{
		$this->CI->session->unset_userdata($this->session_data_base);
		return TRUE;
	}

	/**
	 * Ensures that the session data adheres to the
	 * structure of the session data base.
	 * 
	 * @return boolean TRUE if the data is good
	 */
	public function validate_sess_data($sess_data)
	{
		//Must be the same length
		if (count($sess_data) !== count($this->session_data_base))
		{
			show_error('Session data does not adhere to standards. Incorrect Size.');
		}

		foreach($sess_data as $key => $val)
		{
			if(!in_array($key, $this->session_data_base))
			{
				show_error('Session data does not adhere to standards. Key not in base.');
			}
		}

		return TRUE;		
	}

	/**
	 * Will check if the user is logged in.
	 *
	 * @param boolean $mode Whether to redirect when not logged in, or simply output a string. 
	 *
	 * @return	True if logged in, False if not
	 */
	function check_login($redirect = TRUE)
	{
		if (isset($this->CI->session->logged_in))
		{
			return TRUE;
		}
		if ($redirect)
		{
			redirect('login','refresh', 401);
		}
		return FALSE;
	}

	/**
	 * Will check if the user is an admin
	 *
	 * @param boolean $redirect Whether to redirect when not logged in, or simply output a string
	 *
	 * @return True if logged in, False if not
	 */
	function check_admin($redirect = FALSE)
	{
		if ($this->CI->session->user_id !== NULL && $this->CI->session->privileges == 'admin')
		{
			return TRUE;
		}
		if ($redirect)
		{
			redirect('login','refresh');
		}
		return FALSE;
	}

	/**
	 * Checks if the user has a required privilege.
	 * @param  string  $privilege The privilege that the user should have
	 * @param  boolean $redirect  Whether to redirect Home or not
	 * @return boolean            True if the user has that privilege. False otherwise
	 */
	function check_privileges($privilege, $redirect = FALSE)
	{
		if ($this->CI->session->privilege == $privilege)
		{
			return TRUE;
		}
		else
		{
			//Not authorized
			if ($redirect)
			{
				redirect('home','refresh'); 
			}
			return FALSE;
		}
	}

	/**
	 * Recover the user's account using provided email.
	 * @return boolean TRUE if Successful. False if not.
	 */
	public function recover($email)
	{
		$user = $this->CI->user_model->get_user($email);
		//Validate email
		if (empty($user))
		{
			$this->error = 'invalid_email';
			return FALSE;
		}

		//Set the password to a temporary password
		$this->CI->load->helper('string');

		$temp_pass = random_string();
		$insert_data = array('password' => crypt($temp_pass, $this->salt));
		$this->CI->user_model->update_user($user->user_id, $insert_data);

		//Generate the email
		//------------------
		
		//load Email Library and Config
		$this->CI->load->library('email');
		$this->CI->load->config('email');

		$this->CI->email->from($this->CI->config->item('smtp_user'), 'Password Manager');
		$this->CI->email->to($email);
		
		$this->CI->email->subject('Password Recovery Request');
		
		//Message formatting
		$message = $this->CI->config->item('recover_email_content', 'authentication');
		$message = str_replace('{name}', $user->name, $message);
		$message = str_replace('{link}', site_url("recover-form/{$user->user_id}/{$temp_pass}"), $message);

		$this->CI->email->message($message);

		//Send it out!
		$this->CI->email->send();

		return TRUE;
	}

	/**
	 * Reset the user's password
	 * @param int $user_id The user's ID
	 * @param string $pass The new password
	 * @return boolean TRUE if successful
	 */
	public function reset_pass($user_id, $pass)
	{
		$update_data = array('password' => crypt($pass, $this->salt));
		$this->CI->user_model->update_user($user_id, $update_data);
		return TRUE;
	}

	/**
	 * Validates the password given with password in the database
	 * @param  int $user_id   The User ID of the user
	 * @param  string $pass The password
	 * @return boolean            True if Validated. False Otherwise
	 */
	public function validate_pass($user_id, $pass)
	{
		$user = $this->CI->user_model->get_user($user_id, 'id');

		if ($user->password === crypt($pass, $user->password))
		{
			return TRUE;
		}
		
		return FALSE;
	}

	/**
	 * Gets the latest error.
	 *
	 * Error can be specific or general base on the $specific_errors config	
	 * @return string The error
	 */
	public function get_error()
	{
		if ($this->specific_errors)
		{
			return $this->messages[$this->error];
		}
		else
		{
			return $this->messages['general_error'];
		}
	}
}

/* End of file Authentication.php */
/* Location: ./application/libraries/Authentication.php */