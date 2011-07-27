<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
    <script src="http://hatteras.meas.ncsu.edu/hadinon/mapiconmaker.js" type="text/javascript"></script>
    <script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=ABQIAAAA88_iwxxb1RIDVcnydI6KqBRyqzClQTNiLXQmSpWrmK3wX-IJAhRGQXsmMoKmyTrgausL5dKC2HEbPA&sensor=false" type="text/javascript"></script>
    <script type="text/javascript" src="http://www.nc-climate.ncsu.edu/klgore/Awesomeness3.js"></script>
    <script type="text/javascript" src="http://www.google.com/jsapi"></script>
</head>
<body>
<style type="text/css">
table#chart_table{
 display:inline;
}
</style>

<?php  
require_once( 'cronos.php' );
require_once( 'ETfunctionAPI.php' );

$c = new CRONOS( '2a46c43c6354e1eb94b1303d8af9b923641fde35780d688a1a8c915b8e3c4' ); // Replace with your API key.

// Collect data for requested station.
$results = $c->listStations( array(), array(), array($_REQUEST['station']), array(), true );

$stations=array();
$stninfo=array();

foreach ($results as $r){
  
  $stations[] = $r['station'];
  $stninfo['elev'] = $r['elev'];
  $stninfo['type'] = $r['network'];
  $stninfo['lat'] = $r['lat'];
  $stninfo['lon'] = $r['lon'];
  $stninfo['name'] = $r['name'];
  $stninfo['county'] = $r['county'];
  $stninfo['city'] = $r['city'];
  $stninfo['state'] = $r['state'];
  $stninfo['startdate'] = $r['startdate'];
  $stninfo['enddate'] = $r['enddate'];
  $stninfo['station'] = $r['station'];
       
}

//Start and enddates from above. Put this into multi-dimensional array?
$start=$stninfo['startdate'];
//date('Y-m-d',strtotime($_REQUEST['date']));
$end=$stninfo['enddate'];
//date('Y-m-d',strtotime($_REQUEST['date']));

$daily = $c->getDailyData( $stations, $start, $end );
//print_r( $daily ); // Uncomment for raw data from $daily

// Display the reference ET per station per day (simple loop)
//$et_estimate=0;
foreach( $daily as $d ) {
    
  // Format the day of year for reference ET estimate
  $doy=date('z',strtotime($d['ob']));
  $doy=$doy+1;
//ADD IN COMMAND TO NOT COMPUTE IF VALUES ARE NULL?
  // Compute the reference ET
  
  //include sravg!='' argument for ECONET and RAWS networks which record SR
  if($stninfo['type']=='ECONET' || $stninfo['type']=='RAWS'){
  if($d['sravg']!='' && $d['tempmax']!='' && $d['tempmin']!='' && $d['wsavg']!='' && $d['rhmax']!='' && $d['rhmin']!=''){
  $stninfo['data'][$d['ob']]['etavg']=HargreavesRad_ET_estimate($stninfo['type'],$d['sravg'],$d['tempmax'],$d['tempmin'],$d['wsavg'],$d['rhmax'],$d['rhmin'],$doy,$stninfo['elev'],$stninfo['lat'],$stninfo['lon']);
  $stninfo['data'][$d['ob']]['etavg_inch']=($stninfo['data'][$d['ob']]['etavg']*0.03937007874);
  }
  }else{
    //exclude sravg!='' argument for ASOS/AWOS since this parameter is always NULL for those networks.
  if($d['tempmax']!='' && $d['tempmin']!='' && $d['wsavg']!='' && $d['rhmax']!='' && $d['rhmin']!=''){
  $stninfo['data'][$d['ob']]['etavg']=HargreavesRad_ET_estimate($stninfo['type'],$d['sravg'],$d['tempmax'],$d['tempmin'],$d['wsavg'],$d['rhmax'],$d['rhmin'],$doy,$stninfo['elev'],$stninfo['lat'],$stninfo['lon']);
  $stninfo['data'][$d['ob']]['etavg_inch']=($stninfo['data'][$d['ob']]['etavg']*0.03937007874);
  } 
  }
  //Format the date as required by Google Annotated Vis.
  $date=date('Y-m-d',strtotime($d['ob']));
  list($Y,$M,$D)=explode("-",$date);
  $m=$M-1;
  $stninfo['data'][$d['ob']]['date']="new Date (".($Y+0).", ".($m+0).", ".($D+0).")";
  //echo "HELLO";
  //echo $et_estimate."\n";
  //echo "The reference ET for ".$d['ob']." is: ".$stninfo['data'][$d['ob']]['etavg']." at Station: ".$d['station']."at elevation, latitude, longitude, and network of: ".$stninfo['elev'].",".$stninfo['lat'].",".$stninfo['lon'].",".$stninfo['type']."\n";
 //NEXT STEP: CREATE MULTIDIMENSIONAL ARRAY TO OUTPUT NAME, COUNTY, CITY, STATE, START_DATE, END_DATE, AND ETAVG_INCH. ADD THIS INTO CODE FOR GOOGLE MAPS. LIMIT ET FROM 0 TO 10. SEND TO CHRIS!
 // echo "Name: ".$stninfo['name'].", County: ".$stninfo['county'].", City: ".$stninfo['city'].", State: ".$stninfo['state'].", Startdate: ".$stninfo['startdate'].", Enddate: ".$stninfo['enddate']."\n";
}

