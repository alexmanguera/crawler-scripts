<?php
/**************

====================
Job Portal Crawler - monster.com.ph
Current Version: 1.0.0
====================

Change Log:
ver 1.0.0
- Script that crawls monster.com.ph Jobs section that captures specific fields.

**************/
	$time_start = microtime(true);

	ini_set('max_execution_time', 0);
	ini_set('memory_limit', '-1');

	$job_portal = "monster.com.ph";
	
	// =======================================

	$host     = 'localhost';
    $dbname   = 'crawler';
    $user     = 'root';
    $password = '';

    $pdo = new PDO("mysql:host=" . $host . ";dbname=" . $dbname, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
					
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
								$output_nationality,
								$output_function,
								$output_role,
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
										nationality,
										function,
										role,
										keyword_skill
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
										'$output_nationality',
										'$output_function',
										'$output_role',
										'$output_keyword_skill'
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
										company_overview = '$output_companyOverview',
										job_date_posted = '$output_jobDatePosted',
										email = '$output_email',
										phone = '$output_phone',
										company_logo = '$output_companyLogo'
									";
							

		$statement = $pdo->prepare($sql);

		if($statement->execute()) {
			print 'Failed - ' . $scrape_page . '\n';
		} else {
			print 'Success - ' . $scrape_page . '\n';
		}
		
		//echo $last_id.' | '.$scrape_page.'</br>';
	}
	
	

	//============= START DATA CAPTURE ===============



	// ---- START FIRST SCRAPE ----

	$website_root = 'http://jobsearch.monster.com.ph/industry/jobs-by-industry.html';
	$website_page_contents = getURLContents($website_root);

	$array_captured_link_a = array();

	$get_start = '<div role="tabpanel" class="tab-pane padd20 pdtb30 active jobsby">';
	$get_end = '<div class="col-md-3 hidden-sm hidden-xs">';

	if(strpos($website_page_contents, $get_start) > strlen($get_start))
	{ 
		$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
		$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
		$select_length = $select_pos_end - $select_pos_start;

		$captured = trim(substr($website_page_contents, $select_pos_start, $select_length));

		$get_start = '<a href="';
		$get_end = '</a>';

		while(strpos($captured, $get_start) > strlen($get_start))
		{
			$select_pos_start = strpos($captured, $get_start) + strlen($get_start);
			$select_pos_end = strpos($captured, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$captured_link_raw = trim(substr($captured, $select_pos_start, $select_length));
			
			$captured_industry = substr($captured_link_raw, strpos($captured_link_raw, '"') + 2, strlen($captured_link_raw));
			$captured_industry = str_replace('class="ar_lnk">', "", $captured_industry);
			$captured_link = substr($captured_link_raw, strpos($captured_link_raw, "href='"), strpos($captured_link_raw, '"'));
			
			$array_captured_link_a[] = array($captured_industry, $captured_link);

			$captured = str_replace($get_start.$captured_link_raw.$get_end, "", $captured);
		}
	}
	//print_r($array_captured_link_a);
	// ---- END FIRST SCRAPE ----

	
	
	// ---- START SECOND SCRAPE ----

	$array_captured_link_b = array();

	$counter = 0;
	
	foreach($array_captured_link_a as $key => $value)
	{
		$raw_url = $value[1];
		$i = 0;
		
		$website_page_contents = getURLContents($raw_url);
		
		$captured_a = $website_page_contents;
		
		$count_occurance = substr_count($captured_a, "',this)");
		
		if($count_occurance < 1)
		{
			$count_occurance = 1;
		}

		for($i=1; $i<=$count_occurance;$i++)
		{
			// ==================================================================
			
			$raw_url_with_pagination = str_replace('.html', '-'.$i.'.html', $value[1]);
			//echo $raw_url_with_pagination;
			
			$array_fields_string = array('n' => $i);
					
			$website_page_contents = getURLContentsPost($raw_url_with_pagination, $array_fields_string);

			$get_start = '<a itemscope itemtype="http://schema.org/JobPosting" class="joblnk" target="_blank" href="';
			$get_end = '">';
			
			$captured = $website_page_contents;

			while(strpos($captured, $get_start) > strlen($get_start))
			{
				$select_pos_start = strpos($captured, $get_start) + strlen($get_start);
				$select_pos_end = strpos($captured, $get_end, $select_pos_start);
				$select_length = $select_pos_end - $select_pos_start;

				$captured_link = trim(substr($captured, $select_pos_start, $select_length));
				
				$array_captured_link_b[] = array($value[0], $captured_link);
				
				$counter++;
				
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
			// ==================================================================
			//break;
		}
		//break;
	}
	//print_r($array_captured_link_b);
	// ---- END SECOND SCRAPE ----

	

	
	
	// ==========================================================
	// function to scrape the detailed job page (last level page)
	// ==========================================================
	function scrape_detailed_page($pdo, $job_industry, $scrape_page, $job_portal, $counter)
	{
		$website_page_contents = getURLContents($scrape_page);
		
		// -------------------------------------
		// Job Title
		$get_start = '<div class="job_title"><h1 class="cmp_jd">';
		$get_end = '</h1>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;
			
			$output_jobTitle = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_jobTitle = trim(strip_tags($output_jobTitle));
		}
		else
		{
			$output_jobTitle = '';
		}

		// -------------------------------------
		// Job Description
		$get_start = '<div class="heading">Job Description</div>';
		$get_end = '<div class="row">';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_jobDescription = substr($website_page_contents, $select_pos_start, $select_length);
			$output_jobDescription = str_replace("&nbsp;", " ", $output_jobDescription);
			$output_jobDescription = str_replace("\n", " ", $output_jobDescription);
			$output_jobDescription = str_replace("\t", " ", $output_jobDescription);
			$output_jobDescription = str_replace('<', ' <', $output_jobDescription);
			$output_jobDescription_raw = $output_jobDescription;
			$output_jobDescription = addslashes(trim(strip_tags($output_jobDescription_raw)));
		}
		else
		{
			$output_jobDescription = '';
			$output_jobDescription_raw = '';
		}
		
		
		// -------------------------------------
		// Company Overview / About Company
		$get_start = '<div class="heading">About Company</div>';
		$get_end = '<div class="row">';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_companyOverview = substr($website_page_contents, $select_pos_start, $select_length);
			$output_companyOverview = str_replace("&nbsp;", " ", $output_companyOverview);
			$output_companyOverview = str_replace("\n", " ", $output_companyOverview);
			$output_companyOverview = str_replace("\t", " ", $output_companyOverview);
			$output_companyOverview = str_replace('<', ' <', $output_companyOverview);
			$output_companyOverview_raw = $output_companyOverview;
			$output_companyOverview = addslashes(trim(strip_tags($output_companyOverview_raw)));
		}
		else
		{
			$output_companyOverview = '';
			$output_companyOverview_raw = '';
		}
		
		
		// -------------------------------------
		// Email
		$extractedEmail = extract_email_address($output_jobDescription_raw.$output_companyOverview_raw);
		$output_email = (!empty($extractedEmail)) ? json_encode($extractedEmail) : NULL;
		
		
		// Phone
		$extractedPhone = extract_phone($output_jobDescription_raw.$output_companyOverview_raw);
		$output_phone = (!empty($extractedPhone)) ? json_encode($extractedPhone) : NULL;
		
		
		
		// -------------------------------------
		// Employment Type
		$get_start = 'xxxxxxxxxxxxxxxxxxxxx';
		$get_end = 'xxxxxxxxxxxxxxxxxxxxx';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_employmentType = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_employmentType = trim($output_employmentType);
		}
		else
		{
			$output_employmentType = '';
		}
		
		// -------------------------------------
		// Minimum Work Experience
		$get_start = '<div class="jtxt jico ico2 pull-left mar_left10">';
		$get_end = '</div>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_MinWorkExperience = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_MinWorkExperience = trim($output_MinWorkExperience);
		}
		else
		{
			$output_MinWorkExperience = '';
		}
		
		// -------------------------------------
		// Minimum Education Level
		$get_start = '<div class="heading">Education</div>';
		$get_end = '</span>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_MinEducationLevel = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_MinEducationLevel = trim($output_MinEducationLevel);
			$output_MinEducationLevel = str_replace('<span>', '', $output_MinEducationLevel);
		}
		else
		{
			$output_MinEducationLevel = '';
		}
		
		// -------------------------------------
		// Monthly Salary Range
		$get_start = 'xxxxxxxxxxxxxxxxxxxxx';
		$get_end = 'xxxxxxxxxxxxxxxxxxxxx';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;
			
			$output_monthlySalaryRange = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_monthlySalaryRange = trim($output_monthlySalaryRange);
		}
		else
		{
			$output_monthlySalaryRange = '';
		}
		
		// -------------------------------------
		// Location
		$get_start = '<div class="jtxt jico ico1 pull-left">';
		$get_end = '</div>';
		
		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_location = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_location = trim($output_location);
		}
		else
		{
			$output_location = '';
		}
		
		// -------------------------------------
		// Company Name
		$get_start = '<div class="company">';
		$get_end = '</div>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_companyName = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_companyName = trim($output_companyName);
		}
		else
		{
			$output_companyName = '';
		}
		
		// -------------------------------------
		// Job Date Posted
		$get_start = '<span>Posted: </span>';
		$get_end = '</div>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_jobDatePosted = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_jobDatePosted = trim($output_jobDatePosted);
		}
		
		$output_jobDatePosted = (!empty($output_jobDatePosted)) ? date('Y-m-d', strtotime($output_jobDatePosted)) : NULL;
		
		// -------------------------------------
		// Company Logo
		$get_start = '<img align="absbottom" src="';
		$get_end = '">';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_companyLogo = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_companyLogo = trim($output_companyLogo);
		}
		else
		{
			$output_companyLogo = '';
		}
		
		
		// ===========================================================
		// NEW FIELDS SPECIFIC FOR THIS CRAWLER
		// ===========================================================
		
		// -------------------------------------
		// Nationality
		$get_start = '<div class="heading">Nationality</div>';
		$get_end = '</span>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_nationality = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_nationality = trim($output_nationality);
			$output_nationality = str_replace('<span>', '', $output_nationality);
		}
		else
		{
			$output_nationality = '';
		}
		
		
		// -------------------------------------
		// Function
		$get_start = 'Function</div>';
		$get_end = '<div class="heading">';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_function = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_function = trim($output_function);
			$output_function = str_replace('<span>', '', $output_function);
			$output_function = str_replace('&bull;', '', $output_function);
			$output_function = strip_tags($output_function);
		}
		else
		{
			$output_function = '';
		}
		
		
		// -------------------------------------
		// Role
		$get_start = '<div class="heading">Role</div>';
		$get_end = '<div class="heading">';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_role = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_role = trim($output_role);
			$output_role = str_replace('<span>', '', $output_role);
			$output_role = str_replace('&bull;', '', $output_role);
			$output_role = strip_tags($output_role);
		}
		else
		{
			$output_role = '';
		}
		
		
		// -------------------------------------
		// Keyword/Skill
		$get_start = '<span>Keywords / Skills :</span>';
		$get_end = '</div>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_keyword_skill = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_keyword_skill = trim($output_keyword_skill);
			$output_keyword_skill = str_replace('<span>', '', $output_keyword_skill);
		}
		else
		{
			$output_keyword_skill = '';
		}
		
		// ===========================================================
		
		
		
		$output_industry = $job_industry;
		
		$output_careerLevel = '';


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
							$output_nationality,
							$output_function,
							$output_role,
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
		
		preg_match_all('/[0-9]{4}[\-][0-9]{3}[\-][0-9]{4}|[0-9]{3}[\-][0-9]{6}|[0-9]{3}[\s][0-9]{6}|[0-9]{3}[\s][0-9]{3}[\s][0-9]{4}|[0-9]{5}[\s][0-9]{9}|[0-9]{5}[\s][0-9]{10}|[0-9]{5}[\s][0-9]{11}|[0-9]{13}|[0-9]{12}|[0-9]{11}|[0-9]{1}[\-][0-9]{9}|[0-9]{2}[\-][0-9]{9}|[0-9]{2}[\-][0-9]{10}|[0-9]{3}[\-][0-9]{9}|[0-9]{3}[\-][0-9]{10}|[0-9]{3}[\-][0-9]{3}[\-][0-9]{4}/', $string, $matches);
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
				//@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
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