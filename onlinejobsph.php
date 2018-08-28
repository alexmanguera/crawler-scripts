<?php
/**************

====================
Job Portal Crawler - onlinejobs.ph
Current Version: 1.0.0
====================

Change Log:
ver 1.0.0
- Script that crawls onlinejobs.ph Jobs section that captures specific fields.

**************/

	$time_start = microtime(true);

	ini_set('max_execution_time', 0);
	ini_set('memory_limit', '-1');

	$job_portal = "onlinejobs.ph";
	
	// =======================================
	$host     = 'localhost';
    $dbname   = 'crawler';
    $user     = 'root';
    $password = '';

    $pdo = new PDO("mysql:host=" . $host . ";dbname=" . $dbname, $user, $password);
    //$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
					
	// =======================================
	

	function insertDBJobDetails(	
								$pdo,
								$job_portal, 
								$output_industry, 
								$scrape_page, 
								$output_jobTitle, 
								$output_jobDescription, 
								$output_careerLevel, 
								$output_employmentType, 
								$output_MinWorkExperience, 
								$output_MinEducationLevel, 
								$output_monthlySalaryRange, 
								$output_location,
								$output_companyName,
								$output_companyOverview,
								$output_jobDatePosted,
								$output_email,
								$output_phone,
								$output_companyLogo
								)
	{
		
		$output_jobDescription 	= clean($output_jobDescription);
		$output_jobDatePosted 	= (!empty($output_jobDatePosted)) ? date('Y-m-d', strtotime($output_jobDatePosted)) : NULL;
		
		$job_unique_id = md5($scrape_page);

		$job_post_id  = substr(uniqid(bin2hex(openssl_random_pseudo_bytes(12))), 0, 12) . getUniqueId($pdo);
		
		$sql = "INSERT INTO job_details (
										job_unique_id,
										job_post_id,
										job_portal,
										job_industry,
										job_url,
										job_title,
										job_description,
										career_level,
										employment_type,
										min_work_experience,
										min_educational_level,
										monthly_salary_range,
										job_location,
										company_name,
										company_overview,
										job_date_posted,
										email,
										phone,
										company_logo,
										date_added
									) VALUES (
										'$job_unique_id',
										'$job_post_id',
										'$job_portal',
										'$output_industry',
										'$scrape_page',
										'$output_jobTitle',
										'$output_jobDescription',
										'$output_careerLevel',
										'$output_employmentType',
										'$output_MinWorkExperience',
										'$output_MinEducationLevel',
										'$output_monthlySalaryRange',
										'$output_location',
										'$output_companyName',
										'$output_companyOverview',
										'$output_jobDatePosted',
										'$output_email',
										'$output_phone',
										'$output_companyLogo',
										NOW()
									)
									ON DUPLICATE KEY UPDATE 
										job_portal = '$job_portal',
										job_industry = '$output_industry',
										job_title = '$output_jobTitle',
										job_description = '$output_jobDescription',
										career_level = '$output_careerLevel',
										employment_type = '$output_employmentType',
										min_work_experience = '$output_MinWorkExperience',
										min_educational_level = '$output_MinEducationLevel',
										monthly_salary_range = '$output_monthlySalaryRange',
										job_location = '$output_location',
										company_name = '$output_companyName',
										job_date_posted = '$output_jobDatePosted',
										email = '$output_email',
										phone = '$output_phone',
										company_logo = '$output_companyLogo'
									";
		//echo $sql;
		//die();
		$statement = $pdo->prepare($sql);

		$statement->execute();
		
		//echo $last_id.' | '.$scrape_page.'</br>';
	}
	
	

	//============= START DATA CAPTURE ===============



	// ---- START FIRST SCRAPE ----

	$website_root = 'https://www.onlinejobs.ph/jobseekers/jobsearch';
	$website_page_contents = getURLContents($website_root);	
	
	$get_start = 'Displaying 30 jobs of';
	$get_end = '<';
	
	if(strpos($website_page_contents, $get_start) > strlen($get_start))
	{
		$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
		$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
		$select_length = $select_pos_end - $select_pos_start;

		$total_jobs = trim(substr($website_page_contents, $select_pos_start, $select_length));
		$total_jobs = preg_replace('/\s+/', '', $total_jobs);
		$total_jobs = str_replace(',', "", $total_jobs);
		
		$last_page = ceil($total_jobs / 30);
	}
	
	$counter = 0;
	$i = 0;
	
	while($i <= $last_page)
	{
		if($i < 1)
		{
			$website_page_contents = $website_page_contents;
		}
		else
		{
			$website_page_contents = getURLContents($website_root.'/'.$i);
		}
		
		$get_start = '<!-- Display list of jobs -->';
		$get_end = '<!-- Pagination -->';
		
		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{
			$captured = $website_page_contents;
			
			$get_start = '<a href="https://www.onlinejobs.ph/jobseekers/job/';
			$get_end = '"';

			while(strpos($captured, $get_start) > strlen($get_start))
			{
				$select_pos_start = strpos($captured, $get_start) + strlen($get_start);
				$select_pos_end = strpos($captured, $get_end, $select_pos_start);
				$select_length = $select_pos_end - $select_pos_start;

				$captured_link = trim(substr($captured, $select_pos_start, $select_length));
				$captured_link = 'https://www.onlinejobs.ph/jobseekers/job/'.$captured_link;
				
				$counter++;
				
				//print_r($counter.' - '.$captured_link."\r");
				
				
				// -------------------
				// scrape the detailed job page (last page)
				scrape_detailed_page($pdo, $captured_link, $job_portal, $counter);
				// -------------------				
				
				
				$pos = strpos($captured, $get_start);
				if ($pos !== false) {
					$captured = substr_replace($captured, '', $pos, strlen($get_start));
				}
				
				$pos = strpos($captured, $get_end);
				if ($pos !== false) {
					$captured = substr_replace($captured, '', $pos, strlen($get_start));
				}
				//break;
			}
		}
		// ==================================================================
		//break;
		$i = $i + 30;
	}
	// ---- END FIRST SCRAPE ----

	
	
	
	
	// ==========================================================
	// function to scrape the detailed job page (last level page)
	// ==========================================================
	function scrape_detailed_page($pdo, $scrape_page, $job_portal, $counter)
	{
		$website_page_contents = getURLContents($scrape_page);
			
		// -------------------------------------
		// Job Title
		$get_start = '<h2 class="txt-c">';
		$get_end = '</h2>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;
			
			$output_jobTitle = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_jobTitle = addslashes(trim(strip_tags($output_jobTitle)));
		}
		else
		{
			$output_jobTitle = '';
		}
		
		// -------------------------------------
		// Job Description
		$get_start = '<!-- description -->';
		$get_end = '<!-- description -->';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_jobDescription = strip_tags(trim(substr($website_page_contents, $select_pos_start, $select_length)));
			$output_jobDescription = addslashes(trim(strip_tags($output_jobDescription)));
		}
		else
		{
			$output_jobDescription = '';
		}
		
		// -------------------------------------
		// Company Overview
		$get_start = 'xxxxxxxxxxxxxxxxxxxxx';
		$get_end = 'xxxxxxxxxxxxxxxxxxxxx';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			
			$output_companyOverview = substr($website_page_contents, $select_pos_start, $select_length);
			$output_companyOverview = addslashes(trim(strip_tags($output_companyOverview)));
		}
		else
		{
			$output_companyOverview = '';
		}
		
		// -------------------------------------
		// Email
		$extractedEmail = extract_email_address($output_jobDescription);
		$extractedPhone = extract_phone($output_jobDescription);

		$output_email = (!empty($extractedEmail)) ? json_encode($extractedEmail) : NULL;
		
		// Phone
		$output_phone = (!empty($extractedPhone)) ? json_encode($extractedPhone) : NULL;
		
		
		// -------------------------------------
		// Career Level
		$get_start = 'xxxxxxxxxxxxxxxxxxxxx';
		$get_end = 'xxxxxxxxxxxxxxxxxxxxx';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_careerLevel = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_careerLevel = str_replace('<dd>', "", $output_careerLevel);
			$output_careerLevel = addslashes(trim(strip_tags($output_careerLevel)));
		}
		else
		{
			$output_careerLevel = '';
		}
		
		// -------------------------------------
		// Employment Type
		$get_start = '<i class="fa fa-bolt"></i>';
		$get_end = '</p>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_employmentType = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_employmentType = addslashes(trim(strip_tags($output_employmentType)));
		}
		else
		{
			$output_employmentType = '';
		}
		
		// -------------------------------------
		// Minimum Work Experience
		$get_start = 'xxxxxxxxxxxxxxxxxxxxx';
		$get_end = 'xxxxxxxxxxxxxxxxxxxxx';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_MinWorkExperience = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_MinWorkExperience = trim($output_MinWorkExperience);
			$output_MinWorkExperience = addslashes(trim(strip_tags($output_MinWorkExperience)));
		}
		else
		{
			$output_MinWorkExperience = '';
		}
		
		// -------------------------------------
		// Minimum Education Level
		$get_start = 'xxxxxxxxxxxxxxxxxxxxx';
		$get_end = 'xxxxxxxxxxxxxxxxxxxxx';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_MinEducationLevel = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_MinEducationLevel = addslashes(trim(strip_tags($output_MinEducationLevel)));
		}
		else
		{
			$output_MinEducationLevel = '';
		}
		
		// -------------------------------------
		// Monthly Salary Range
		$get_start = '<i class="fa fa-money"></i>';
		$get_end = '</p>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_monthlySalaryRange = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_monthlySalaryRange = trim($output_monthlySalaryRange);
			$output_monthlySalaryRange = addslashes(trim(strip_tags($output_monthlySalaryRange)));
		}
		else
		{
			$output_monthlySalaryRange = '';
		}
		
		// -------------------------------------
		// Location (City)
		$get_start = 'xxxxxxxxxxxxxxxxxxxxx';
		$get_end = 'xxxxxxxxxxxxxxxxxxxxx';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_location = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_location = str_replace('<strong>', "", $output_location);
			$output_location = addslashes(trim(strip_tags($output_location)));
		}
		else
		{
			$output_location = '';
		}
		
		// -------------------------------------
		// Company Name
		$get_start = '<i class="fa fa-suitcase"></i>';
		$get_end = '</p>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_companyName = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_companyName = addslashes(trim(strip_tags($output_companyName)));
		}
		else
		{
			$output_companyName = '';
		}
		
		// -------------------------------------
		// Company Logo
		$get_start = 'xxxxxxxxxxxxxxxxxxxxx';
		$get_end = 'xxxxxxxxxxxxxxxxxxxxx';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_companyLogo = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_companyLogo = addslashes(trim(strip_tags($output_companyLogo)));
		}
		else
		{
			$output_companyLogo = '';
		}
		
		// -------------------------------------
		// Job Date Posted
		$get_start = '<i class="fa fa-calendar"></i>';
		$get_end = '</p>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_jobDatePosted = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_jobDatePosted = str_replace(',', "", $output_jobDatePosted);
			//$output_jobDatePosted = trim(str_replace(array("\r", "\n"), '', $output_jobDatePosted));
			
			$output_jobDatePosted_array =  explode(" ", $output_jobDatePosted);
			$converted_month = strtolower($output_jobDatePosted_array[0]);
			
			switch($converted_month)
			{
				case "jan":
					$month =  "01";
					break;
				case "feb":
					$month =  "02";
					break;
				case "mar":
					$month =  "03";
					break;
				case "apr":
					$month =  "04";
					break;
				case "may":
					$month =  "05";
					break;
				case "jun":
					$month =  "06";
					break;
				case "jul":
					$month =  "07";
					break;
				case "aug":
					$month =  "08";
					break;
				case "sep":
					$month =  "09";
					break;
				case "oct":
					$month =  "10";
					break;
				case "nov":
					$month =  "11";
					break;
				case "dec":
					$month =  "12";
					break;
			}
			$output_jobDatePosted = $output_jobDatePosted_array[2].'-'.$month.'-'.$output_jobDatePosted_array[1];
		}
		else
		{
			$output_jobDatePosted = NULL;
		}		
		
		
		
		// Job Industry
		$get_start = '<!-- skills required -->';
		$get_end = '<!-- skills required -->';
		
		$array_job_industries = array(
									'Office & Admin (Virtual Assistant)',									
									'Marketing & Sales',
									'Advertising',
									'Web Development',
									'Webmaster',
									'Graphics & Multimedia',
									'Software Development / Programming',
									'Finance &amp; Management',
									'Customer Service & Admin Support',
									'Professional Services',
									'Project Management',
									'English',
									'Writing'
									);

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_industry_section = trim(substr($website_page_contents, $select_pos_start, $select_length));
			
			$output_industry = '';
			
			foreach($array_job_industries as $key => $value)
			{
				if(strpos($output_industry_section, $value))
				{
					$output_industry = addslashes(trim(strip_tags($value)));
					break;
				}
			}
		}
		else
		{
			$output_industry = '';
		}
		
		
		if(strlen($output_industry) > 2)
		{
			// --------------------------------------------
			insertDBJobDetails(
								$pdo,
								$job_portal, 
								$output_industry, 
								$scrape_page, 
								$output_jobTitle, 
								$output_jobDescription, 
								$output_careerLevel, 
								$output_employmentType, 
								$output_MinWorkExperience, 
								$output_MinEducationLevel, 
								$output_monthlySalaryRange, 
								$output_location,
								$output_companyName,
								$output_companyOverview,
								$output_jobDatePosted,
								$output_email,
								$output_phone,
								$output_companyLogo
								);
			// --------------------------------------------
		}
	}
	// ==========================================================
	
	function extract_email_address($string)
	{
		$emails = array();
		$string = str_replace("\r\n",' ',$string);
		$string = str_replace("\n",' ',$string);

		foreach(preg_split('/ /', $string) as $token) {
			$email = filter_var($token, FILTER_VALIDATE_EMAIL);
			if ($email !== false) { 
				$emails[] = $email;
			}
		}
		return $emails;
	}
	
	
	function extract_phone($string)
	{
		$matches = array();
		
		preg_match_all('/[0-9]{4}[\-][0-9]{3}[\-][0-9]{4}|[0-9]{3}[\-][0-9]{6}|[0-9]{3}[\s][0-9]{6}|[0-9]{3}[\s][0-9]{3}[\s][0-9]{4}|[0-9]{5}[\s][0-9]{9}|[0-9]{5}[\s][0-9]{10}|[0-9]{5}[\s][0-9]{11}|[0-9]{9}|[0-9]{10}|[0-9]{11}|[0-9]{12}|[0-9]{1}[\-][0-9]{9}|[0-9]{2}[\-][0-9]{9}|[0-9]{2}[\-][0-9]{10}|[0-9]{3}[\-][0-9]{9}|[0-9]{3}[\-][0-9]{10}|[0-9]{3}[\-][0-9]{3}[\-][0-9]{4}/', $string, $matches);
		$matches = $matches[0];
		
		return $matches;
	}
	
	
	
	//============= END DATA CAPTURE ===============	







	// =============================================
	// CURL FUNCTION
	function getURLContents($this_url)
	{
		$timeout = 15;
		$retry = 3;
		$website_page_contents = false;
		$success_crawl = false;
		
		while (!$success_crawl) {
			// initialize cURL
			$ch = curl_init();

			if (strlen($this_url) > 8) {
				curl_setopt($ch, CURLOPT_URL, $this_url);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
				curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
				curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.2 (KHTML, like Gecko) Chrome/22.0.1216.0 Safari/537.2");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_REFERER, "http://www.facebook.com");
				curl_setopt($ch, CURLOPT_HEADER, true);
				curl_setopt($ch,CURLOPT_HTTPHEADER,array('HeaderName: HeaderValue'));
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				@curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
				curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
				curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'/c00kie.txt');
				// get contents
				$website_page_contents = curl_exec($ch);

				// check if there's some error's
				if(curl_error($ch))
				{
					echo 'Curl error: ' . curl_error($ch) . ".\n";
					$retry--;
					if($retry < 1)
					{
						$success_crawl = true; // just to stop crawling
					}
					else
					{
						$success_crawl = false;
						echo 'Retrying in a second. ' . $retry . ' retries left.' . "\n";
						sleep(1);
					}				
				}
				else
				{
					$success_crawl = true;
					//echo 'curl success!'."\n";
					// close cURL
					curl_close($ch);
				}
			}
			else
			{
				echo 'Invalid URL: ' . $this_url . "\n";
				$website_page_contents = '';
				$retry = 0;
			}
		}
		// return the contents
		return $website_page_contents;
	}
	// =============================================
	
	function getUniqueId($pdo) {

        $sql       = "SELECT SUBSTRING(UUID(), 1, 8) as uniqueId";
        $statement = $pdo->prepare($sql);

        $statement->execute();
        $statement->setFetchMode(PDO::FETCH_ASSOC);

        $results = $statement->fetchAll();

        return $results[0]['uniqueId'];
    }

    function checkIfSkip($pdo, $url, $jobPortal) {

        $sql       = "SELECT job_url, skip FROM job_details WHERE job_url = '$url' AND job_portal = '$jobPortal' ";
        $statement = $pdo->prepare($sql);

        $statement->execute();
        $statement->setFetchMode(PDO::FETCH_ASSOC);

        $results = $statement->fetchAll();

        if(empty($results)) 
        	return false;

        
        $skip = $results[0]['skip'];

        if($skip) 
        	return true;
        else 
        	return false;
    }
	

	function clean($str) {
    	$str = str_replace("\n", '\\n', $str);
		$str = str_replace("\r", '\\r', $str);
		$str = str_replace("\t", '\\t', $str);
		$str = str_replace("\b", '\\b', $str);
		$str = str_replace("\f", '\\f', $str);
		$str = str_replace("\u", '\\u', $str);

		return $str;
    }
?>