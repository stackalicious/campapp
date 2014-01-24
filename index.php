<?php

require_once 'Libraries/HPCloud-PHP-master/src/HPCloud/Bootstrap.php';

use \HPCloud\Bootstrap;
use \HPCloud\Services\IdentityServices;
use \HPCloud\Storage\ObjectStorage;
Bootstrap::useAutoloader();

# TODO: Read these from a parameters.ini file
$account            = '';
$secret             = '';
$tenantid           = '';
$idServicesEndpoint = '';

$idService          = new IdentityServices($idServicesEndpoint);
$token              = $idService->authenticateAsAccount($account, $secret, $tenantid);

$objectServiceStorage   = ObjectStorage::newFromServiceCatalog($idService->serviceCatalog(), $token);
$photosContainer        = $objectServiceStorage->container('DevOpsWorkshopSF');
    
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	
    <title>StackUp</title>
	<meta name="viewport" content="initial-scale=1">
    <meta name="description" content="StackUp">
    <meta name="author" content="Platform D, 2013">
	<meta name="geography" content="Mountain View, CA">
	<meta name="copyright" content="(c) 2013, Platform D. All rights reserved.">
	<meta name="designer" content="Take Flight Graphics: www.takeflightgraphics.com">
	<meta name="distribution" content="global">
	<meta name="robots" content="index, follow">

	<link rel="shortcut icon" href="public/img/favicon.ico">
	
	<link rel="stylesheet" href="public/css/hack-global.css">
	<link rel="stylesheet" href="public/css/containers.css">
	<link rel="stylesheet" href="public/css/lists-links.css">
	<link rel="stylesheet" href="public/css/forms.css">
	
	<link rel="stylesheet" href="public/css/webFonts.css">
	<link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Muli">
	
	<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=places"></script>
    <script src="public/js/datescript.js"></script>
	
	<!-- royalslider stylesheets -->  
	<link rel="stylesheet" href="public/css/royalslider.css" class="rs-file">       
	<link rel="stylesheet" href="public/css/rs-default.css" class="rs-file">

    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script> <!-- royalslider, jQuery cycle, superfish menu -->
	<script class="rs-file" src="public/js/jquery.royalslider.min.js"></script>
	
	<script src="public/js/datescript.js"></script>
	
    <script >
        campsiteHost="http://www.campsite.org";
        groupsArray = [];
        
        var map;
        var markersArray = [];
        var infoArray = [];

        function initialize() {
            var mapOptions = {
                center: new google.maps.LatLng(55.1136944, -6.6849769),
                zoom: 1,
                mapTypeId: google.maps.MapTypeId.ROADMAP
            };

            var map = new google.maps.Map(document.getElementById('map-canvas'),
                mapOptions);

            var input = document.getElementById('map-location-search');
            var autocomplete = new google.maps.places.Autocomplete(input);

            autocomplete.bindTo('bounds', map);

            google.maps.event.addListener(autocomplete, 'place_changed', function() {
                input.className = '';
                var place = autocomplete.getPlace();
                if (!place.geometry) {
                    // Inform the user that the place was not found and return.
                    input.className = 'notfound';
                    return;
                }

                // If the place has a geometry, then present it on a map.
                if (place.geometry.viewport) {
                    map.fitBounds(place.geometry.viewport);
                } else {
                    map.setCenter(place.geometry.location);
                    map.setZoom(17);
                }
            });

            geocoder = new google.maps.Geocoder();

            if (groupsArray.length > 0) {

                var infowindow = new google.maps.InfoWindow();

                for (i=0; i<groupsArray.length; i++) {

                    var contentString = '<div class="map-info">' +
                            '<div class="map-info-title"><a href="' + groupsArray[i]['url'] + '">' + groupsArray[i]['name'] + '</a></div>' +
                            '<div class="map-info-address1">' + groupsArray[i]['address1'] + '</div>' +
                            '<div class="map-info-address2">' + groupsArray[i]['address2'] + '</div>' +
                            '<div class="map-info-city">' + groupsArray[i]['city'] + '</div>' +
                            '<div class="map-info-state">' + groupsArray[i]['stateProvince'] + '</div>' +
                        '</div>';
                        
                    var address = groupsArray[i]['location'] + " " + 
                                groupsArray[i]['address1'] + " " + 
                                groupsArray[i]['address2'];
                                  
                    var name = groupsArray[i]['name'];
                    
                    geocoder.geocode( { 'address': address}, function(results, status) { 
                        if(results[0] == null)
                            return;
                                                   
                        var marker = new google.maps.Marker({
                            title:name,
                            map: map,
                            info: contentString,
                            position: results[0].geometry.location
                        });

                        markersArray.push(marker);

                        google.maps.event.addListener(marker, 'click', function() {
                            infowindow.setContent(this.info);
                            infowindow.open(map, this);
                        });
                    });
                }
            }
        }
    </script>
    
    <script id="addJS">
	    jQuery(document).ready(function($) {
		
		    $("#campsite").attr("href", campsiteHost);
		    $("#about").attr("href", campsiteHost + $("#about").attr("href"));
		    $("#home").attr("href", campsiteHost + $("#home").attr("href"));
		    $("#contact").attr("href", campsiteHost + $("#contact").attr("href"));
		    $("#contact2").attr("href", campsiteHost + $("#contact2").attr("href"));
		    $("#signin").attr("href", campsiteHost + $("#signin").attr("href"));
		    $("#createaccnt").attr("href", campsiteHost + $("#createaccnt").attr("href"));
		    $("#join").attr("href", campsiteHost + $("#join").attr("href"));
		    $("#join2").attr("href", campsiteHost + $("#join2").attr("href"));
		    $("#mwe").attr("href", campsiteHost + $("#mwe").attr("href"));
		    
		    var url = campsiteHost + '/api/groups/16';
		    
		    $.getJSON(url + '?callback=?', null,
                function(data) {
                    eventLocationsEs = null;
                    
                    $('#description').append(data.description);
                    
                    $("#group-link").attr("href", data.url);
                    console.log(data.avatarPath);
                    if(data.avatarPath != null) {
		                $logo = $("#brand-group");
		                $logo.attr("src", data.avatarPath);
		                $logo.attr("style", "max-height:64px;max-width:413px");
		            }
		            
		            $("#mre").attr("href", data.url + "events");
		            
		            var group = $("#group");
                    group.attr("href", data.url);
                    group.text(data.name);
		                      
                    entrySets = data.entrySets;
                    if(entrySets) {
                        for (i in entrySets){
                            var es = entrySets[i];
                            
                            if(es.name == 'Attend a Stackup Near You') {
                                eventLocationsEs = es;
		                        $("#mwe").attr("href", eventLocationsEs.url);
                            }
                        }
                    }
                    
                    if(eventLocationsEs) {
                        for(i in eventLocationsEs.entries) {
                            var entry = eventLocationsEs.entries[i];
                            $('#locations').append(
                                '<tr '+(i%2==0?'class="fill"':'')+'><td><a href="' + entry.url + '">' + entry.name + '</td>' +
                                '<td>' + entry.numVotes + '</td></tr>'
                            );
                        }
                    }
                    
                    if(data.pastEvents) {
                        for(i in data.pastEvents) {
                            var event = data.pastEvents[i];
                            $('#pastEvents').append(
                                '<tr '+(i%2==0?'class="fill"':'')+'><td><a href="' + event.url + '">' + event.name + '</a></td>' +
                                '<td>' + event.daterange + '</td></tr>'
                            );
                        }
                    }
                    
                    if(data.upcomingEvents) {
                        for(i in data.upcomingEvents) {
                            var event = data.upcomingEvents[i];
                            $('#upcomingEvents').append(
                                '<tr '+(i%2==0?'class="fill"':'')+'><td><a href="' + event.url + '">' + event.name + '</a></td>' +
                                '<td>' + event.daterange + '</td></tr>'
                            );
                            
                            singleGroup = [];
                            singleGroup['name'] = event.location;
                            singleGroup['address1'] = event.address1;
                            singleGroup['address2'] = event.address2;
                            singleGroup['url'] = event.url;
                            groupsArray.push(singleGroup);
                        }
                    }
                    
                    $('#nextEventName').append('<a href="'+data.nextEvent['url']+'">'+data.nextEvent['name']+'</a>');
                    $('#nextEventDateRange').append(data.nextEvent.daterange);
                    $('#nextEventLocation').append(
                        data.nextEvent['location'] + '<br/>' + 
                        data.nextEvent['address1'] + '<br/>' + 
                        data.nextEvent['address2']);
                     
                    initialize();
                });
	    });
    </script>
	
