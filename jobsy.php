<?php
/**************

====================
Job Portal Crawler - job.sy
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
										company_logo
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
										'$output_companyLogo'
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
	
	$array_captured_link_a = array();
	
	$website_root = 'http://www.job.sy/index.php';
	
	$website_page_contents = getURLContents($website_root);
	
	$get_start = 'JOB VACANCIES BY FIELD OF WORK </h3>';
	$get_end = '<!-- End Main Column -->';

	if(strpos($website_page_contents, $get_start) > strlen($get_start))
	{ 
		$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
		$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
		$select_length = $select_pos_end - $select_pos_start;

		$captured = trim(substr($website_page_contents, $select_pos_start, $select_length));

		$get_start = 'href=';
		$get_end = '</a>';

		while(strpos($captured, $get_start) > strlen($get_start))
		{
			$select_pos_start = strpos($captured, $get_start) + strlen($get_start);
			$select_pos_end = strpos($captured, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$captured_link_raw = trim(substr($captured, $select_pos_start, $select_length));
			
			$captured_industry = substr($captured_link_raw, strpos($captured_link_raw, "'>") + 2, strlen($captured_link_raw));
			
			$captured_link = substr($captured_link_raw, 0, strpos($captured_link_raw, "'>"));
			$captured_link = "http://www.job.sy/".$captured_link;
			$captured_link = str_replace("'", "", $captured_link);
			
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
	//die();
	// ---- END FIRST SCRAPE ----

	
	
	// ---- START SECOND SCRAPE ----

	$counter = 0;
	
	foreach($array_captured_link_a as $key => $value)
	{
		$i = 0;
		
		$raw_url =  $value[1];
		
		$pagination = true;
		
		while($pagination == true)
		{
			$i++;
			
			$rowsPerPage = 25;
			
			$website_page_contents = getURLContents($raw_url.'&page='.$i.'&rowsPerPage='.$rowsPerPage);
			
			//$current_url_test = $raw_url.'&page='.$i.'&rowsPerPage='.$rowsPerPage;
			//print_r($i.' - '.$current_url_test."\r");
			
			$get_start = "<div id='mainContent'>";
			
			if(strpos($website_page_contents, $get_start) > strlen($get_start))
			{
				$get_start = '<div class="listBody">';
				$get_end = '<!-- End Main Column -->';
				
				if(strpos($website_page_contents, $get_start) > strlen($get_start))
				{
					$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
					$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
					$select_length = $select_pos_end - $select_pos_start;

					$captured = trim(substr($website_page_contents, $select_pos_start, $select_length));
					
					$get_start = "href='../company/vacancy/";
					$get_end = "'>";

					while(strpos($captured, $get_start) > strlen($get_start))
					{
						$select_pos_start = strpos($captured, $get_start) + strlen($get_start);
						$select_pos_end = strpos($captured, $get_end, $select_pos_start);
						$select_length = $select_pos_end - $select_pos_start;

						$captured_link = trim(substr($captured, $select_pos_start, $select_length));
						
						$captured_link = 'http://www.job.sy/company/vacancy/'.$captured_link;
						
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
				else
				{
					break;
				}
			}			
			else
			{
				$pagination = false;
				break;
			}
		}
	}
	// ---- END SECOND SCRAPE ----



	
	
	// ==========================================================
	// function to scrape the detailed job page (last level page)
	// ==========================================================
	function scrape_detailed_page($pdo, $job_industry, $scrape_page, $job_portal, $counter)
	{
		$website_page_contents = getURLContents($scrape_page);
		
		$arab = false;
		$get_start = 'class="right tahomaFont">';
			
		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{
			$arab = true;
		}
		else
		{
			$arab = false;
		}
			
		// -------------------------------------
		// Job Industry
		/* $get_start = "Field of Work </div><div id='tableCell' style=";
		$get_end = '</div>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;
			
			$job_industry = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$job_industry = str_replace('"width:550px; "  >', "", $job_industry);
		}
		else
		{
			$job_industry = '';			
		} */
		
		// -------------------------------------
		// Job Title
		if($arab == false){
			$get_start = "Job Title </div><div id='tableCell' style=";
			$get_end = '</div>';
		}else{
			$get_start = "المسمى الوظيفي </div><div id='tableCell' style=";
			$get_end = '</div>';
		}

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;
			
			$output_jobTitle = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_jobTitle = str_replace('"width:550px;"  >', "", $output_jobTitle);
			$output_jobTitle = addslashes(trim(strip_tags($output_jobTitle)));
		}
		else
		{
			$output_jobTitle = '';
		}
		
		// -------------------------------------
		// Job Description
		if($arab == false){
			$get_start = "Responsibilities </div><div id='tableCell' style=";
			$get_end = '</div>';
		}else{
			$get_start = "المهام الوظيفية </div><div id='tableCell' style=";
			$get_end = '</div>';
		}

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_jobDescription = strip_tags(trim(substr($website_page_contents, $select_pos_start, $select_length)));
			$output_jobDescription = str_replace('"width:550px;"  >', "", $output_jobDescription);
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
		if($arab == false){
			$get_start = "Qualifications </div><div id='tableCell' style=";
			$get_end = '</div>';
		}else{
			$get_start = "المؤهلات </div><div id='tableCell' style=";
			$get_end = '</div>';
		}

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_careerLevel = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_careerLevel = str_replace('"width:550px;"  >', "", $output_careerLevel);
			$output_careerLevel = trim($output_careerLevel);
			$output_careerLevel = addslashes(trim(strip_tags($output_careerLevel)));
		}
		else
		{
			$output_careerLevel = '';
		}
		
		// -------------------------------------
		// Employment Type
		if($arab == false){
			$get_start = "Job Type </div><div id='tableCell'   >";
			$get_end = '</div>';
		}else{
			$get_start = "طبيعة العمل </div><div id='tableCell'   >";
			$get_end = '</div>';
		}

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_employmentType = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_employmentType = str_replace('"width:550px;"  >', "", $output_employmentType);
			$output_employmentType = trim($output_employmentType);
			$output_employmentType = addslashes(trim(strip_tags($output_employmentType)));
		}
		else
		{
			$output_employmentType = '';
		}
		
		// -------------------------------------
		// Minimum Work Experience
		$get_start = 'xxxxxxxxxxxxx';
		$get_end = 'xxxxxxxxxxxxx';

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
		if($arab == false){
			$get_start = "Minimum Education Level </div><div id='tableCell'   >";
			$get_end = '</div>';
		}else{
			$get_start = "الحد الأدنى للمستوى التعليمي </div><div id='tableCell'   >";
			$get_end = '</div>';
		}

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_MinEducationLevel = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_MinEducationLevel = str_replace('"width:550px;"  >', "", $output_MinEducationLevel);
			$output_MinEducationLevel = trim($output_MinEducationLevel);
			$output_MinEducationLevel = addslashes(trim(strip_tags($output_MinEducationLevel)));
		}
		else
		{
			$output_MinEducationLevel = '';
		}
		
		// -------------------------------------
		// Monthly Salary Range
		if($arab == false){
			$get_start = "Salary and Benefits </div><div id='tableCell' style=";
			$get_end = '</div>';
		}else{
			$get_start = "الراتب و الفوائد </div><div id='tableCell' style=";
			$get_end = '</div>';
		}

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_monthlySalaryRange = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_monthlySalaryRange = str_replace('"width:550px;"  >', "", $output_monthlySalaryRange);
			$output_monthlySalaryRange = str_replace('<br />', "", $output_monthlySalaryRange);
			$output_monthlySalaryRange = trim($output_monthlySalaryRange);
			$output_monthlySalaryRange = addslashes(trim(strip_tags($output_monthlySalaryRange)));
		}
		else
		{
			$output_monthlySalaryRange = '';
		}
		
		// -------------------------------------
		// Location
		if($arab == false){
			$get_start = "City </div><div id='tableCell' style=";
			$get_end = '</div>';
		}else{
			$get_start = "المدينة </div><div id='tableCell' style=";
			$get_end = '</div>';	
		}

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_location_city = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_location_city = str_replace('"width:550px;"  >', "", $output_location_city);
			$output_location_city = trim($output_location);
			$output_location_city = addslashes(trim(strip_tags($output_location_city)));
		}
		else
		{
			$output_location_city = '';
		}
		
		if($arab == false){
			$get_start = "Country </div><div id='tableCell' style=";
			$get_end = '</div>';		
		}else{
			$get_start = "الدولة </div><div id='tableCell' style=";	
			$get_end = '</div>';
		}

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_location_country = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_location_country = str_replace('"width:550px;"  >', "", $output_location_country);
			$output_location_country = ', '.trim($output_location_country);
			$output_location_country = addslashes(trim(strip_tags($output_location_country)));
		}
		else
		{
			$output_location_country = '';
		}
		
		$output_location = $output_location_city.$output_location_country;
		
		// -------------------------------------
		// Company Name
		if($arab == false){
			$get_start = "To work for </div><div id='tableCell' style=";
			$get_end = '</div>';
		}else{
			$get_start = "للعمل لدى </div><div id='tableCell' style=";
			$get_end = '</div>';
		}

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_companyName = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_companyName = str_replace('"width:550px; font-weight: bold;"  >', "", $output_companyName);
			$output_companyName = trim($output_companyName);
			$output_companyName = addslashes(trim(strip_tags($output_companyName)));
		}
		else
		{
			$output_companyName = '';
		}
		
		// -------------------------------------
		// Company Overview
		if($arab == false){
			$get_start = "About us </div><div id='tableCell' style=";
			$get_end = '</div>';
		}else{
			$get_start = "لمحة عن الجهة الموظفة </div><div id='tableCell' style=";
			$get_end = '</div>';
		}
		
		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			
			$output_companyOverview = substr($website_page_contents, $select_pos_start, $select_length);
			$output_companyOverview = str_replace('"width:550px;"  >', "", $output_companyOverview);
			$output_companyOverview = trim($output_companyOverview);
			$output_companyOverview = addslashes(trim(strip_tags($output_companyOverview)));
			
		}
		else
		{
			$output_companyOverview = '';
		}
		
		// -------------------------------------
		// Company Logo
		$get_start = 'onclick="return hs.expand(this)">';
		$get_end = '">';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_companyLogo = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_companyLogo = str_replace('src="../../', 'http://www.job.sy/', $output_companyLogo);
			$output_companyLogo = str_replace('<img', '', $output_companyLogo);
			$output_companyLogo = trim($output_companyLogo);
		}
		else
		{
			$output_companyLogo = '';
		}
			
		// -------------------------------------
        // Job Date Posted
		if($arab == false){
			$get_start = "Posted On </div><div id='tableCell'   >";
			$get_end = '</div>';
		}else{
			$get_start = "عرضت بتاريخ </div><div id='tableCell'   >";
			$get_end = '</div>';
		}
 
        if(strpos($website_page_contents, $get_start) > strlen($get_start))
        { 
            $select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
            $select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
            $select_length = $select_pos_end - $select_pos_start;
 
            $output_jobDatePosted = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_jobDatePosted = str_replace(',', "", $output_jobDatePosted);
            $output_jobDatePosted_array =  explode(" ", $output_jobDatePosted);
            $converted_month = $output_jobDatePosted_array[0];
			
			$month = "";
			
			if(strpos($converted_month, "Jan") !== false || $converted_month == "Jan"){
				$month =  "01";
			}
			elseif(strpos($converted_month, "Feb") !== false || $converted_month == "Feb"){
				$month =  "02";
			}
			elseif(strpos($converted_month, "Mar") !== false || $converted_month == "Mar"){
				$month =  "03";
			}
			elseif(strpos($converted_month, "Apr") !== false || $converted_month == "Apr"){
				$month =  "04";
			}			
			elseif(strpos($converted_month, "May") !== false || $converted_month == "May"){
				$month =  "05";
			}
			elseif(strpos($converted_month, "Jun") !== false || $converted_month == "Jun"){
				$month =  "06";
			}
			elseif(strpos($converted_month, "Jul") !== false || $converted_month == "Jul"){
				$month =  "07";
			}
			elseif(strpos($converted_month, "Aug") !== false || $converted_month == "Aug"){
				$month =  "08";
			}
			elseif(strpos($converted_month, "Sep") !== false || $converted_month == "Sep"){
				$month =  "09";
			}
			elseif(strpos($converted_month, "Oct") !== false || $converted_month == "Oct"){
				$month =  "10";
			}
			elseif(strpos($converted_month, "Nov") !== false || $converted_month == "Nov"){
				$month =  "11";
			}
			elseif(strpos($converted_month, "Dec") !== false || $converted_month == "Dec"){
				$month =  "12";
			}
			else
			{
				$output_jobDatePosted = '';
			}
			
			if(strlen($month > 0))
			{
				$output_jobDatePosted = $output_jobDatePosted_array[2].'-'.$month.'-'.$output_jobDatePosted_array[1];
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
							$output_companyLogo
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