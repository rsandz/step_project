<?php 
/**
 * User Controller
 * ===============
 * Written by: Ryan Sandoval, May 2018
 *
 * This controller handles user-specific functionality such as displaying the dashboard and user specific statistics.
 * It also handles the login process.
 */
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->model('user_model');
		$this->load->library('session');
		$this->load->helper('url');
	}

	public function index()
	{
		$this->check_login(); //Ensures that the user is logged in.

		$data['title']='Dashboard';
		$data['name'] = $this->session->name;

		$data['header'] = array(
			'text' => 'Hello '.$data['name'].', Welcome to your Dashboard',
			'colour' => 'is-info');

		$data['privileges'] = $this->session->privileges;

		$this->load->view('templates/header', $data);
		$this->load->view('templates/hero-head');
		$this->load->view('templates/navbar');
		$this->load->view('user/tabs', $data);

		//Loads table for previous entries
		$this->load->model('logging_model');
		$data['entries_table'] = $this->logging_model->get_entries_table(10)['table'];
		$this->load->view('logging/user-entries', $data); 

		$this->load->view('templates/footer');
	}

	/**
	 * Controller for login page
	 */
	public function loginUI() {
		$data['title'] = 'login';

		$this->load->helper('form');
		$this->load->library('form_validation');

		$this->form_validation->set_rules('email', 'Email', 'required|valid_email');
		$this->form_validation->set_rules('password', 'Password', 'required');

		if ($this->form_validation->run() === FALSE) {
			$this->load->view('templates/header', $data);
			$this->load->view('login/login');
			$this->load->view('templates/footer');
		} else {
			$result = $this->user_model->login_user();

			if ($result !== 'Loged_in') {
				$data['errors'] = $result;

				$this->load->view('templates/header', $data);
				$this->load->view('login/login', $data);
				$this->load->view('templates/footer');
			} else {
				redirect('home');
			}
		}
	}
	/**
	 * Logs user out.
	 */
	public function logout() {

		$this->session->sess_destroy();
		redirect('welcome');
	}

	public function check_login() 
	{
		if (!$this->session->logged_in)
		{
			redirect('login','refresh');
		} 
	}

	public function recover_password()
	{
		$data = array(
			'title' => 'Password Reset',
			);

		//Load Form Validatin Libraries ann models
		$this->load->library('form_validation');
		$this->load->helper('form');

		$this->form_validation->set_rules('email','Email','required|valid_email|callback_in_database');

		if ($this->form_validation->run() == TRUE) {
			//Get email from post
			$email = $this->input->post('email');

			$user_data = $this->user_model->recovery_data($email);

			//load Email Library
			$this->load->library('email');
			//Load email config
			$this->load->config('email');

			$this->email->from($this->config->item('smtp_user'), $this->config->item('recovery_name'));
			$this->email->to($email);
			
			$this->email->subject('Password Recovery Request');
			
			//Message formatting
			$message = $this->config->item('recovery_message');
			$message = str_replace('{name}', $user_data['name'], $message);
			$message = str_replace('{recovery_name}', $this->config->item('recovery_name'), $message);
			$message = str_replace('{link}', site_url('recover-form/'.$user_data['user_id'].'/'.$user_data['email_code']), $message);

			$this->email->message($message);
				
			$this->email->send(FALSE);
			echo $this->email->print_debugger();
		} 
		else 
		{

			$this->load->view('templates/header', $data);
			$this->load->view('login/recover', $data);
			$this->load->view('templates/error', $data);
		}

		
	}

	public function in_database($email) {
		$in_database = $this->user_model->email_in_database($email);

		if ($in_database)
		{
			return True;
		}
		else
		{
			$this->form_validation->set_message('in_database', 'Invalid Email');
			return FALSE;
		}
	}

	public function recover_form($user_id, $email_code)
	{
		$this->load->library('form_validation');

		$this->form_validation->set_rules('password', 'Password', 'required');
		$this->form_validation->set_rules('password_confirm', 'Confirm Password', 'required|matches[password]');

		if ($this->form_validation->run())
		{
			$reset_hash = $this->input->post('reset_hash');
			if ($this->user_model->validate_reset_hash($user_id, $email_code, $reset_hash))
			{
				$data['title'] = 'Reset Successful';

				$this->user_model->reset_password($user_id, $this->input->post('password'));
				$this->load->view('templates/header', $data);
				$this->load->view('user/reset/success', $data);
			}
			else
			{
				show_error('Error during password reset.', 400);
			}
		}
		else
		{
			//Validate Email Code
			if (password_verify($this->user_model->user_email($user_id), $email_code))
			{
				$reset_hash = $this->user_model->get_reset_hash($user_id, $email_code);
			
				$data['reset_hash'] = $reset_hash;
				$data['email_code'] = $email_code;
				$data['user_id'] = $user_id;
			
				$data['title'] = 'Reset Password';
				$data['show_form_errors'] = TRUE;
			
				$this->load->view('templates/header', $data);
				$this->load->view('user/reset/reset_form', $data);
				$this->load->view('templates/error');
			}
			else
			{
				show_error('Invalid Link', 401);
			}
		}
		
	}


}