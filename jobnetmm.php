<?php
/**************

====================
Job Portal Crawler - jobnet.com.mm
Current Version: 1.1.0
====================

Change Log:
ver 1.0.0
- Script that crawls jobnet.com.mm Jobs section that captures specific fields.
ver 1.1.0
- Rework 2nd level scraping due to updated website layout/design.

**************/

	$time_start = microtime(true);

	ini_set('max_execution_time', 0);
	ini_set('memory_limit', '-1');

	$job_portal = "jobnet.com.mm";
	
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
								$output_gender,
								$output_salaryType
								)
	{
		
		$output_jobDescription 	= clean($output_jobDescription);
		$output_jobDatePosted 	= (!empty($output_jobDatePosted)) ? date('Y-m-d', strtotime($output_jobDatePosted)) : NULL;
		
		$job_unique_id = md5($scrape_page);

		$job_post_id  = substr(uniqid(bin2hex(openssl_random_pseudo_bytes(12))), 0, 12) . getUniqueId($pdo);
		
		$sql = "INSERT INTO job_details_mm (
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
										gender,
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
										'$output_gender',
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

	$counter = 0;
	
	$array_job_industries = array('1','2','3','4','5','6','7','8','9','10','11','13','14','15','16','17','18','19','20','21','23','24','25','26','28','29','30','31','32','33','34','35','37','38','39',',40','41','42','43','44','45','49','55','56');
	
	foreach($array_job_industries as $key => $industry_id)
	{		
		$website_root = 'https://www.jobnet.com.mm/en/jobs/i-'.$industry_id;
		
		$website_page_contents = getURLContents($website_root);
	
		$get_start = 'class="desktop-search-title"><b>';
		$get_end = "</b>";

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$total_jobs = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$last_page = ceil($total_jobs / 30);
			
			
			$get_start = '<div class="wrap-search-result">';
			$get_end = '<div class="pagination">';
			
			if(strpos($website_page_contents, $get_start) > strlen($get_start))
			{
				$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
				$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
				$select_length = $select_pos_end - $select_pos_start;

				$captured = trim(substr($website_page_contents, $select_pos_start, $select_length));

				$get_start = '<h3><a href="';
				$get_end = '" target="';

				while(strpos($captured, $get_start) > strlen($get_start))
				{
					$select_pos_start = strpos($captured, $get_start) + strlen($get_start);
					$select_pos_end = strpos($captured, $get_end, $select_pos_start);
					$select_length = $select_pos_end - $select_pos_start;

					$captured_link_raw = trim(substr($captured, $select_pos_start, $select_length));
					
					$captured_link = substr($captured_link_raw, 0, strlen($captured_link_raw));
					$captured_link = 'https://www.jobnet.com.mm'.$captured_link;
					
					//echo $captured_link.'</br>';
					
					$counter++;
							
					//print_r($counter.' - '.$captured_link."\r");
					
					if(!checkIfSkip($pdo, $captured_link, $job_portal)) {
					// -------------------
					// scrape the detailed job page (last page)
					scrape_detailed_page($pdo, $captured_link, $job_portal, $counter);
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
				}
			}
			
			
			// =============================
			if($last_page > 1)
			{
				preg_match_all("/id=\"__VIEWSTATEGENERATOR\" value=\"(.*?)\"/", $website_page_contents, $arr_viewstategen);
				$viewstategenerator = urlencode($arr_viewstategen[1][0]);
				$viewstategenerator = urldecode($viewstategenerator);
				
				preg_match_all("/id=\"__VIEWSTATE\" value=\"(.*?)\"/", $website_page_contents, $arr_viewstate);
				$viewstate = urlencode($arr_viewstate[1][0]);
				$viewstate = urldecode($viewstate);
				
				preg_match_all("/id=\"__EVENTVALIDATION\" value=\"(.*?)\"/", $website_page_contents, $arr_eventvalidation);
				$eventvalidation = urlencode($arr_eventvalidation[1][0]);
				$eventvalidation = urldecode($eventvalidation);
		
				$i = 1;
				
				while($i <= $last_page)
				{
					$i++;
					
					$array_fields_string = array(
									'__EVENTTARGET' => 'ctl00$BodyPlaceHolder$pagerControl',
									'__EVENTARGUMENT' => $i,
									'__EVENTVALIDATION' => $eventvalidation,
									'__VIEWSTATE' => $viewstate,
									'__VIEWSTATEGENERATOR' => $viewstategenerator
									);
						
					$website_page_contents = getURLContentsPost($website_root, $array_fields_string);
					
					$get_start = '<div class="wrap-search-result">';
					$get_end = '<div class="pagination">';
					
					if(strpos($website_page_contents, $get_start) > strlen($get_start))
					{ 
						$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
						$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
						$select_length = $select_pos_end - $select_pos_start;

						$captured = trim(substr($website_page_contents, $select_pos_start, $select_length));
						
						$get_start = '<h3><a href="';
						$get_end = '" target="';

						while(strpos($captured, $get_start) > strlen($get_start))
						{
							$select_pos_start = strpos($captured, $get_start) + strlen($get_start);
							$select_pos_end = strpos($captured, $get_end, $select_pos_start);
							$select_length = $select_pos_end - $select_pos_start;

							$captured_link_raw = trim(substr($captured, $select_pos_start, $select_length));
							
							$captured_link = substr($captured_link_raw, 0, strlen($captured_link_raw));
							$captured_link = 'https://www.jobnet.com.mm'.$captured_link;
							
							//echo $captured_link.'</br>';
							
							$counter++;
							
							//print_r($counter.' - '.$captured_link."\r");
							
							if(!checkIfSkip($pdo, $captured_link, $job_portal)) {
							// -------------------
							// scrape the detailed job page (last page)
							scrape_detailed_page($pdo, $captured_link, $job_portal, $counter);
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
						}
					}
				}// end for loop
				
			} // end if last_page
			// =============================
		}
		else
		{
			continue;
		}
		//break;		
	} // end foreach
	// ---- END FIRST SCRAPE ----
	

	
	
	// ==========================================================
	// function to scrape the detailed job page (last level page)
	// ==========================================================
	function scrape_detailed_page($pdo, $scrape_page, $job_portal, $counter)
	{
		$website_page_contents = getURLContents($scrape_page);
		
		// -------------------------------------
		// Job Industry
		$get_start = 'Industry</span> :';
		$get_end = '</div>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;
			
			$output_industry = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_industry = str_replace('<br/>', "/", $output_industry);
			$output_industry = trim($output_industry);
			$output_industry = addslashes(trim(strip_tags($output_industry)));
		}
		else
		{
			$output_industry = '';
		}
			
		// -------------------------------------
		// Job Title
		$get_start = 'style="color:#7C0A78;font-size:17px;">';
		$get_end = '</span>';

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
		// Company Name
		$get_start = '<span id="ContentPlaceHolder1_JobDetail1_lblCompanyNameLeft">';
		$get_end = '</span>';

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
		// Job Description - part 1
		$get_start = '<span id="ContentPlaceHolder1_JobDetail1_lblJobDescription">';
		$get_end = '</span>';

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
		
		// Job Description - part 2
		$get_start = '<span id="ContentPlaceHolder1_JobDetail1_lblJobRequirements">';
		$get_end = '</span>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_jobDescription_part_two = strip_tags(trim(substr($website_page_contents, $select_pos_start, $select_length)));
			$output_jobDescription_part_two = addslashes(trim(strip_tags($output_jobDescription_part_two)));
		}
		else
		{
			$output_jobDescription_part_two = '';
		}
		
		$output_jobDescription = $output_jobDescription.$output_jobDescription_part_two;
		
		// -------------------------------------
		// Company Overview
		$get_start = '<div class="job-profile-block col-md-12">';
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
		// Email
		$extractedEmail = extract_email_address($output_jobDescription.$output_companyOverview);
		$extractedPhone = extract_phone($output_jobDescription.$output_companyOverview);

		$output_email = (!empty($extractedEmail)) ? json_encode($extractedEmail) : NULL;
		
		// Phone
		$output_phone = (!empty($extractedPhone)) ? json_encode($extractedPhone) : NULL;
		
		
		// -------------------------------------
		// Career Level
		$get_start = '<span id="ContentPlaceHolder1_JobDetail1_lblExperienceLevel">';
		$get_end = '</span>';

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
		$get_start = '<span id="ContentPlaceHolder1_JobDetail1_lblWorkType">';
		$get_end = '</span>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_employmentType = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_employmentType = trim($output_employmentType);
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
		$get_start = '<span id="ContentPlaceHolder1_JobDetail1_lblSalary">';
		$get_end = '</span>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_monthlySalaryRange = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_monthlySalaryRange = addslashes(trim(strip_tags($output_monthlySalaryRange)));
		}
		else
		{
			$output_monthlySalaryRange = '';
		}
		
		// -------------------------------------
		// Location
		$get_start = '<span class="job-detail-location-mobile bold">';
		$get_end = '</div>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_location = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_location = str_replace('Location', "", $output_location);
			$output_location = str_replace('</span>', "", $output_location);
			$output_location = str_replace('<span>:&nbsp;', "", $output_location);
			$output_location = trim($output_location);
			$output_location = addslashes(trim(strip_tags($output_location)));
		}
		else
		{
			$output_location = '';
		}
		
		// -------------------------------------
		// Company Logo
		$get_start = '<div class="col-xs-12 col-sm-2 col-md-2 job-seeker-logo job-seeker-logo-desktop">';
		$get_end = '" alt';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_companyLogo = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_companyLogo = substr($output_companyLogo, strpos($output_companyLogo, '<img src="') + 10, strlen($output_companyLogo));
			$output_companyLogo = str_replace($get_end, '', $output_companyLogo);
			$output_companyLogo = str_replace('<img src="', '', $output_companyLogo);
			$output_companyLogo = trim($output_companyLogo);
			$output_companyLogo = addslashes(trim(strip_tags($output_companyLogo)));
		}
		else
		{
			$output_companyLogo = '';
		}

		// -------------------------------------
		// Job Date Posted
		$get_start = '<span class="job-detail-postdate-mobile bold">';
		$get_end = '</div>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_jobDatePosted = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_jobDatePosted = str_replace('Posted', "", $output_jobDatePosted);
			$output_jobDatePosted = str_replace('</span>', "", $output_jobDatePosted);
			$output_jobDatePosted = str_replace('<span> &nbsp;:&nbsp; ', "", $output_jobDatePosted);
			$output_jobDatePosted = trim(str_replace(array("\r", "\n"), '', $output_jobDatePosted));
			
			$output_jobDatePosted_array =  explode(" ", $output_jobDatePosted);
			$converted_month = strtolower($output_jobDatePosted_array[1]);
			
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
			$output_jobDatePosted = $output_jobDatePosted_array[2].'-'.$month.'-'.$output_jobDatePosted_array[0];
		}
		
		// ===========================================================
		// NEW FIELDS SPECIFIC FOR THIS CRAWLER
		// ===========================================================
		
		// -------------------------------------
		// Gender
		$get_start = '<h5 class="post" style="margin-top:0;">';
		$get_end = '</h5>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_gender = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_gender = substr($output_gender, 0, strpos($output_gender, '&nbsp;'));
			$output_gender = trim(str_replace(array("\r", "\n"), '', $output_gender));
			$output_gender = addslashes(trim(strip_tags($output_gender)));
		}
		else
		{
			$output_gender = '';
		}
		
		// -------------------------------------
		// Salary Type
		$get_start = '<span id="ContentPlaceHolder1_JobDetail1_lblPayStructure">';
		$get_end = '</span>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_salaryType = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_salaryType = trim($output_salaryType);
			$output_salaryType = addslashes(trim(strip_tags($output_salaryType)));
		}
		else
		{
			$output_salaryType = '';
		}
		
		
		
		
		
		
		
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
							$output_gender,
							$output_salaryType
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
				@curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
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

        $sql       = "SELECT job_url, skip FROM job_details_mm WHERE job_url = '$url' AND job_portal = '$jobPortal' ";
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