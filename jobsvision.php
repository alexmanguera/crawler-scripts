	<?php
/**************

====================
Job Portal Crawler - jobsvision.co.in
Current Version: 1.0.0
====================

Change Log:
ver 1.0.0
- Script that crawls jobsvision.co.in Jobs section that captures specific fields.

**************/

	$time_start = microtime(true);

	ini_set('max_execution_time', 0);
	ini_set('memory_limit', '-1');

	$job_portal = "jobsvision.co.in";
	
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
								$output_companyLogo,
								$output_keyword_skill
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
										keyword_skill,
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
										'$output_keyword_skill',
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


	$array_jobs = array(
						array('Medical' , 'http://www.jobsvision.co.in/government-job-notifications/jobs/Medical'),
						array('Banking' , 'http://www.jobsvision.co.in/government-job-notifications/jobs/Banking'),
						array('Accounting' , 'http://www.jobsvision.co.in/government-job-notifications/jobs/Accounting'),
						array('Marketing' , 'http://www.jobsvision.co.in/government-job-notifications/jobs/Marketing'),
						array('Engineering' , 'http://www.jobsvision.co.in/government-job-notifications/jobs/Engineering'),
						array('University' , 'http://www.jobsvision.co.in/government-job-notifications/jobs/University'),
						array('Defense' , 'http://www.jobsvision.co.in/government-job-notifications/jobs/Defence'),
						array('Railways' , 'http://www.jobsvision.co.in/government-job-notifications/jobs/Railways'),
						array('IT - .Net' , 'http://www.jobsvision.co.in/It-Jobs/Openings/DOTNET'),
						array('IT - Java' , 'http://www.jobsvision.co.in/It-Jobs/Openings/java'),
						array('IT - PHP' , 'http://www.jobsvision.co.in/It-Jobs/Openings/php'),
						array('IT - SQL Server' , 'http://www.jobsvision.co.in/It-Jobs/Openings/sql-server'),
						array('IT - Oracle' , 'http://www.jobsvision.co.in/It-Jobs/Openings/oracle'),
						array('IT - MySQL Database' , 'http://www.jobsvision.co.in/It-Jobs/Openings/mysql-database'),
						array('IT - SEO Analyst' , 'http://www.jobsvision.co.in/It-Jobs/Openings/seo-analyst'),
						array('IT - Networking' , 'http://www.jobsvision.co.in/It-Jobs/Openings/Networking'),
						array('IT - CSS' , 'http://www.jobsvision.co.in/It-Jobs/Openings/css'),
						array('IT - Software Testing' , 'http://www.jobsvision.co.in/It-Jobs/Openings/software-testing')
						);

	shuffle($array_jobs);
	
	// ---- START FIRST SCRAPE ----

	$counter = 0;
	$i = 0;
	
	foreach($array_jobs as $key => $value)
	{
		$raw_url = $value[1];
		
		$i = 0;
		
		$last_page = false;			
		
		$website_page_contents = getURLContents($raw_url);
		
		$get_start = '<ul class="searchResults">';
		$get_end = '</ul>';
		
		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{
			$captured = $website_page_contents;
			
			$get_start = '<h4><a href="../';
			$get_end = '"';

			while(strpos($captured, $get_start) > strlen($get_start))
			{		
				$select_pos_start = strpos($captured, $get_start) + strlen($get_start);
				$select_pos_end = strpos($captured, $get_end, $select_pos_start);
				$select_length = $select_pos_end - $select_pos_start;

				$captured_link = trim(substr($captured, $select_pos_start, $select_length));
				$captured_link = str_replace('../', "", $captured_link);
				$captured_link = 'http://www.jobsvision.co.in/'.$captured_link;
				
				$counter++;
				
				//print_r($counter.' - '.$value[0].' - '.$captured_link."\r");
					
				if(!checkIfSkip($pdo, $captured_link, $job_portal)) {
					// -------------------
					// scrape the detailed job page (last page)
					scrape_detailed_page($pdo, $value[0], $captured_link, $job_portal, $counter);
					// -------------------				
				} else {
					print_r('SKIPPING - ' .$counter.' - '.$captured_link."\r");
				}

				
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
		
		$get_start = ')">Last</a>';
		$get_end = '</span>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{
			$last_page = false;
		}
		else
		{
			$last_page = true;
		}
			
		preg_match_all("/id=\"__VIEWSTATE\" value=\"(.*?)\"/", $website_page_contents, $arr_viewstate);
		$viewstate = urlencode($arr_viewstate[1][0]);
		$viewstate = urldecode($viewstate);
		
		preg_match_all("/id=\"__EVENTVALIDATION\" value=\"(.*?)\"/", $website_page_contents, $arr_eventvalidation);
		$eventvalidation = urlencode($arr_eventvalidation[1][0]);
		$eventvalidation = urldecode($eventvalidation);			
			
		while($last_page !== true)
		{
			$array_fields_string = array(
									'__EVENTTARGET' => 'ctl00$ContentPlaceHolder1$dpager$ctl02$ctl00',
									'__EVENTARGUMENT' => '',
									'__EVENTVALIDATION' => $eventvalidation,
									'__VIEWSTATE' => $viewstate
									);
						
			$website_page_contents = getURLContentsPost($raw_url, $array_fields_string);
			
			preg_match_all("/id=\"__VIEWSTATE\" value=\"(.*?)\"/", $website_page_contents, $arr_viewstate);
			$viewstate = urlencode($arr_viewstate[1][0]);
			$viewstate = urldecode($viewstate);
			
			preg_match_all("/id=\"__EVENTVALIDATION\" value=\"(.*?)\"/", $website_page_contents, $arr_eventvalidation);
			$eventvalidation = urlencode($arr_eventvalidation[1][0]);
			$eventvalidation = urldecode($eventvalidation);			
			
			$i++;
			
			$get_start = '<ul class="searchResults">';
			$get_end = '</ul>';
			
			if(strpos($website_page_contents, $get_start) > strlen($get_start))
			{
				$captured = $website_page_contents;
				
				$get_start = '<h4><a href="../';
				$get_end = '"';

				while(strpos($captured, $get_start) > strlen($get_start))
				{		
					$select_pos_start = strpos($captured, $get_start) + strlen($get_start);
					$select_pos_end = strpos($captured, $get_end, $select_pos_start);
					$select_length = $select_pos_end - $select_pos_start;

					$captured_link = trim(substr($captured, $select_pos_start, $select_length));
					$captured_link = str_replace('../', "", $captured_link);
					$captured_link = 'http://www.jobsvision.co.in/'.$captured_link;
					
					$counter++;
					
					//print_r($counter.' - '.$value[0].' - '.$captured_link."\r");
						
					if(!checkIfSkip($pdo, $captured_link, $job_portal)) {
						// -------------------
						// scrape the detailed job page (last page)
						scrape_detailed_page($pdo, $value[0], $captured_link, $job_portal, $counter);
						// -------------------				
					} else {
						print_r('SKIPPING - ' .$counter.' - '.$captured_link."\r");
					}

					
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
			
			$get_start = ')">Last</a>';
			$get_end = '</span>';

			if(strpos($website_page_contents, $get_start) > strlen($get_start))
			{
				$last_page = false;
			}
			else
			{
				$last_page = true;
			}
			//break;
		}
		//break;
	}
	// ---- END FIRST SCRAPE ----
	
	
	// =======================================
	// function to scrape the detailed section
	// =======================================
	function scrape_detailed_page($pdo, $job_industry, $scrape_page, $job_portal, $counter)
	{
		$website_page_contents = getURLContents($scrape_page);
		
		// -------------------------------------
		// Job Title
		$get_start = '<title>';
		$get_end = '</title>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;
			
			$output_jobTitle = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_jobTitle = substr($output_jobTitle, strpos($output_jobTitle, '-') + 1, strlen($output_jobTitle));
			$output_jobTitle = str_replace('Post.', "", $output_jobTitle);
			$output_jobTitle = str_replace('Posts', "", $output_jobTitle);
			$output_jobTitle = addslashes(trim(strip_tags($output_jobTitle)));
		}
		else
		{
			$output_jobTitle = '';
		}
		
		// -------------------------------------
		// Company Name
		$get_start = '<h5 class="orangeText">';
		$get_end = '</h5>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_companyName = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_companyName = str_replace('Jobs Openings in', "", $output_companyName);
			$output_companyName = addslashes(trim(strip_tags($output_companyName)));
		}
		else
		{
			$output_companyName = '';
		}
		
		// -------------------------------------
		// Job Description
		$output_jobDescription_qualifications = '';
		
		$get_start = '<span style="font-weight: bold;">Job Description:</span>';
		$get_end = '<span style="font-weight: bold;">';

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
			$get_start = '<aside>Age Limits:</aside>';
			$get_end = '</span>';

			if(strpos($website_page_contents, $get_start) > strlen($get_start))
			{ 
				$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
				$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
				$select_length = $select_pos_end - $select_pos_start;

				$output_jobDescription_age = strip_tags(trim(substr($website_page_contents, $select_pos_start, $select_length)));
				$output_jobDescription_age = str_replace('<span>', "", $output_jobDescription_age);
				$output_jobDescription_age = addslashes(trim(strip_tags($output_jobDescription_age)));
			}
			else
			{
				$output_jobDescription_age = '';
			}		
			
			$get_start = '<aside>Qualification:</aside>';
			$get_end = '</span>';

			if(strpos($website_page_contents, $get_start) > strlen($get_start))
			{ 
				$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
				$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
				$select_length = $select_pos_end - $select_pos_start;

				$output_jobDescription_qualifications = strip_tags(trim(substr($website_page_contents, $select_pos_start, $select_length)));
				$output_jobDescription_qualifications = str_replace('<span>', "", $output_jobDescription_qualifications);
				$output_jobDescription_qualifications = addslashes(trim(strip_tags($output_jobDescription_qualifications)));
			}
			else
			{
				$output_jobDescription_qualifications = '';
			}
			
			$get_start = '<aside>How to Apply:</aside>';
			$get_end = '</span>';

			if(strpos($website_page_contents, $get_start) > strlen($get_start))
			{ 
				$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
				$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
				$select_length = $select_pos_end - $select_pos_start;

				$output_jobDescription_application = strip_tags(trim(substr($website_page_contents, $select_pos_start, $select_length)));
				$output_jobDescription_application = str_replace('<span>', "", $output_jobDescription_application);
				$output_jobDescription_application = addslashes(trim(strip_tags($output_jobDescription_application)));
			}
			else
			{
				$output_jobDescription_application = '';
			}
			
			$output_jobDescription = $output_jobDescription_age.' '.$output_jobDescription_qualifications.' '.$output_jobDescription_application;
		}
		
		// -------------------------------------
		// Company Overview
		$get_start = '<span style="font-weight: bold;">Company Profile:</span>';
		$get_end = '<span style="font-weight: bold;">Job Description:';

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
			$get_start = '<span style="font-weight: bold;">About';
			$get_end = '</section>';

			if(strpos($website_page_contents, $get_start) > strlen($get_start))
			{ 
				$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
				$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
				$select_length = $select_pos_end - $select_pos_start;

				
				$output_companyOverview = substr($website_page_contents, $select_pos_start, $select_length);
				$output_companyOverview = str_replace('&nbsp;', "", $output_companyOverview);
				$output_companyOverview = addslashes(trim(strip_tags($output_companyOverview)));
			}
			else
			{
				$output_companyOverview = '';
			}
		}
		
		// -------------------------------------
		// Email
		$extractedEmail = extract_email_address($output_jobDescription.' '.$output_companyOverview);
		$extractedPhone = extract_phone($output_jobDescription.' '.$output_companyOverview);

		$output_email = (!empty($extractedEmail)) ? json_encode($extractedEmail) : NULL;
		
		// Phone
		$output_phone = (!empty($extractedPhone)) ? json_encode($extractedPhone) : NULL;
		
		
		// -------------------------------------
		// Career Level
		$get_start = 'xxxxxxxxxxxxxxxxxxxxxxxx';
		$get_end = 'xxxxxxxxxxxxxxxxxxxxxxxx';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_careerLevel = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_careerLevel = trim($output_careerLevel);
			$output_careerLevel = addslashes(trim(strip_tags($output_careerLevel)));
		}
		else
		{
			$output_careerLevel = '';
		}
		
		// -------------------------------------
		// Employment Type
		$get_start = 'xxxxxxxxxxxxxxxxxxxxxxxx';
		$get_end = 'xxxxxxxxxxxxxxxxxxxxxxxx';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_employmentType = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_employmentType = str_replace('<dd class="temp">', "", $output_employmentType);
			$output_employmentType = addslashes(trim(strip_tags($output_employmentType)));
		}
		else
		{
			$output_employmentType = '';
		}
		
		// -------------------------------------
		// Minimum Work Experience
		$get_start = '<aside>Experience:</aside>';
		$get_end = '</span>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_MinWorkExperience = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_MinWorkExperience = str_replace('<span>', "", $output_MinWorkExperience);
			$output_MinWorkExperience = addslashes(trim(strip_tags($output_MinWorkExperience)));
		}
		else
		{
			$output_MinWorkExperience = '';
		}
		
		// -------------------------------------
		// Minimum Education Level
		$get_start = '>Qualification:</span>';
		$get_end = '</div>';

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
			if(strlen($output_jobDescription_qualifications) > 2)
			{
				$output_MinEducationLevel = $output_jobDescription_qualifications;
			}
			else
			{
				$output_MinEducationLevel = '';
			}
		}
		
		// -------------------------------------
		// Monthly Salary Range
		$get_start = 'xxxxxxxxxxxxxxxxxxxxxxxx';
		$get_end = 'xxxxxxxxxxxxxxxxxxxxxxxx';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_monthlySalaryRange = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_monthlySalaryRange = str_replace('<dd>', "", $output_monthlySalaryRange);
			$output_monthlySalaryRange = preg_replace('/\s+/', '', $output_monthlySalaryRange);
			$output_monthlySalaryRange = addslashes(trim(strip_tags($output_monthlySalaryRange)));
		}
		else
		{
			$output_monthlySalaryRange = '';
		}
		
		// -------------------------------------
		// Location
		$get_start = '<h1 class="cmpName_Tittle"><b>';
		$get_end = ')</p>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_location = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_location = substr($output_location, strpos($output_location, '<p>(') + 4, strlen($output_location));
			$output_location = addslashes(trim(strip_tags($output_location)));
		}
		else
		{
			$get_start = '<aside>Location:</aside>';
			$get_end = '</span>';

			if(strpos($website_page_contents, $get_start) > strlen($get_start))
			{ 
				$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
				$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
				$select_length = $select_pos_end - $select_pos_start;

				$output_location = trim(substr($website_page_contents, $select_pos_start, $select_length));
				$output_location = str_replace('<span>', "", $output_location);
				$output_location = addslashes(trim(strip_tags($output_location)));
			}
			else
			{
				$output_location = '';
			}
		}
		
		// -------------------------------------
		// Company Logo
		$get_start = '<img id="ctl00_ContentPlaceHolder1_img_Logo"';
		$get_end = '" alt';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_companyLogo = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_companyLogo = substr($output_companyLogo, strpos($output_companyLogo, 'src="') + 5, strlen($output_companyLogo));
			$output_companyLogo = addslashes(trim(strip_tags($output_companyLogo)));
		}
		else
		{
			$output_companyLogo = '';
		}

		// -------------------------------------
		// Job Date Posted
		$get_start = '<aside>Last Date:</aside>';
		$get_end = '</span>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;
			
			$output_jobDatePosted = trim(substr($website_page_contents, $select_pos_start, $select_length));
		
			
			$output_jobDatePosted = str_replace('<span>', "", $output_jobDatePosted);
			$output_jobDatePosted = preg_replace('/\s+/', '', $output_jobDatePosted);

			if(strlen($output_jobDatePosted) < 5)
			{
				$output_jobDatePosted = '';
			}
			else
			{
				$output_jobDatePosted_array =  explode("-", $output_jobDatePosted);
				$output_jobDatePosted = $output_jobDatePosted_array[2].'-'.$output_jobDatePosted_array[0].'-'.$output_jobDatePosted_array[1];
			}
		}
		else
		{
			$get_start = '<aside>Posted On:</aside>';
			$get_end = '</span>';

			if(strpos($website_page_contents, $get_start) > strlen($get_start))
			{ 
				$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
				$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
				$select_length = $select_pos_end - $select_pos_start;
				
				$output_jobDatePosted = trim(substr($website_page_contents, $select_pos_start, $select_length));
			
				
				$output_jobDatePosted = str_replace('<span>', "", $output_jobDatePosted);
				$output_jobDatePosted = preg_replace('/\s+/', '', $output_jobDatePosted);

				if(strlen($output_jobDatePosted) < 5)
				{
					$output_jobDatePosted = '';
				}
				else
				{
					$output_jobDatePosted_array =  explode("/", $output_jobDatePosted);
					$output_jobDatePosted = $output_jobDatePosted_array[2].'-'.$output_jobDatePosted_array[0].'-'.$output_jobDatePosted_array[1];
				}
			}
			else
			{
				$output_jobDatePosted = '';
			}
		}
		
		// -------------------------------------
		// Keyword/Skill
		$get_start = 'xxxxxxxxxxxxx';
		$get_end = 'xxxxxxxxxxxxx';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_keyword_skill = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_keyword_skill = addslashes(trim(strip_tags($output_keyword_skill)));
		}
		else
		{
			$output_keyword_skill = '';
		}
		
		
		
		
		$output_industry = $job_industry;
		
		
		
		
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
							$output_companyLogo,
							$output_keyword_skill
							);
		// --------------------------------------------
		
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
		$timeout = 8;
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
	
	
	// POST method
	function getURLContentsPost($this_url, $fields_string)
	{
		$timeout = 8;
		$retry = 10;
		$website_page_contents = false;
		$success_crawl = false;
		
		while (!$success_crawl) {
			// initialize cURL
			$ch = curl_init();

			if (strlen($this_url) > 8) {
				curl_setopt($ch, CURLOPT_URL, $this_url);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
				curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
				curl_setopt($ch, CURLOPT_HEADER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				
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