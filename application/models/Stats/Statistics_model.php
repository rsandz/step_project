<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH.'models/Searching/Search_model.php');
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use function GuzzleHttp\json_encode;

/**
 * Statistics Model
 * ================
 * @author Ryan Sandoval
 * @package Search
 * @uses Search_Model
 *
 * The statistics model allows for the generation of log frequency and hours
 * statistics based on the action log table. The statistics can be displayed 
 * on different time intervals, which are: Daily, Weekly, Monthly, Yearly.
 *
 * The statistics model extends the search model, so it must be loaded first
 * before this can be used. This may be done through CI's loader or simply
 * by using require_once(). 
 *
 * Since this model extends the search model, filters for the statistics can
 * be set using the standard search model methods. For example:
 * 		` ...->statistics_model->keywords('Blue'); `
 * The above will limit statistics to logs that contain the keyword 'Blue'
 *
 * For more information on the search_model's filters, @see Search_Model
 * 
 * --------------------------------------------------------------------------
 * Getting the data:
 * 
 * statistics_model->get() returns the statistics data.
 * 
 * It contains:
 *  dataSets - Array of datasets (a.k.a metrics)
 *  dataSets[n][query] - The exported query for this metric
 *  dataSets[n][total] - Total Data points
 *  dataSets[n][y]     - Y values of the data set for graphing
 *  range 			   - The date range of the stats
 *  x				   - The x values of the stats for graphing
 * --------------------------------------------------------------------------
 * A note on years after 2038:
 * Dut to the Y2038 bug, if the application is running on a 32-bit PHP and server,
 * any date past 2038 will cause unexpected data to return
 *			
 */
class Statistics_model extends Search_Model {

	/**	@var string The Date intervals the data should follow */
	protected $SM_interval_type;
	/** @var string Log Frequency or Hours */
	protected $SM_metrics = array();
	/** @var array Array of labels */
	protected $SM_labels = array();

	/**
	 * Loads the necessary resources to run the statistics model. 
	 */
	public function __construct()
	{
		parent:: __construct();
	}

	/**
	 * Gets and returns the statistics data
	 */
	public function get()
	{
		//--Cached Instructions -------//
		$this->db->start_cache();
		$this->join_tables();
		$this->db->from('action_log');
		$this->db->stop_cache();
		//-----------------------------//

		foreach($this->SM_metrics as $index => $metric)
		{
			//Apply where and like filters
			$this->apply_filters();
			
			//Set the time interval
			$this->apply_interval_type();
			
			$dataset =& $data['dataSets'][$index];

			//Get total results
			$dataset['total'] = $this->db->count_all_results('', FALSE);

			//Sets the metric to get
			$this->apply_metric($metric);
			
			//Export the query so it can be used in searching later
			$dataset['query'] = $this->export_query();
			
			//Debug info
			$this->set_debug(); //TODO: fix for 2+ metrics

			//Apply Labels
			$dataset['label'] = isset($this->SM_labels[$index]) ? $this->SM_labels[$index] : NULL;

			//Get the data
			$results = $this->parse_results($this->db->get());
			$dataset['y'] = $results['y'];
			$x = $results['x'];
		}

		//Sets the range of the data
		$data['range'] = array(
			'from' => $this->SB_from_date,
			'to'   => $this->SB_to_date
		);

		//Sets the X Values
		$data['x'] = $x;
		
		$this->db->flush_cache();
		$this->reset();

		return $data;
	}