?>
<script type="text/javascript">
//Set up Google Annotated Timeline properties (date as a date and add an ET line).
    google.load('visualization', '1', {packages: ['annotatedtimeline']});
    function drawVisualization() {
   var data = new google.visualization.DataTable();
  data.addColumn('date', 'Date');
  data.addColumn('number', 'Calculated Daily PM ET');
<?php
//Loop through results and put them into array called $data. Output results for annotated timeline (dependent //on requested unit).
$row=0;
foreach($stninfo['data'] as $data){
   if($data['etavg']<=0 || $data['etavg']>10){ 
   continue;
   }?>
  data.addRows(1);
  data.setValue(<?php echo $row;?>, 0, <?php  echo $data['date'];?>);
  data.setValue(<?php echo $row;?>, 1, <?php If($_REQUEST['unit']=='mm'){echo $data['etavg'];}
    elseif($_REQUEST['unit']=='inches'){echo $data['etavg_inch'];}?>);
  <?php
  $row++;}?>
      var annotatedtimeline = new google.visualization.AnnotatedTimeLine(
          document.getElementById('visualization'));
      //Specify timeline properties.
      annotatedtimeline.draw(data, { 'displayAnnotations': true,
                                    'allValuesSuffix': '<?php If($_REQUEST['unit']=='mm'){echo " mm";}
				    elseif($_REQUEST['unit']=='inches'){echo " inches";}?>', 
                                    // A suffix that is added to all values
				    'colors':['green'], // The colors to be used
                                    'displayExactValues': true, // Do not truncate values (i.e. using K suffix)
                                    'legendPosition': 'newRow', // Can be sameRow
                                    'zoomStartTime': new Date(<?php echo $_REQUEST['year'];?>, 0 ,1), 
                                     //NOTE: month 1 = Feb (javascript to blame)
                                    'zoomEndTime': new Date(<?php echo $_REQUEST['year'];?>, 11 ,31) 
                                    //NOTE: month 1 = Feb (javascript to blame)
                                   });
		  }
      google.setOnLoadCallback(drawVisualization);
  </script>
<?php //Echo station metadata and link to explain station types.
echo "<p><b>Station: </b>".$stninfo['name']." (".$stninfo['station'].")<br><b>Type: </b>".$stninfo['type']." <A href=# onClick=window.open('http://www.nc-climate.ncsu.edu/dynamic_scripts/cronos/types.php','link','width=500,height=1000,scrollbars=yes')>what does this mean?</A> <br><b>Elevation: </b>".$stninfo['elev']." feet above sea level<br><b>Location: </b>".$stninfo['city'].", ".$stninfo['state']."<br><b>Start Date: </b>".$stninfo['startdate']."<br><b>End Date: </b>".$stninfo['enddate']."</p>";?>
<p><table id="chart_table"><tr>
<?php If($_REQUEST['unit']=='inches'){ ?>
  <td><form action="http://www.nc-climate.ncsu.edu/hadinon/refETdynchart.php?station=<?php echo $stninfo['station'];?>&year=<?php echo $_REQUEST['year'];?>&unit=mm" method="post">
  <input type="submit" name="units" value="Display mm">
  </form></td>
<?php }
elseif($_REQUEST['unit']=='mm'){
?>
  <td><form action="http://www.nc-climate.ncsu.edu/hadinon/refETdynchart.php?station=<?php echo $stninfo['station'];?>&year=<?php echo $_REQUEST['year'];?>&unit=inches" method="post">
  <input type="submit" name="units" value="Display inches">
  </form></td>
<?php }
?>
  <td><form action="http://www.nc-climate.ncsu.edu/et" method="post">
  <input type="submit" name="main" value="Main page">
  </form></td></tr></table></p>
<p><u><b><?php echo "Time Series of FAO56 Penman-Monteith Estimated Reference Evapotranspiration";?></u></b></p>
<div id="visualization" style="width: 800px; height: 400px;"></div>
<br><br><p align='left'><img src='http://www.nc-climate.ncsu.edu/hadinon/get_adobe_flash_player.png' width='158' height='39' border='0' usemap='#Map'><map name='Map'><area shape='rect' coords='0,0,162,44' href='http://get.adobe.com/flashplayer/?promoid=BUIGP'></map></p>
</body>
</html>