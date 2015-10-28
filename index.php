<?php
// Load the Google API PHP Client Library.
ini_set('include_path', '/home/dfredriksen/google-api-php-client/src/Google/autoload.php' . ini_get('include_path'));

require_once '/home/dfredriksen/google-api-php-client/src/Google/autoload.php';


// Start a session to persist credentials.
session_start();

// Create the client object and set the authorization configuration
// from the client_secretes.json you downloaded from the developer console.
$client = new Google_Client();
$client->setAuthConfigFile('client_secrets.json');
$client->addScope(Google_Service_Analytics::ANALYTICS_READONLY);

// If the user has already authorized this app then get an access token
// else redirect to ask the user to authorize access to Google Analytics.
if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
  // Set the access token on the client.
  $client->setAccessToken($_SESSION['access_token']);

  // Create an authorized analytics service object.
  $analytics = new Google_Service_Analytics($client);

  // Get the first view (profile) id for the authorized user.
  $profile = getFirstProfileId($analytics);

  // Get the results from the Core Reporting API and print the results.
  
  $day = rand(1,27);
  $month = rand(1,10);

  $day = strlen($day) < 2 ? "0$day" : $day;
  $month = strlen($month) < 2 ? "0$month" : $month;

  $results = getResults($analytics, $profile, "2015-$month-$day", "2015-$month-$day",1);
  printResults($results, $analytics, $profile);
} else {
  $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/oauth2callback.php';
  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}


function getFirstprofileId(&$analytics) {
  // Get the user's first view (profile) ID.

  // Get the list of accounts for the authorized user.
  $accounts = $analytics->management_accounts->listManagementAccounts();

  if (count($accounts->getItems()) > 0) {
    $items = $accounts->getItems();
    $firstAccountId = $items[0]->getId();

    // Get the list of properties for the authorized user.
    $properties = $analytics->management_webproperties
        ->listManagementWebproperties($firstAccountId);

    if (count($properties->getItems()) > 0) {
      $items = $properties->getItems();
      $firstPropertyId = $items[0]->getId();

      // Get the list of views (profiles) for the authorized user.
      $profiles = $analytics->management_profiles
          ->listManagementProfiles($firstAccountId, $firstPropertyId);

      if (count($profiles->getItems()) > 0) {
        $items = $profiles->getItems();

        // Return the first view (profile) ID.
        return $items[0]->getId();

      } else {
        throw new Exception('No views (profiles) found for this user.');
      }
    } else {
      throw new Exception('No properties found for this user.');
    }
  } else {
    throw new Exception('No accounts found for this user.');
  }
}

function getResults(&$analytics, $profileId, $startdate = '2015-01-01', $enddate = '2015-01-01', $startindex = 1) {
    $optParams = array( 
        'dimensions' => 'ga:pagePath',
        'max-results' => 100,
        'start-index' => $startindex,
        'filters' => 'ga:pagePath=@/story/,ga:pagePath=@/features/;ga:pageviews>100'
    );
  
   return $analytics->data_ga->get(
      'ga:' . $profileId,
      $startdate,
      $enddate,
      'ga:pageviews',
      $optParams);
}

function printResults(&$results, &$analytics, $profileId) {
      
  // Parses the response from the Core Reporting API and prints
  // the profile name and total sessions.
  $startIndex = 1;
  $loop = true;
  // Get the entry for the first entry in the first row.

  $file = fopen('nyc_sample.csv', 'a');
  
  $count = 0;
  while($count < 365) 
  {
      $day = rand(1,27);
      $month = rand(1,10);
      $day = strlen($day) < 2 ? "0$day" : $day;
      $month = strlen($month) < 2 ? "0$month" : $month; 
      $date = "2015-$month-$day";

        echo "$count:\n";
        echo "Random 100 for $date:\n";

        $rows = $results->getRows();        
        foreach($rows as $row) 
        {
            if( stristr($row[0], ",") > -1 || stristr($row[1], ",") > -1 || stristr($row[2], ",") > -1 || stristr($row[0], "?") > -1) {
                continue;
            } else {
                $output = $row[0] . "," . $date . "," . $row[1] . "\n";
                fwrite($file, $output);
                echo $output;
            }
        }
      
        $results = getResults($analytics, $profileId,$date,$date,1);
        $startIndex++;       
        if( $startIndex % 10 == 0) sleep(1); //Avoid API rate limits    
        if(count($results) > 0)
            $count++;
  }

  fclose($file);

  die("done");
}

