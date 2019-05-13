<?php
 /*
  * CCTV video compare UI
  *
  * play the videos from different directories with the same time stamp synchronously
  * Compensates for seconds drift (where timestamps will differ slighly by seconds over time)
  * 
  * William Sengdara - May 12, 2019
  *
  */

  // directories where CCTV footage is saved
  // you could glob() instead if you have dated directories 
  // e.g. 2019-02-02, 2019-02-03, 2019-02-04
  $cams = array(0=>"ipcam2substream",
		1=>"ipcam3substream",
                2=>"ipcam4substream");

  $ext = "mp4"; // extension

  // for javascript
  $js_videofeatures = "autoplay"; // "controls autoplay";
  $js_cams = array();
  $players = "";
  $js_videos  = "";
  $videofiles = "";  
	
  forEach($cams as $k=>$dir){
	$js_cams[] = "cams[$k] = \"$dir\"; \n";
  }

  foreach($cams as $key=>$dir){
	$players .= "<div class='col-md-6'>
		  	<video id='video$key' $js_videofeatures></video> 
		     </div>";
 }

 $idx        = 0;
 $dirname    = $cams[0]; // using directory 1 only, others will follow this timestamp (incl. drift)
 $files      = glob($dirname . "/*.$ext");

 // sort DESC so next = next--
 usort($files, create_function('$a,$b', 'return filemtime($b) - filemtime($a);'));

 // sort ASC so next = next++
 //usort($files, create_function('$a,$b', 'return filemtime($a) - filemtime($b);'));

 $max_files = sizeof($files);

 /* Checking
  * hh:mm:05, hh:mm:04, hh:mm:03, etc
  * */
 $checktimes = array();
 for($idx1 = 5; $idx1 <= 59; $idx1+=5){
     $checktimes[] = $idx1;
 }
	
 /*
  * videos["timestamp"] = ["TS000","TS001","TS002"];
  * */
 foreach ($files as $file) {
	   $file  = basename($file);
	   $timestamp = str_replace("-$dirname.$ext","", $file);
	   $idy   = $idx + 1;
	   
	   $seconds = explode('-',$timestamp);
	   $minutes = $seconds;
	   array_pop($minutes); // remove seconds
	   $seconds = $seconds[ 4 ];
	   $minutes = implode("-", $minutes);
	   $videofiles .= "$idy. <a href='#' onclick=\"playfile('$timestamp', $idx); return false;\">$timestamp</a> <BR>";
	   
	   $vids = array();
	   $idz = 0;
	   
	   foreach ($cams as $k=>$dir){
		    $path = "\"$dir/$file\"";
		    $bfound = false;
		    
		    if ($idz == 0){
			//$vids[] = $path;
			} else {		
			// when greater than index 0
			// find the next existing file within the current timestamp				
			$path_ = "$dir/$file";
				
			if (! file_exists($path_)){
			//echo "Nope: $path_ ,";
					
				$seconds = intval($seconds)+5;
					
				foreach ($checktimes as $t){
					if ($seconds <= $t+5){
						for($s = $seconds; $s <= $t+5; $s--){
							$pad = strlen($s) == 1 ? "0" : "";
							$file_t = "$dir/$minutes-$pad$s.$ext";
							//echo "checking $file_t.. <BR>";
								
							if ( file_exists( $file_t ) ){
								$bfound = true;
								$vids[] = "\"$dir/$minutes-$pad$s.$ext\"";
								//echo "~~ Closest: '$file_t' <BR>";
								break;
							}
								
							if ($s == 0) {
								$bfound = true; //force outer loop to break
								$vids[] = $path;
								//echo "~~~~ Gave up: No file close to main timestamp found inside $dir. <BR>";
								break;
							}
						}
					}
						
					if ($bfound){
					//echo "~~~~ leaving outer loop <BR>";
						break;
					}
				}			
			}

		} 
			
		// default path if nothing found
		if (!$bfound) {	$vids[] = $path; }
		$idz++;
	   }
	   $vids = implode(",", $vids);
	   
	   $js_videos .= "videos[\"$timestamp\"] = [$vids]; \n";
	   $idx++;
	}
