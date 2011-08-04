<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <script src="mapiconmaker.js" type="text/javascript"></script>
    <!--Change below API key to your key.-->
    <script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=ABQIAAAA88_iwxxb1RIDVcnydI6KqBRyqzClQTNiLXQmSpWrmK3wX-IJAhRGQXsmMoKmyTrgausL5dKC2HEbPA&sensor=false" type="text/javascript"></script>
    <script type="text/javascript" src="http://www.google.com/jsapi"></script>
</head>
<body>
<style type="text/css">
.infowindow3{
	width: 325px;
	height: 150px;
	font-size: 14px;
}
div#mapcanvasTEST_ET {
	z-index: 0;
	width: 905px;
	height: 855px;
	border: 1px solid #000000;
	margin: 25px;
}
</style>

<?php
//Obtain metadata for displaying stations on Google map (below).

require_once( 'cronos.php' );

$c = new CRONOS( '2a46c43c6354e1eb94b1303d8af9b923641fde35780d688a1a8c915b8e3c4' ); // Replace with your API key.

// Collect data from ECONET, RAWS, ASOS and AWOS networks for NC, SC, AL, FL, GA, and VA.
$results = $c->listStations( array( 'ECONET', 'RAWS', 'ASOS', 'AWOS' ), array( 'NC', 'SC', 'AL', 'FL', 'GA', 'VA' ), array(), array(), true );

$stninfo=array();

foreach( $results as $r ) {
  $stninfo[$r['station']]['station'] = $r['station'];
  $stninfo[$r['station']]['elev'] = $r['elev'];
  $stninfo[$r['station']]['type'] = $r['network'];
  $stninfo[$r['station']]['lat'] = $r['lat'];
  $stninfo[$r['station']]['lon'] = $r['lon'];
  $stninfo[$r['station']]['name'] = $r['name'];
  $stninfo[$r['station']]['county'] = $r['county'];
  $stninfo[$r['station']]['city'] = $r['city'];
  $stninfo[$r['station']]['state'] = $r['state'];
  $stninfo[$r['station']]['startdate'] = $r['startdate'];
  $stninfo[$r['station']]['enddate'] = $r['enddate'];
}
?>

<link type="text/css" href="http://jqueryui.com/latest/themes/base/jquery.ui.all.css" rel="stylesheet"/>
<script type="text/javascript" src="http://jqueryui.com/latest/jquery-1.5.1.js"></script>
<script type="text/javascript" src="http://jqueryui.com/latest/ui/jquery.ui.core.js"></script>
<script type="text/javascript" src="http://jqueryui.com/latest/ui/jquery.ui.datepicker.js"></script>
<script type="text/javascript">

//Function to set up the Javascript calendar.
$(function() {
	    $("#datepicker").datepicker({showOn: 'both', buttonImage: 'calendar.gif', buttonImageOnly: true, changeMonth: true, changeYear: true, dateFormat: 'yy-mm-dd', minDate: (new Date(2002, 1 - 1, 1)), maxDate: '-1D'});
});

//Set up the map and its properties (location, type, and user controls).
function initialize3() {
 if (GBrowserIsCompatible()) { 
  var map = new GMap2(document.getElementById("mapcanvasTEST_ET"));
  map.setUIToDefault();
  map.setCenter(new GLatLng(31.8,-81.5), 6);
  map.setMapType(G_PHYSICAL_MAP);
  map.disableScrollWheelZoom();
  map.disableDoubleClickZoom();
<?php
//Loop through results and put them into two seperate arrays, $station and $data, using the list function.
while(list($station,$data)=each($stninfo)){
?>
//Create a new point with the station lat/lon.
var myLatLon = new GLatLng(<?php echo $data['lat']; ?>, <?php echo $data['lon']; ?>);

//Set up the marker icon properties (width, height, color, shape, etc).
var iconOptions = {};
  iconOptions.width = 12;
  iconOptions.height = 12;
  iconOptions.primaryColor = '#000000';
  iconOptions.label = "";
  iconOptions.labelSize = 0;
  iconOptions.labelColor = '#000000';
  iconOptions.shape = "circle";
  
  //Create a new variable for the above marker specifications.
  var icon = MapIconMaker.createFlatIcon(iconOptions);

  //Function to create a clickable marker and open an Info Window at each marker. 
  //Each marker has station metadata and a link to explain station type.
  function create<?php echo $station;?>Marker(myLatLon) {

  //Set up our GMarkerOptions object
  markerOptionsThree = { icon:icon };
  var marker = new GMarker(myLatLon, markerOptionsThree);
  marker.station = "<?php echo $station;?>";
  GEvent.addListener(marker, "click", function() {
    marker.openInfoWindowHtml("<div class='infowindow3'><?php echo "<p style='font-weight:bold;font-size:14px;text-decoration:underline;text-align:center;'>Station Information:</p>"; echo "<b>Name: </b>"; echo $data['name']; echo " ("; echo $station; echo ")"; echo "<br><b>Location: </b>"; echo $data['city'].", ".$data['state']; echo "<br><b>Elevation: </b>"; echo $data['elev']; echo " feet above sea level"; echo "<br><b>Type: </b>"; echo $data['type']; echo " <A href=# onClick=window.open('http://www.nc-climate.ncsu.edu/dynamic_scripts/cronos/types.php','meta_info','width=500,height=1000,scrollbars=yes,resizable=yes')>what does this mean?</A>"; echo "<br><b>Start Date: </b>"; echo $data['startdate']; echo "<br><b>End Date: </b>"; echo $data['enddate']; echo"</div>";?>
    ");
  });
  return marker;
}

//Add the stations to the map.
  map.addOverlay(create<?php echo $station;?>Marker(myLatLon));
  <?php
  } ?> //ends the while loop.
 }  //Ends the if GBrowserIsCompatible statement.
} //Ends function initialize3

//Execute onload and onunload here instead of in the body tag (see Google Maps API example).
if(window.addEventListener){
	window.addEventListener("load",initialize3,false);
}
else{
	window.attachEvent("onload",initialize3);	
}
if(window.addEventListener){
 window.addEventListener( "unload", GUnload, false ); 
} 
else {
 window.attachEvent( "onunload", GUnload ); 
}
</script>
<form action="refETdynmap.php" method="get">
<p><b><i>Please select your date and unit of interest.</b></i></p>
                  <div class="demo">
                   <p>Date: <input type="text" name="date" id="datepicker" size="30"/>
                    &nbsp&nbsp&nbsp Unit:
                  <select name="unit">
                    <option value="inches">inches</option>
                    <option value="mm">mm</option>
                  </select>
                  &nbsp&nbsp&nbsp&nbsp<input type="submit" value="Submit" class="button" />
            &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<A href='http://www.nc-climate.ncsu.edu/et'>Back to Main Page</A>
                   <br> &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp (YYYY-MM-DD)
                  </p></div>
                  <hr width=95%>
                  <center><div id="mapcanvasTEST_ET"></div></center>
</form>
</body>
</html>