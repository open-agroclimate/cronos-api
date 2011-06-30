<?php
/**
 * @file cronos.php
 * @brief The main CRONOS library
 *
 * @mainpage notitle
 * @section License
 * Licesned under the BSD Modified license.
 * Copyright (c) 2011, The Open AgroClimate Project
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * 
 * * Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 * 
 * * Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation 
 *   and/or other materials provided with the distribution.
 * 
 * * Neither the name of the The Open AgroClimate Project nor the names of its 
 *   contributors may be used to endorse or promote products derived from this 
 *   software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED 
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE 
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL 
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR 
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

require_once('lib/HttpClient.class.php');
ini_set('memory_limit','-1'); // EVIL HACK... need to deal with large datasets better
/**
 * @class CRONOS
 * @brief The CRONOS class is the main interface to connect to the CRONOS database.
 *
 * This class is used to connect to the CRONOS database. It provides a connection handler and convenience functions.
 *
 * @todo Add quality control flags to getHourlyData and getDailyData. (CV)
 * @todo Add ability to parse the comments for parameters and station metadata (CV)
 * @todo Organize the hourly and daily output array by [station][date] = data vs. a list. (CV)
 */
class CRONOS {
  private $http;
  private $hash;
  private $filters;
  
  /**
   * Creates a new CRONOS instance.
   *
   * A new CRONOS instance is created using your CRONOS API key. If you need a key, email sco@climate.ncsu.edu.
   *
   * @param string $api_key
   *   A string containing your CRONOS API key. DEFAULT: ''
   * 
   */
  public function __construct( $api_key = '' ) {
    $this->http = new HttpClient('www.nc-climate.ncsu.edu');
    $this->hash = $api_key;
    $this->filters = array();
  }
  
  /**
   * List stations available, with filters.
   *
   * List all stations available in the CRONOS database as an array. Can be filtered according to network, state and whether or not a station is active.
   *
   * @param array $networks
   *  The networks you want included in list. By passing an empty array, you will get all the networks available. DEFAULT: array()
   *
   * @param array $states
   *  The states you want included in the list. By passing an empty array, you will receive all the states available. DEFAULT: array()
   *
   * @param array $exclude_networks
   *   The networks you would like excluded from the list. By passing an empty array, you will not exclude any networks. DEFAULT: array()
   *
   * @param bool $active_only
   *   Only include active stations in the list. DEFAULT: false
   *
   * @return
   *   An indexed array of associative arrays containing:
   *   - station: The station id
   *   - name: The name of the station
   *   - lat: Latitude
   *   - lon: Longitude
   *   - elev: Elevation
   *   - network: Network the station belongs to
   *   - city: City
   *   - county: County
   *   - state: State
   *   - huc: ??
   *   - climdiv: Climate Division
   *   - startdate: Date this station started reporting
   *   - enddate: Date this station last reported
   *   - active: 1 is the station is active, 0 if it is not.
   */
  public function listStations( $networks = array(), $states = array(), $exclude_networks = array(), $active_only = false ) {
    $nets = implode( ',', $networks );
    $sts  = implode( ',', $states );
    $data = array();
    $data['network'] = $nets;
    $data['state']   = $sts;
    $data['hash']    = $this->hash;
    
    if( count( $exclude_networks ) !== 0 ) {
      $this->filters['exclude_networks'] = $exclude_networks;
    } else {
      unset( $this->filters['exclude_networks'] );
    }
    
    if( ! $this->http->get('/dynamic_scripts/cronos/getCRONOSinventory.php', $data ) ) {
      return false;
    } else {
      $results = $this->parseToObject( $this->http->getContent() );
      
      if( ( ! is_null( $this->filters ) ) && ( array_key_exists( 'exclude_networks', $this->filters ) ) ) {
        $results = array_filter( $results, array( $this, 'exclude_networks' ) );
      }      
      if( $active_only ) {
        $results = array_filter( $results, array( $this, 'active_only' ) );
      }
      return $results;
    }
  }

  /**
   * Pull hourly data from an array of stations.
   *
   * Pull hourly data from an array of stations from the CRONOS database, based on a time window.
   *
   * @param array $stations
   *   A list of station ids. DEFAULT: array()
   *
   * @param string $start
   *   A starting datetime string formatted as YYYY-MM-DD hh:mm:ss  DEFAULT: ''
   *
   * @param string $end
   *   A ending datetime string formatted as YYY-MM-DD hh:mm:ss  DEFAULT: ''
   *
   * @return
   *   An indexed array of associative arrays. The associcative arrays consist of the data provided by the station. They may or may not be consistant across networks.
   */
  public function getHourlyData( $stations = array(), $start = "", $end = ""  ) {
    $data = array( 'station' => implode( $stations, ',' ), 'hash' => $this->hash, 'start' => $start, 'obtype' => 'H', 'parameter' => 'all' );
    if( $end != '' ) {
      $data['end'] = $end;
    }
    return $this->getStationData( $data );  
  }

  /**
   * Pull daily data from an array of stations.
   *
   * Pull daily data from an array of stations from the CRONOS database, based on a date window.
   *
   * @param array $stations
   *   A list of station ids. DEFAULT: array()
   *
   * @param string $start
   *   A starting date string formatted as YYYY-MM-DD DEFAULT: ''
   *
   * @param string $end
   *   A ending date string formatted as YYY-MM-DD DEFAULT: ''
   *
   * @return
   *   An indexed array of associative arrays. The associcative arrays consist of the data provided by the station. They may or may not be consistant across networks.
   */  
  public function getDailyData( $stations = array(),  $start = "", $end = "" ) {
    $data = array( 'station' => implode( $stations, ',' ), 'hash' => $this->hash, 'start' => $start, 'obtype' => 'D', 'parameter' => 'all' );
    if( $end != '' ) {
      $data['end'] = $end;
    }
    return $this->getStationData( $data );
  }
  
  private function getStationData( $data ) {
    if( ! $this->http->get( '/dynamic_scripts/cronos/getCRONOSdata.php', $data ) ) {
      //echo 'DEBUG (QUERY:FAILED):: ',$this->http->getRequestURL()."\n";
      echo 'Failed (getStationData)';
      return false;
    } else {
      //echo 'DEBUG (QUERY):: '.$this->http->getRequestURL()."\n";
      return $this->parseToObject( $this->http->getContent() );
    }
  }
  
  private function exclude_networks( $item ) {
    if( in_array( $item['network'], $this->filters['exclude_networks'] ) ) {
      return false;
    } else {
      return true;
    }
  }
  
  private function active_only( $item ) {
    return ( $item['active'] == 1 );
  }

  
  private function parseToObject( $content, $parseComments=false ) {
    $first_line = true;
    $map = array();
    $results = array();
    $token = strtok( $content, "\n" );
    while( $token !== false ) {
      //echo "DEBUG:: {$token}\n";  // Remove comments to get raw values from website displayed.
      $tmp = array();
      // Parse the line here.
      if( substr( $token, 0, 1 ) == '#' || substr( $token, 0, 5 ) == '<pre>' ) {
      } else {
        if( $first_line ) {
          $map = explode( '|', $token );
          $first_line = false;
        } else {
          $tmp_line = explode( '|', $token );
          $l = count( $tmp_line );
          for( $i=0; $i < $l; $i++ ) {
            $tmp[$map[$i]] = $tmp_line[$i];
          }
          $results[] = $tmp;
        }
      }
      $token = strtok( "\n" );
    }
    return $results;
  }
}



?>
