<?php
define( 'DEBUG_MODE', true );

require_once( 'cronos.php' );


$c = new CRONOS( '123abc' ); // Replace with your API key.

// Collect data from COOP, CoCoRaHS, AWOS and ASOS networks for SECC states and filter out the COOP (pointless but testing)
$results = $c->listStations( array( 'COOP', 'CoCoRaHS', 'AWOS', 'ASOS' ), array( 'AL', 'FL', 'GA', 'NC', 'SC' ), array( 'COOP' ), true );

echo "====================\nStart run\n====================\n";

// Collect the stations by network.
$networks = array();
foreach( $results as $r ) {
  $networks[$r['network']][] = $r['station'];
}

// Get daily weather information for the last week from ASOS network only.
$startdate = date('Y-m-d h:m:s', strtotime( '-1 day' ));
#$startdate = '2011--25';
$daily = $c->getHourlyData( $networks['AWOS'], $startdate, '', array( 'temp', 'rh' ) );
if( DEBUG_MODE) print_r( $daily ); // Uncomment for raw data from $daily

// Display the average tempurature per station per day (simple loop)
foreach( $daily as $d ) {
  echo 'Tempurature for station '.$d['station'].' on '.$d['ob'].' was '.$d['temp'].".\n";
}
echo "====================\nEnd run\n====================\n";
echo "Max memory used: ".memory_get_peak_usage()."\n";
?>