?>
<!DOCTYPE html>
<html>
 <head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CCTV Footage Compare</title>
  <style>
   video {border:1px solid gray;}
   a {text-decoration:none; padding:5px;}
   div#files { max-height: 300px; overflow: scroll;}
   .container-fluid {margin-top:15px;}
  </style>
  <link href="lib/bootstrap.3.3.4.min.css" rel="stylesheet" type="text/css">
 </head>
 <body>
  <div class='container-fluid'>

   <div class='row'>
    <div class='col-md-12'>
	 <b>Speed:</b>
     <a href='#' onclick="setvideospeed(0.5, 0); return false;" class='speed'>Slower</a> /
     <a href='#' onclick="setvideospeed(1.0, 1); return false;" style='color:red' class='speed'>Normal</a> /
     <a href='#' onclick="setvideospeed(2.0, 2); return false;" class='speed'>Faster</a>
   	 <b>Playing:</b> <span id='nowplaying'>Ready</span>

	 <span class='pull-right'>
	 <b>Navigation:</b>
      <a href='#' onclick="setvideoplaying(1); return false;"><< Prev</a>
	  <a href='#' onclick="setvideoplaying(0); return false;">Next >></a>
     </span>
    </div>
   </div> <!-- row -->
	<p>&nbsp;</p>
	<div class='row'>
         <?php echo $players; ?>
     </div> <!-- row -->
    <div class='row'>
	<div class='col-md-12'>
       <p style='font-weight:bold'>Video File List (<?php echo $max_files; ?> files) - <small>Click a time stamp below to play videos. Click Prev/Next at top to navigate</small></p>
      </div>
	  <div class='col-md-6' id='files'>
		<?php echo $videofiles; ?>
	  </div>
    </div><!-- row -->  

  </div> <!-- container -->

  <script>
   var g_currIdx  = 0
   var g_playbackRate = 1.0

   var nowplaying = document.getElementById('nowplaying')
   var anchors    = document.querySelectorAll('div#files a')
   var cams       = []
   <?php echo implode('', $js_cams); ?>
   var players    = document.querySelectorAll('video')
   var videos = {};
   <?php echo $js_videos; ?>
   
   players[ 0 ].onended = function(){
	console.log('video ended')
	setvideoplaying( 0 );
    }

   // handle nav: next or prev
   var setvideoplaying = function( prev_or_next ){
		if ( !anchors.length ) return;

		console.log('setvideoplaying', prev_or_next)
		
		switch (prev_or_next){
			case 0: // prev
				if ( g_currIdx-1 < 0 ) return
				console.log('setvideoplaying',0);
				
				anchors[ g_currIdx ].style.color = 'initial' // reset active a
				g_currIdx--
				anchors[ g_currIdx ].click()
				break

			case 1: // next
				if ( g_currIdx+1 >= anchors.length ) return
				console.log('setvideoplaying',1);
				
				anchors[ g_currIdx ].style.color = 'initial' // reset active a
				g_currIdx++
				anchors[ g_currIdx ].click() // set new active a
				break;
        }
   }

   // handle playback speed: slower, normal or faster
   var setvideospeed = function( speed, aIdx ){
	  players.forEach((el,idx)=>{
        el.playbackRate = speed // 0.5, 1.0, 2.0
      })

	  // update global var for next video
      g_playbackRate = speed

	  var speeds = document.querySelectorAll('a.speed');
	  speeds.forEach((el,idx)=>{
          el.style.color = idx == aIdx ? 'red' : 'initial';
      })

	  // all vids are set the same speed, we can query element 0
 	  console.log( 'playbackRate:',players[0].playbackRate );
   }

   // handle file being played: pass the index of the anchor in the div#files > a list
   var playfile = function( timestamp, aIdx ){
		anchors[ g_currIdx ].style.color = 'initial' // reset current active anchor
		anchors[ aIdx ].style.color      = 'red' // set new active anchor
		nowplaying.innerText             = timestamp

		console.log(timestamp, aIdx);
		
		players.forEach( (el,idx)=>{
			// what is the directory for this video?
			let path = videos[timestamp][idx]

			try {
				players[ idx ].src = path
				players[ idx ].playbackRate = g_playbackRate

			} catch ( e ){
				console.log( 'File not found:', e )
			}
		});

		g_currIdx = aIdx
   }
  </script>
   <script src="lib/jquery.1.11.1.min.js"></script>
   <script src="lib/bootstrap.3.3.4.min.js"></script>
 </body>
</html>