	/**	
	 * Generates an x and y array for easy graphing
	 * @param CI_DB_result $results 
	 */
	public function parse_results($results)
	{
		while ($row = $results->unbuffered_row())
		{
			$raw_x[] = new Carbon($row->x);
			$raw_y[] = $row->y;
		}

		//Fill in missing dates
		switch ($this->SM_interval_type) {
			case 'daily':
				//Create date range of days
				$date_range = new CarbonPeriod($this->SB_from_date, '1 day', $this->SB_to_date);

				//If no Data
				if (empty($raw_x))
				{
					foreach($date_range as $date)
					{
						$x[] = $date->format('Y-m-d');
						$y[] = 0;
					}
					break;
				}

				foreach ($date_range as $key => $date)
				{
					$x[] = $date->format('Y-m-d');

					$index = array_search($date, $raw_x);
					if ($index !== FALSE)
					{
						$y[] = $raw_y[$index];
					}
					else
					{
						$y[] = 0;
					}
				}
				break;
			case 'weekly':
				//Create date range of Week
				$date_range = new CarbonPeriod($this->SB_from_date, '1 week', $this->SB_to_date); 

				//If no Data
				if (empty($raw_x))
				{
					foreach($date_range as $date)
					{
						$x[] = $date->startOfWeek()->format('Y-m-d');
						$y[] = 0;
					}
					break;
				}
				
				//Modify raw x to represent start of week
				$raw_x = array_map(function($x) {return $x->startOfWeek();}, $raw_x);
				foreach ($date_range as $date)
				{	
					$x[] = $date->startOfWeek()->format('Y-m-d');
					$index = array_search($date->startOfWeek(), $raw_x);
					if ($index !== FALSE)
					{
						$y[] = $raw_y[$index];
					}
					else
					{
						$y[] = 0;
					}
				}
				break;
			case 'monthly':
				//Create date range of Week
				$date_range = new CarbonPeriod($this->SB_from_date, '1 month', $this->SB_to_date); 

				//If no Data
				if (empty($raw_x))
				{
					foreach($date_range as $date)
					{
						$x[] = $date->startOfMonth()->format('F Y');
						$y[] = 0;
					}
					break;
				}

				//Modify raw x to represent start of month
				$raw_x = array_map(function($x) {return $x->startOfMonth();}, $raw_x);
				foreach ($date_range as $date)
				{
					$x[] = $date->startOfMonth()->format('F Y');

					$index = array_search($date->startOfMonth(), $raw_x);
					if ($index !== FALSE)
					{
						$y[] = $raw_y[$index];
					}
					else
					{
						$y[] = 0;
					}
				}
				break;
			case 'yearly':
				//Create date range of Week
				$date_range = new CarbonPeriod($this->SB_from_date, '1 year', $this->SB_to_date); 

				//If no Data
				if (empty($raw_x))
				{
					foreach($date_range as $date)
					{
						log_message('error', $date->startOfYear()->format('Y'));
						$x[] = $date->startOfYear()->format('Y');
						$y[] = 0;
					}
					break;
				}

				//Modify raw x to represent start of month
				$raw_x = array_map(function($x) {return $x->startOfYear();}, $raw_x);
				foreach ($date_range as $date)
				{
					$x[] = $date->startOfYear()->format('Y');

					$index = array_search($date->startOfYear(), $raw_x);
					if ($index !== FALSE)
					{
						$y[] = $raw_y[$index];
					}
					else
					{
						$y[] = 0;
					}
				}
			default:
				$this->error('Invalid Time Interval');
				break;
		}

		return array('x' => $x, 'y' => $y);
	}

	/**	
	 * Specifies which metric to measure.
	 * Either Logs or Hours.
	 * @return statistics_model Method Chaining
	 */
	public function metrics($metrics)
	{
		$this->SM_metrics[] = $metrics;
		return $this;
	}

	/**	
	 * Specifies which time interval to use.
	 * 'daily', 'weekly', 'monthly'
	 * @return statistics_model Method Chaining
	 */
	public function interval_type($type)
	{
		$this->SM_interval_type = $type;
		return $this;
	}

	/**
	 * Chart.js will use this to label the datasets
	 * @param array|string $labels An array of labels for the metrics (Same size as metric array)
	 * 							   If a string, the first passed in will correspond
	 * 							    to the first dataset, 2nd to the 2nd data set, etc.
	 * @return statistics_model Method Chaining
	 */
	public function labels($labels)
	{
		if (is_array($labels))
		{
			$this->SM_labels = array_merge($labels, $this->SM_labels);
		}
		else
		{
			$this->SM_labels[] = $labels;
		}
		return $this;
	}

	/**	
	 * Applies the Grouping and Selecting for the time interval
	 * @return void;
	 */
	public function apply_interval_type()
	{
		switch ($this->SM_interval_type)
		{
			case 'daily':
				$this->db
					->group_by('log_date')
					->select('log_date AS x')
					->order_by('log_date', 'DESC');
				break;

			case 'weekly':
				$this->db
					->group_by('WEEKOFYEAR(log_date)')
					->select('DATE_SUB(log_date, INTERVAL (WEEKDAY(log_date)) DAY) AS x')
					->order_by('log_date', 'DESC');
				break;

			case 'monthly':
				$this->db
					->group_by('MONTH(log_date)')
					->select('CONCAT(MONTHNAME(log_date), " ", YEAR(log_date)) AS x')
					->order_by('YEAR(log_date)', 'ASC')
					->order_by('MONTH(log_date)', 'ASC');
				break;

			case 'yearly':
				$this->db
					->group_by('YEAR(log_date)')
					->select('YEAR(log_date) AS x')
					->order_by('YEAR(log_date)', 'ASC');
				break;

			default:
				$this->error('Invalid Metric '.$this->SM_metric);
				break;
		}
	}

	/**	
	 * Applies the select command that will get the proper metric
	 */
	public function apply_metric($metric)
	{
		switch($metric)
		{
			case 'logs':
				$this->db->select('COUNT(*) AS `y`');
				break;
			case 'hours':
				$this->db->select('SUM(hours) AS `y`');
				break;
			default:
				$this->error('Invalid Metric '.$metric);
				break;
		}
	}

	/**	
	 * Extends the functionality of the reset function
	 */
	public function reset()
	{
		parent::reset();
		$this->SM_interval_type = '';
		$this->SM_metrics = array();
		$this->SM_labels = array();
	}
}
/* End of file Statistics_model.php */
/* Location: ./application/models/Stats/Statistics_model.php */