<?php
/**************

====================
Job Portal Crawler - www.dubizzle.com
Current Version: 1.3.4
====================

Change Log:
ver 1.0.0
- Script that crawls dubbizle.com Jobs section that captures specific fields.
ver 1.1.1
- Trim monthly_salary_range values to exclude whitespaces. Include Phone and Email fields to crawl.
ver 1.1.2
- Updated the selector for Job Title as the website has changed its format.
- Updated the Cron function.
- Added the job_date_posted field.
ver 1.1.3
- Updated the Second Level Scrape links to detect all jobs under an Industry even without assigned roles.
ver 1.2.0
- Added escape functionality for mysqli strings.
ver 1.3.0
- Added a new feature for "skip" method.
ver 1.3.1
- Fixed function "scrape_detailed_page()" to retrieve complete url.
ver 1.3.2
- Included the Company Name for scraping.
ver 1.3.3
- Update selector within 2nd level scraping.
ver 1.3.4
- Remove trailing parameters on the second level captured_link to prevent duplicate.

**************/

	$time_start = microtime(true);

	ini_set('max_execution_time', 0);
	ini_set('memory_limit', '-1');

	$job_portal = "dubizzle.com";
	
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
								$output_jobDatePosted
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
										job_date_posted
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
										'$output_jobDatePosted'
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
										job_date_posted = '$output_jobDatePosted'
									";

									
		$statement = $pdo->prepare($sql);

		$statement->execute();
	}
	
	

	//============= START DATA CAPTURE ===============



	// ---- START FIRST SCRAPE ----

	$website_root = 'https://dubai.dubizzle.com/jobs/';
	$website_page_contents = getURLContents($website_root);

	$array_captured_link_a = array();

	$get_start = '<span class="more">Jobs</span>';
	$get_end = '<li class="parent more" id="nav-community">';

	if(strpos($website_page_contents, $get_start) > strlen($get_start))
	{ 
		$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
		$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
		$select_length = $select_pos_end - $select_pos_start;

		$captured = trim(substr($website_page_contents, $select_pos_start, $select_length));

		$get_start = "<li><a href='/jobs/";
		$get_end = "'>";

		while(strpos($captured, $get_start) > strlen($get_start))
		{
			$select_pos_start = strpos($captured, $get_start) + strlen($get_start);
			$select_pos_end = strpos($captured, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$captured_link = trim(substr($captured, $select_pos_start, $select_length));
			
			$captured_link_final = $website_root.$captured_link;
			
			$array_captured_link_a[] = array(str_replace("/","", $captured_link), $captured_link_final);

			//echo '</br>'.$captured_link_final;

			$captured = str_replace($get_start.$captured_link.$get_end, "", $captured);
		}
	}
	//print_r($array_captured_link_a);
	// ---- END FIRST SCRAPE ----
	
	
	


	// ---- START SECOND SCRAPE ----

	$array_captured_link_c = array();
	
	$counter = 0;

	foreach($array_captured_link_a as $key => $value)
	{
		$website_page_contents = getURLContents($value[1]);
		
		// ------------------------
		// If Pagination Exists
		
		$has_pagination = false;
		
		$get_start 	= ' id="next_page">&gt;</a>';
		$get_end 	= ' id="last_page">&gt;&gt;</a>';
		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$captured = trim(substr($website_page_contents, $select_pos_start, $select_length));

			$get_start = '?page=';
			$get_end = '"';

			if(strpos($captured, $get_start) > strlen($get_start))
			{
				$select_pos_start = strpos($captured, $get_start) + strlen($get_start);
				$select_pos_end = strpos($captured, $get_end, $select_pos_start);
				$select_length = $select_pos_end - $select_pos_start;

				$captured_total_pages = trim(substr($captured, $select_pos_start, $select_length));
				
				//echo $captured_total_pages.' pages = '.$value[1].'</br>';
				
				$has_pagination = true;
			}
		}
		// ------------------------
		
		if($has_pagination)
		{
			$total_pages = $captured_total_pages;
		}
		else
		{
			$total_pages = 1;
		}
			
		for($i = 1; $i <= $total_pages; $i++)
		{
			$website_page_contents = getURLContents($value[1].'?page='.$i);

			$get_start = '<div class="group-content" xtdztype="CZSE">';
			$get_end = '<div id="dfp-cpc-bottom"';

			if(strpos($website_page_contents, $get_start) > strlen($get_start))
			{ 
				$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
				$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
				$select_length = $select_pos_end - $select_pos_start;

				$captured = trim(substr($website_page_contents, $select_pos_start, $select_length));

				$get_start = '<a href="https://dubai.dubizzle.com/jobs/';
				$get_end = '"';

				while(strpos($captured, $get_start) > strlen($get_start))
				{
					$select_pos_start = strpos($captured, $get_start) + strlen($get_start);
					$select_pos_end = strpos($captured, $get_end, $select_pos_start);
					$select_length = $select_pos_end - $select_pos_start;

					$captured_link = trim(substr($captured, $select_pos_start, $select_length));
					$captured_link = substr($captured_link, 0, strpos($captured_link, '?'));
					$captured_link_final = 'https://dubai.dubizzle.com/jobs/'.$captured_link;
					
					$counter++;
					
					print_r($counter.' - '.$value[0].' - '.$captured_link_final."\r");
					
					if(!checkIfSkip($pdo, $captured_link, $job_portal)) {
						// -------------------
						// scrape the detailed job page (last page)
						scrape_detailed_page($pdo, $value[0], $captured_link_final, $job_portal, $counter);
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
					
				}// end while
			}// end if
			// =======================
			//break;
		}// end for
		
		//break;
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
		$get_start = '<span id="listing-title-wrap" class="title no-float" itemprop="title">';
		$get_end = '</span>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;
			
			$output_jobTitle = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_jobTitle = addslashes(trim(strip_tags($output_jobTitle)));
		} else {
			$output_jobTitle = NULL;
		}
		
		// -------------------------------------
		// Job Description
		$get_start = '<span class="title" style="direction: ltr">';
		$get_end = '</span>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_jobDescription = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_jobDescription = addslashes(trim(strip_tags($output_jobDescription)));
		} else {
			$output_jobDescription = NULL;
		}
		
		// -------------------------------------
		// Career Level
		$get_start = 'Career Level:';
		$get_end = '</strong>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_careerLevel = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_careerLevel = str_replace("</span>", "", $output_careerLevel);
			$output_careerLevel = str_replace("<strong>", "", $output_careerLevel);
			$output_careerLevel = str_replace("</strong>", "", $output_careerLevel);
			$output_careerLevel = trim($output_careerLevel);
			$output_careerLevel = addslashes(trim(strip_tags($output_careerLevel)));
		} else {
			$output_careerLevel = NULL;
		}
		
		// -------------------------------------
		// Employment Type
		$get_start = '<strong itemprop="employmentType">';
		$get_end = '</strong>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_employmentType = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_employmentType = trim($output_employmentType);
			$output_employmentType = addslashes(trim(strip_tags($output_employmentType)));
		} else {
			$output_employmentType = NULL;
		}
		
		// -------------------------------------
		// Minimum Work Experience
		$get_start = '<strong itemprop="experienceRequirements">';
		$get_end = '</strong>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_MinWorkExperience = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_MinWorkExperience = trim($output_MinWorkExperience);
			$output_MinWorkExperience = addslashes(trim(strip_tags($output_MinWorkExperience)));
		} else {
			$output_MinWorkExperience = NULL;
		}
		
		// -------------------------------------
		// Minimum Education Level
		$get_start = '<strong itemprop="educationRequirements">';
		$get_end = '</strong>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_MinEducationLevel = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_MinEducationLevel = trim($output_MinEducationLevel);
			$output_MinEducationLevel = addslashes(trim(strip_tags($output_MinEducationLevel)));
		} else {
			$output_MinEducationLevel = NULL;
		}
			
		// -------------------------------------
		// Monthly Salary Range
		$get_start = 'Monthly Salary:';
		$get_end = '</strong>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_monthlySalaryRange = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_monthlySalaryRange = str_replace("</span>", "", $output_monthlySalaryRange);
			$output_monthlySalaryRange = str_replace("<strong>", "", $output_monthlySalaryRange);
			$output_monthlySalaryRange = str_replace("</strong>", "", $output_monthlySalaryRange);
			$output_monthlySalaryRange = trim($output_monthlySalaryRange);
			$output_monthlySalaryRange = preg_replace('/\s+/', '', $output_monthlySalaryRange);
			$output_monthlySalaryRange = addslashes(trim(strip_tags($output_monthlySalaryRange)));
		} else {
			$output_monthlySalaryRange = NULL;
		}
		
		// -------------------------------------
		// Location
		$get_start = '<div class="location-areas">';
		$get_end = '</div>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_location = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_location = str_replace('<span class="location">', "", $output_location);
			$output_location = str_replace("</span>", "", $output_location);
			$output_location = str_replace("&#8234;", "", $output_location);
			$output_location = trim($output_location);
			$output_location = preg_replace('/\s+/', '', $output_location);
			$output_location = addslashes(trim(strip_tags($output_location)));
		}
		else
		{
			$output_location = '';
		}
		
		// -------------------------------------
		// Company Name
		$get_start = 'Company Name:';
		$get_end = '</strong>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_companyName = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_companyName = str_replace("</span>", "", $output_companyName);
			$output_companyName = str_replace("<strong>", "", $output_companyName);
			$output_companyName = preg_replace('/\s+/', ' ', $output_companyName);
			$output_companyName = addslashes(trim(strip_tags($output_companyName)));
		}
		else
		{
			$output_companyName = '';
		}
		
		// -------------------------------------
		// Job Date Posted
		$get_start = '<span>Posted on: ';
		$get_end = '</span>';

		if(strpos($website_page_contents, $get_start) > strlen($get_start))
		{ 
			$select_pos_start = strpos($website_page_contents, $get_start) + strlen($get_start);
			$select_pos_end = strpos($website_page_contents, $get_end, $select_pos_start);
			$select_length = $select_pos_end - $select_pos_start;

			$output_jobDatePosted = trim(substr($website_page_contents, $select_pos_start, $select_length));
			$output_jobDatePosted = trim($output_jobDatePosted);
			$output_jobDatePosted = addslashes(trim(strip_tags($output_jobDatePosted)));
		}
		else
		{
			$output_jobDatePosted = '';
		}
		
		$output_industry = $job_industry;
		
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
							$output_jobDatePosted
							);
		// --------------------------------------------
		
		//break;
	}
	// ==========================================================

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