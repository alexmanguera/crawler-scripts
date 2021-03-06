<?php
/**************

====================
Job Portal Crawler - work.com.mm
Current Version: 1.0.1
====================

Change Log:
ver 1.0.0
- Script that crawls work.com.mm Jobs section that captures specific fields.
ver 1.0.1
- Fix Invalid Url within Loop.

**************/

	$time_start = microtime(true);

	ini_set('max_execution_time', 0);
	ini_set('memory_limit', '-1');

	$job_portal = "work.com.mm";
	
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
								$output_foreignLanguage,
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
										foreign_language,
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
										'$output_foreignLanguage',
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



	// ---- START FIRST SCRAPE ----

	$website_root = 'https://www.work.com.mm/en/top/industries/';
	$website_page_contents = getURLContents($website_root);
	
	$array_captured_link_a = array();

	$get_start = '<section class="top-list">';
	$get_end = '</section>';

	if(strpos($website_page_contents, $get_start) > strlen($get_start))
	{ 
		$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
		$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
		$select_length = $select_pos_end - $select_pos_start;

		$captured = trim(substr($website_page_contents, $select_pos_start, $select_length));

		$get_start = '<a href="';
		$get_end = '<small>';

		while(strpos($captured, $get_start) > strlen($get_start))
		{
			$select_pos_start = strpos($captured, $get_start) + strlen($get_start);
			$select_pos_end = strpos($captured, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$captured_link_raw = trim(substr($captured, $select_pos_start, $select_length));
			
			$captured_industry = substr($captured_link_raw, strpos($captured_link_raw, '">') + 2, strlen($captured_link_raw));
			
			$captured_link = 'https://www.work.com.mm'.$captured_link_raw;
			$captured_link = substr($captured_link, 0, strpos($captured_link, '">'));
			
			$array_captured_link_a[] = array($captured_industry, $captured_link);

			
			$pos = strpos($captured, $get_start);
			if ($pos !== false) {
				$captured = substr_replace($captured, '', $pos, strlen($get_start));
			}
			
			$pos = strpos($captured, $get_end);
			if ($pos !== false) {
				$captured = substr_replace($captured, '', $pos, strlen($get_start));
			}
		}
	}
	//print_r($array_captured_link_a);
	// ---- END FIRST SCRAPE ----

	
	
	

	// ---- START SECOND SCRAPE ----

	$array_captured_link_b = array();

	$counter = 0;
	$i = 0;
	
	$total_industry = count($array_captured_link_a);
	
	foreach($array_captured_link_a as $key => $value)
	{
		$raw_url = $value[1];
		
		if(strlen($raw_url) < 3)
		{
			die();
		}
		
		$website_page_contents = getURLContents($raw_url);
		
		$get_start = '<div class="col-xs-12" data-automation="jobseeker-job-container">';
		$get_end = '<section id="banner-search-bottom" class="everad bottom">';
		
		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{
			$get_start = 'We found';
			$get_end = 'jobs';
			
			if(strpos($website_page_contents, $get_start) > strlen($get_start))
			{
				$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
				$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
				$select_length = $select_pos_end - $select_pos_start;

				$total_jobs = trim(substr($website_page_contents, $select_pos_start, $select_length));
				$last_page = ceil($total_jobs / 20);
				
				for($i = 0; $i <= $last_page; $i++)
				{
					$website_page_contents = getURLContents($raw_url.'?page='.$i);
					
					$get_start = '<p class="headline3"><strong><a href="';
					$get_end = '"';
					
					if(strpos($website_page_contents, $get_start) > strlen($get_start))
					{
						$captured = $website_page_contents;

						while(strpos($captured, $get_start) > strlen($get_start))
						{
							$select_pos_start = strpos($captured, $get_start) + strlen($get_start);
							$select_pos_end = strpos($captured, $get_end, $select_pos_start);
							$select_length = $select_pos_end - $select_pos_start;

							$captured_link = trim(substr($captured, $select_pos_start, $select_length));
							$captured_link = 'https://www.work.com.mm'.$captured_link;
							
							$counter++;
							
							print_r($counter.' - '.$value[0].' - '.$captured_link."\r");
							
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
					else
					{
						continue;
					}
					// ==================================================================
					//break;
				}
				//break;
			}
			else
			{
				continue;
			}
		}
		$i++;
		
		if($i > $total_industry)
		{
			die();
		}
	}
	// ---- END SECOND SCRAPE ----

	
	
	// ==========================================================
	// function to scrape the detailed job page (last level page)
	// ==========================================================
	function scrape_detailed_page($pdo, $job_industry, $scrape_page, $job_portal, $counter)
	{
		$website_page_contents = getURLContents($scrape_page);
			
		// -------------------------------------
		// Job Title
		$get_start = '<div class="company-header-wrapper">';
		$get_end = '</h3>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;
			
			$output_jobTitle = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_jobTitle = str_replace('<h3>', "", $output_jobTitle);
		}
		else
		{
			$output_jobTitle = '';
		}
		
		// -------------------------------------
		// Job Description
		$get_start = '<h4 class="dl-title">Job Description</h4>';
		$get_end = '<!-- job description -->';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_jobDescription = strip_tags(trim(substr($website_page_contents, $select_pos_start, $select_length)));
			$output_jobDescription = str_replace('<div class="divider"></div>', "", $output_jobDescription);
			$output_jobDescription = str_replace('<div class="dl-horizontal">', "", $output_jobDescription);
			$output_jobDescription = str_replace('</div>', "", $output_jobDescription);
			$output_jobDescription = addslashes(trim(strip_tags($output_jobDescription)));
		}
		else
		{
			$output_jobDescription = '';
		}
		
		// Job Description (Requirements)
		$get_start = '<h4 class="dl-title">Position Requirements</h4>';
		$get_end = '<!-- #required skills -->';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_jobDescription_requirements = strip_tags(trim(substr($website_page_contents, $select_pos_start, $select_length)));
			$output_jobDescription_requirements = str_replace('<div class="divider"></div>', "", $output_jobDescription_requirements);
			$output_jobDescription_requirements = str_replace('<div class="dl-horizontal">', "", $output_jobDescription_requirements);
			$output_jobDescription_requirements = str_replace('</div>', "", $output_jobDescription_requirements);
			$output_jobDescription_requirements = addslashes(trim(strip_tags($output_jobDescription_requirements)));
		}
		else
		{
			$output_jobDescription_requirements = '';
		}
		
		$output_jobDescription = $output_jobDescription.' '.$output_jobDescription_requirements;
		
		// -------------------------------------
		// Company Overview
		$get_start = '<h4 class="dl-title">About the Company</h4>';
		$get_end = '<!-- #about company -->';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			
			$output_companyOverview = substr($website_page_contents, $select_pos_start, $select_length);
			$output_companyOverview = str_replace('<div class="divider"></div>', "", $output_companyOverview);
			$output_companyOverview = str_replace('<div class="dl-horizontal">', "", $output_companyOverview);
			$output_companyOverview = str_replace('</div>', "", $output_companyOverview);
			$output_companyOverview = addslashes(trim(strip_tags($output_companyOverview)));
		}
		else
		{
			$output_companyOverview = '';
		}
		
		// -------------------------------------
		// Email
		$extractedEmail = extract_email_address($output_jobDescription.$output_companyOverview);
		$extractedPhone = extract_phone($output_jobDescription.$output_companyOverview);

		$output_email = (!empty($extractedEmail)) ? json_encode($extractedEmail) : NULL;
		
		// Phone
		$output_phone = (!empty($extractedPhone)) ? json_encode($extractedPhone) : NULL;
		
		
		// -------------------------------------
		// Career Level
		$get_start = '<dt>Career level:</dt>';
		$get_end = '</dd>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_careerLevel = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_careerLevel = str_replace('<dd>', "", $output_careerLevel);
			$output_careerLevel = trim($output_careerLevel);
		}
		else
		{
			$output_careerLevel = '';
		}
		
		// -------------------------------------
		// Employment Type
		$get_start = '<dt>Contract Type:</dt>';
		$get_end = '</dd>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_employmentType = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_employmentType = str_replace('<dd>', "", $output_employmentType);
			$output_employmentType = trim($output_employmentType);
		}
		else
		{
			$output_employmentType = '';
		}
		
		// -------------------------------------
		// Minimum Work Experience
		$get_start = '<dt>Minimum years of experience:</dt>';
		$get_end = '</dd>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_MinWorkExperience = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_MinWorkExperience = str_replace('<dd>', "", $output_MinWorkExperience);
			$output_MinWorkExperience = trim($output_MinWorkExperience);
		}
		else
		{
			$output_MinWorkExperience = '';
		}
		
		// -------------------------------------
		// Minimum Education Level
		$get_start = '<dt>Degree:</dt>';
		$get_end = '</dd>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_MinEducationLevel = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_MinEducationLevel = str_replace('<dd>', "", $output_MinEducationLevel);
			$output_MinEducationLevel = trim($output_MinEducationLevel);
			$output_MinEducationLevel = addslashes(trim(strip_tags($output_MinEducationLevel)));
		}
		else
		{
			$output_MinEducationLevel = '';
		}
		
		// -------------------------------------
		// Monthly Salary Range
		$get_start = '<div class="item salaryIco">';
		$get_end = '</div>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_monthlySalaryRange = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_monthlySalaryRange = str_replace('<span>Зарплата:</span>', "", $output_monthlySalaryRange);
			$output_monthlySalaryRange = str_replace('<span class="value">', "", $output_monthlySalaryRange);
			$output_monthlySalaryRange = str_replace('</span>', "", $output_monthlySalaryRange);
			$output_monthlySalaryRange = trim($output_monthlySalaryRange);
		}
		else
		{
			$output_monthlySalaryRange = '';
		}
		
		// -------------------------------------
		// Location (City)
		$get_start = '<dt>City:</dt>';
		$get_end = '</dd>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_location = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_location = str_replace('<dd>', "", $output_location);
			$output_location = trim($output_location);
		}
		else
		{
			$output_location = '';
		}
		
		// Location (Country)
		$get_start = '<dt>Job Location:</dt>';
		$get_end = '</dd>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_location_country = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_location_country = str_replace('<dd>', "", $output_location_country);
			$output_location_country = trim($output_location_country);
		}
		else
		{
			$output_location_country = '';
		}
		
		$output_location = $output_location.', '.$output_location_country;
		
		// -------------------------------------
		// Company Name
		$get_start = '<div class="company-header-wrapper">';
		$get_end = '</a>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_companyName = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_companyName = substr($output_companyName, strpos($output_companyName, '">') + 2, strlen($output_companyName));
			$output_companyName = str_replace('</a>', '', $output_companyName);
			$output_companyName = trim($output_companyName);
		}
		else
		{
			$output_companyName = '';
		}
		
		// -------------------------------------
		// Company Logo
		$get_start = '<div class="company-logo-wrapper">';
		$get_end = '" class="';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_companyLogo = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_companyLogo = substr($output_companyLogo, strpos($output_companyLogo, '="') + 2, strlen($output_companyLogo));
			$output_companyLogo = str_replace($get_end, '', $output_companyLogo);
			$output_companyLogo = str_replace('<img src="', '', $output_companyLogo);
			$output_companyLogo = trim($output_companyLogo);
		}
		else
		{
			$output_companyLogo = '';
		}
		
		// -------------------------------------
		// Job Date Posted
		$get_start = 'Updated at: <strong>';
		$get_end = '</strong>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_jobDatePosted = trim(substr($website_page_contents, $select_pos_start, $select_length));
		}
		else
		{
			$output_jobDatePosted = NULL;
		}
		
		// ===========================================================
		// NEW FIELDS SPECIFIC FOR THIS CRAWLER
		// ===========================================================
		
		// -------------------------------------
		// Foreign Language
		$get_start = '<h4 class="dl-title">Language Skills</h4>';
		$get_end = '<!-- #language skills -->';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_foreignLanguage = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_foreignLanguage = str_replace('<div class="divider"></div>', "", $output_foreignLanguage);
			$output_foreignLanguage = str_replace('</div>', "", $output_foreignLanguage);
			$output_foreignLanguage = trim($output_foreignLanguage);
		}
		else
		{
			$output_foreignLanguage = '';
		}
		
		// -------------------------------------
		// Keyword/Skill
		$get_start = '<h4 class="dl-title">Professional Skills</h4>';
		$get_end = '<!-- #professional skills -->';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_keyword_skill = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_keyword_skill = str_replace('<div class="divider"></div>', "", $output_keyword_skill);
			$output_keyword_skill = str_replace('</div>', "", $output_keyword_skill);
			$output_keyword_skill = trim($output_keyword_skill);
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
							$output_foreignLanguage,
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