</head>

<body>
    <noscript>For full functionality of this site it is necessary to enable JavaScript. Here are the instructions on how to <a href="http://www.enable-javascript.com/" target="_blank">enable JavaScript in your web browser</a>.</noscript>

    <div id="wrapper-ht">
	
        <div id="header-group" id="header" style="height: 120px;"> <!-- begin header -->
        
	        <a href="http://www.campsite.org" target="_blank">
	            <img src="public/img/logo-campsite-sm.png" width="100" height="23" alt="" class="brand-small">
            </a>
	        <div id="topNav">
		        <ul>
			        <li><a id="contact2" target="_blank" href="/contact">Contact Us</a></li>
			        <li><a id="join" target="_blank" href="/stackup/join"">Join Stackup</a></li>
			        <li><a id="campsite" target="_blank" href="/">Campsite</a></li>
		        </ul>
	        </div>
	
	        <a id="group-link" href="" target="_blank">
	            <img src="" alt="" id="brand-group">
            </a>
	
	        <div id="tagline-group" style="top:110px;">
		        <span class="red">share.</span>
		        <span class="grn">learn.</span>
		        <span class="blu">grow.</span>
	        </div>
	
	        
        </div> <!-- end header -->

        <div id="content">
        
            <hr>
            
            <div id="contentLftMP">
                <h2 id="description"></h2>
	            
	            <hr class="clr">
	            
	            <div>
			        <h2>Vote For Your City</h2>
			        <br>
			        <h3>We need your help!  Let us know what cities we should bring StackUp to next by voting for your city below.  If you don't see your city below click "Add My City" to be added to the list.</h3>
		            <br>
			        <table summary="Most Recent Stackup Events" class="tblStyle">
			            
			            <tbody id="locations">
			            </tbody>
		            </table>
			
			        <a id="mwe" href="" target="_blank" class="btn bld spcr-t3 right">Not Listed? Add It Here &rsaquo;</a>
		        </div>
	                
			</div> <!-- end contentLft -->				
		
		    <div id="contentRtMP">	
		        
	            <div id="map">
                    <h2 class="spcr-b2">Find Events Near You</h2>
                    <input id="map-location-search" type="text" placeholder="Enter your location" autocomplete="off">
                    <div id="map-canvas"></div>
                </div>
                
	            <br>
	            
	            <h2>Upcoming Events</h2>
                <br>
                <table class="tblStyle">
		            <tbody id="upcomingEvents">
		            </tbody>
	            </table>
		        
		        <br/>
		        
		        <a id="mre" href="" target="_blank" class="btn bld right">View Events &rsaquo;</a>
                
                		
	        </div> <!-- end contentRt -->	
	
	        <br class="clr">
		
        </div> <!-- end content -->

        <div class="push"></div> <!-- needed for sticky footer -->
        
    </div><!-- end wrapper-ht -->

    <a name="sitemap"></a>

    <div id="footer">
        <div id="ftr-content">						
	        <div class="ftrcol">
		        <h5>About Us</h5>
		        <ul>
			        <li><a id="about" href="/about">About Campsite</a></li>
		        </ul>
	        </div>
	        <div class="ftrcol">
		        <h5>Campsite</h5>
		        <ul>
			        <li><a id="home" href="">Home</a></li>
			        <li><a id="group" href=""></a></li>
			        <li><a id="contact" href="/contact">Contact Us</a></li>
		        </ul>			
	        </div>
	        <div class="ftrcol">		
		        <ul>
			        <li><a id="signin" href="/login"><strong>SIGN IN</strong></a></li>
			        <li><a id="createaccnt" href="/account/register"><strong>CREATE AN ACCOUNT</strong></a></li>
			        <li><a id="join2" href="/stackup/join"><strong>JOIN STACKUP</strong></a></li>
		        </ul>
		
            </div>
            <div id="contactNfo" class="divrt">
                <h5>Contact Us</h5>
		
		        <p><a href="&#109;&#097;&#105;&#108;&#116;&#111;:&#115;&#117;&#112;&#112;&#111;&#114;&#116;&#064;&#099;&#097;&#109;&#112;&#115;&#105;&#116;&#101;&#046;&#111;&#114;&#103;">&#115;&#117;&#112;&#112;&#111;&#114;&#116;&#064;&#099;&#097;&#109;&#112;&#115;&#105;&#116;&#101;&#046;&#111;&#114;&#103;</a></p>
		
	        </div>
	
	        <br class="clr">

	        <div id="copyright">
		        <script language="JavaScript">document.write(include_copyright(2014));</script>
	        </div>
        </div> <!-- end footer content -->
    </div> <!-- end footer -->
		
    </body>
</html>