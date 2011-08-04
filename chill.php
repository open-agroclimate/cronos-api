<?php
define( 'DEBUG_MODE', true );
include( 'cronos.php' );

$hash = '2a46c43c6354e1eb94b1303d8af9b923641fde35780d688a1a8c915b8e3c4';
$c = new CRONOS( $hash );
$loc = $c->listStations( array( 'AWOS', 'ASOS' ), array( 'AL' ), array(), true );
/*$stations = array();

foreach( $loc as $l ) {
  $stations[] = $l['station'];
}
*/
$stations = array('KAUO');

$results = $c->getHourlyData( $stations, '2011-08-02 00:00:00', '2011-08-03 00:00:00', array( /*'tempmax', 'tempmin', 'tempavg', 'rhavg', 'dewavg', 'wsavg', 'precip' */) );



#$db = new mysqli( 'mysql.osg.ufl.edu', 'cvillalobos', 'Talw$atgigs2h', 'weather_dynamic_data', 3310 );
#$sql = $db->stmt_init();
#$sql->prepare('INSERT INTO SC_cronos_daily (LocID, yyyy, mm, dd, doy, Tavg, Tmin, Tmax, TDavg, RHavg, RainTot, WSavg) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)');
#$sql->bind_param('siiiiddddddd', $station, $year, $month, $day, $doy, $tempavg, $tempmin, $tempmax, $dewavg, $rhavg, $precip, $wsavg);
#$arr = array();
#foreach( $results as $r ) {
  # Step 1: Crap all over the symbol table
#  extract( $r );
  # Step 2: Leverage the symbol table to get quickly get the information we want.
#  if( array_key_exists( $ob, $arr ) ) {
#    extract( $arr[$ob] );
#  } else {
#    list($year, $month, $day) = explode('-', $ob); 
#    $doy = (date('z', strtotime( $ob ) ))+1;
#    $arr[$ob] = compact('year', 'month', 'day', 'doy');
#  }
#  $sql->execute();
#}

print_r( $results );
?>