<?php
/**************

====================
Job Portal Crawler - keejob.com
Current Version: 1.0.0
====================

Change Log:
ver 1.0.0
- Script that crawls keejob.com Jobs section that captures specific fields.

**************/

	$time_start = microtime(true);

	ini_set('max_execution_time', 0);
	ini_set('memory_limit', '-1');

	$job_portal = "keejob.com";
	
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

		$statement->execute();
		
		//echo $last_id.' | '.$scrape_page.'</br>';
	}
	
	

	//============= START DATA CAPTURE ===============



	// ---- START FIRST SCRAPE ----

	$website_root = 'http://www.keejob.com/offres-emploi/jobs/advanced/results/';
	$website_page_contents = getURLContents($website_root);
	
	$array_captured_link_a = array();
	
	$get_start = '<i class="fa fa-info-circle"></i>';
	$get_end = 'Offres d';
	
	if(strpos($website_page_contents, $get_start) > strlen($get_start))
	{
		$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
		$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
		$select_length = $select_pos_end - $select_pos_start;

		$total_jobs = trim(substr($website_page_contents, $select_pos_start, $select_length));
		
		//print_r($total_jobs);
	}
	
	$i = 1;
	
	$counter = 0;
	
	$limit_total_jobs = $total_jobs / 15;
	
	//echo $limit_total_jobs;
	//die();
	
	while($i < $limit_total_jobs)
	{
		//print_r('current page = '.$i."\r");
		
		$website_page_contents = getURLContents($website_root.'?page='.$i);
		
		$get_start = '<div class="item blog-posts" id="loop-container">';
		$get_end = '<nav class="nav-pagination">';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$captured = trim(substr($website_page_contents, $select_pos_start, $select_length));

			$get_start = '<a style="color: #005593;" href="';
			$get_end = '"';

			while(strpos($captured, $get_start) > strlen($get_start))
			{
				$select_pos_start = strpos($captured, $get_start) + strlen($get_start);
				$select_pos_end = strpos($captured, $get_end, $select_pos_start);
				$select_length = $select_pos_end - $select_pos_start;

				$captured_link_raw = trim(substr($captured, $select_pos_start, $select_length));
				
				$captured_link = 'www.keejob.com'.html_entity_decode($captured_link_raw);
				
				$counter++;
						
				//print_r($counter.' - '.$captured_link."\r");
				
				if(!checkIfSkip($pdo, $captured_link, $job_portal)) {
					
					// -------------------
					// scrape the detailed job page (last page)
					scrape_detailed_page($pdo, $captured_link, $job_portal, $counter);
					// -------------------				
					
				} else {
					//print_r('SKIPPING - ' .$counter.' - '.$captured_link."\r");
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

				$captured = str_replace($get_start.$captured_link_raw.$get_end, "", $captured);
			}
		}
		
		//break;
		
		$i++;
	}
	//print_r($array_captured_link_a);
	// ---- END FIRST SCRAPE ----

	
	




	
	
	// ==========================================================
	// function to scrape the detailed job page (last level page)
	// ==========================================================
	function scrape_detailed_page($pdo, $scrape_page, $job_portal, $counter)
	{
		$website_page_contents = getURLContents($scrape_page);
		
		// -------------------------------------
		// Job Industry
		$get_start = '<b>Secteur:</b>';
		$get_end = '<br>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;
			
			$job_industry = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$job_industry = str_replace('<dd class="col-sm-6">', "", $job_industry);
			$job_industry = trim($job_industry);
		}
		else
		{
			$job_industry = '';
		}
		
		// -------------------------------------
		// Job Title
		$get_start = '<h2 class="job-title">';
		$get_end = '</h2>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;
			
			$output_jobTitle = trim(substr($website_page_contents, $select_pos_start, $select_length));
		}
		else
		{
			$output_jobTitle = '';
		}
		
		// -------------------------------------
		// Job Description
		$get_start = "Description de l'annonce:</h6>";
		$get_end = '<div class=';

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
		// Email
		$extractedEmail = extract_email_address($output_jobDescription);
		$extractedPhone = extract_phone($output_jobDescription);

		$output_email = (!empty($extractedEmail)) ? json_encode($extractedEmail) : NULL;
		
		// Phone
		$output_phone = (!empty($extractedPhone)) ? json_encode($extractedPhone) : NULL;
		
		// -------------------------------------
		// Career Level
		$get_start = 'xxxxxxxxxxxxxxxxxxx';
		$get_end = 'xxxxxxxxxxxxxxxxxxx';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_careerLevel = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_careerLevel = str_replace('<br>', "", $output_careerLevel);
			$output_careerLevel = trim($output_careerLevel);
		}
		else
		{
			$output_careerLevel = '';
		}
		
		// -------------------------------------
		// Employment Type
		$get_start = '<b>Disponibilité:</b>';
		$get_end = '</div>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_employmentType = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_employmentType = str_replace('<br>', "", $output_employmentType);
			$output_employmentType = trim($output_employmentType);
		}
		else
		{
			$output_employmentType = '';
		}
		
		// -------------------------------------
		// Minimum Work Experience
		$get_start = '<b>Expérience:</b>';
		$get_end = '</div>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_MinWorkExperience = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_MinWorkExperience = str_replace('<br>', "", $output_MinWorkExperience);
			$output_MinWorkExperience = trim($output_MinWorkExperience);
		}
		else
		{
			$output_MinWorkExperience = '';
		}
		
		// -------------------------------------
		// Minimum Education Level
		$get_start = '<b>Étude:</b>';
		$get_end = '</div>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_MinEducationLevel = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_MinEducationLevel = str_replace('<br>', "", $output_MinEducationLevel);
			$output_MinEducationLevel = trim($output_MinEducationLevel);
		}
		else
		{
			$output_MinEducationLevel = '';
		}
		
		// -------------------------------------
		// Monthly Salary Range
		$get_start = '<b>Rémunération proposée:</b>';
		$get_end = '</div>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_monthlySalaryRange = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_monthlySalaryRange = str_replace('<br>', "", $output_monthlySalaryRange);
			$output_monthlySalaryRange = trim($output_monthlySalaryRange);
		}
		else
		{
			$output_monthlySalaryRange = '';
		}
		
		// -------------------------------------
		// Location
		$get_start = '<b>Lieu de travail:</b>';
		$get_end = '</div>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_location = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_location = str_replace('<br>', "", $output_location);
			$output_location = trim($output_location);
		}
		else
		{
			$output_location = '';
		}
		
		// -------------------------------------
		// Company Name
		$get_start = '<meta name="author" content="';
		$get_end = '"';

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
		// Company Overview
		$get_start = '<p style="margin: 5px 0 10px;">';
		$get_end = '</div>';

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
		// Company Logo
		$get_start = '<figure class="span3 img-polaroid" style="border: 1px solid #dadada;">';
		$get_end = '"/>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_companyLogo = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_companyLogo = str_replace('<img src="', "www.keejob.com", $output_companyLogo);
			$output_companyLogo = trim($output_companyLogo);
		}
		else
		{
			$output_companyLogo = '';
		}
			
		// -------------------------------------
        // Job Date Posted
        $get_start = '<b>Publiée le:</b>';
		$get_end = '</div>';
 
        if(strpos($website_page_contents, $get_start) > strlen($get_start))
        { 
            $select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
            $select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
            $select_length = $select_pos_end - $select_pos_start;
 
            $output_jobDatePosted = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_jobDatePosted = str_replace('<br>', "", $output_jobDatePosted);
            $output_jobDatePosted_array =  explode(" ", $output_jobDatePosted);
            $converted_month = $output_jobDatePosted_array[1];
			
			$month = "";
			
			if(strpos($converted_month, "janv") !== false || $converted_month == "janv"){
				$month =  "01";
			}
			elseif(strpos($converted_month, "févr") !== false || $converted_month == "févr"){
				$month =  "02";
			}
			elseif(strpos($converted_month, "mars") !== false || $converted_month == "mars"){
				$month =  "03";
			}
			elseif(strpos($converted_month, "avril") !== false || $converted_month == "avril"){
				$month =  "04";
			}			
			elseif(strpos($converted_month, "mai") !== false || $converted_month == "mai"){
				$month =  "05";
			}
			elseif(strpos($converted_month, "juin") !== false || $converted_month == "juin"){
				$month =  "06";
			}
			elseif(strpos($converted_month, "juil") !== false || $converted_month == "juil"){
				$month =  "07";
			}
			elseif(strpos($converted_month, "août") !== false || $converted_month == "août"){
				$month =  "08";
			}
			elseif(strpos($converted_month, "sept") !== false || $converted_month == "sept"){
				$month =  "09";
			}
			elseif(strpos($converted_month, "oct") !== false || $converted_month == "oct"){
				$month =  "10";
			}
			elseif(strpos($converted_month, "nov") !== false || $converted_month == "nov"){
				$month =  "11";
			}
			elseif(strpos($converted_month, "déc") !== false || $converted_month == "déc"){
				$month =  "12";
			}
			else
			{
				$output_jobDatePosted = '';
			}
			
			if(strlen($month > 0))
			{
				$output_jobDatePosted = $output_jobDatePosted_array[2].'-'.$month.'-'.$output_jobDatePosted_array[0];
				//echo $output_jobDatePosted;
            }
			else
			{
				$output_jobDatePosted = '';
			}
			
        }
		else
		{
			$output_jobDatePosted = '';
		}
		
		// ===========================================================
		// NEW FIELDS SPECIFIC FOR THIS CRAWLER
		// ===========================================================
		
		// -------------------------------------
		// Role
		$get_start = '<b>Job Role:</b></dt>';
		$get_end = '</dd>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_role = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_role = str_replace('<dd class="col-sm-6">', '', $output_role);
			$output_role = trim($output_role);
		}
		else
		{
			$output_role = '';
		}
		
		// -------------------------------------
		// Keyword/Skill
		$get_start = '<h2>Skills</h2>';
		$get_end = '</div>';

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
		
		// -------------------------------------
		// Company Type
		$get_start = '<b>Company Type:</b></dt>';
		$get_end = '</dd>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_company_type = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_company_type = str_replace('<dd class="col-sm-6">', '', $output_company_type);
		}
		else
		{
			$output_company_type = '';
		}
		
		
		$output_industry = $job_industry;
		
		// -------------------------------------
		
		
		//echo '</br>'.$scrape_page;
		
		
		//echo '</br>'.$output_industry.' - '.$output_jobTitle.' - '.$output_jobDescription.' - '.$output_careerLevel.' - '.$output_employmentType.' - '.$output_MinWorkExperience.' - '.$output_MinEducationLevel.' - '.$output_monthlySalaryRange.' || '.$output_location.' || '.$output_companyName. ' - '.$output_jobDatePosted;
		
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