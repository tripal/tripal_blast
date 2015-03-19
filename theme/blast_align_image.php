<?PHP
/* file: blast_align_image.php
 * 
 * purpose: generate an image of HSPs for a given target
 *          
 *          URL paramters:
 *            acc   - target name
 *            name  - query name, false if none
 *            tsize - target size
 *            qsize - query size
 *            hits  - each hit represented in URL as: 
 *                  targetstart_targetend_hspstart_hspend;
 *            score - score for each hit
 *                  
 * Example: <url>blast_align_image.php?acc=chr2&name=query1&tsize=237068873&qsize=1411&hits=4263001_4262263_1_742;4260037_4259524_895_1411;4260405_4260248_740_897;192158716_192158843_612_742;&scores=722;473;155;51;
 * 
 * history:
 *    09/23/10  Carson  created
 *    04/16/12  eksc    adapted into POPcorn code
 *		03/12/15	deepak	Adapted code into Tripal BLAST
 */
 
  /* include_once('../../inc/lib.php');
   session_start();
   $pc_system = getSystemInfoPC('sequence_search');
  
   $acc    = getCGIParamPC('acc',    'GP', '');
   $scores = getCGIParamPC('scores', 'GP', '');
   $links  = getCGIParamPC('links',  'GP', '');
   $hits   = getCGIParamPC('hits',   'GP', '');
   $tsize  = intval(getCGIParamPC('tsize', 'GP', ''));
   $qsize  = intval(getCGIParamPC('qsize', 'GP', ''));
   $name  = getCGIParamPC('name',    'GP', '');
  */
   // extract hit information from hit param
function generateImage($acc = '', $scores, $hits, $tsize, $qsize, $name) {
   $tok = strtok($hits, ";");
   $b_hits = Array();
   while ($tok !== false) {
      $b_hits[] = $tok;
      $tok = strtok(";");
   }

   // extract score information from score param
   $tokscr = strtok($scores, ";");
   $b_scores = Array();
   while ($tokscr !== false) {
     $b_scores[] = $tokscr;
     $tokscr = strtok(";");
   }

   // image measurements
   $height = 200 + (count($b_hits) * 16);
   $width  = 520;

   $img = imagecreatetruecolor($width, $height);
   
   $white      = imagecolorallocate($img, 255, 255, 255);
   $black      = imagecolorallocate($img, 0, 0, 0);
   $darkgray   = imagecolorallocate($img, 100, 100, 100);
   $strong     = imagecolorallocatealpha($img, 202, 0, 0, 15);
   $moderate   = imagecolorallocatealpha($img, 204, 102, 0, 20);
   $present    = imagecolorallocatealpha($img, 204, 204, 0, 35);
   $weak       = imagecolorallocatealpha($img, 102, 204, 0, 50);
   $gray       = imagecolorallocate($img, 190, 190, 190);
   $lightgray  = imagecolorallocate($img, 230, 230, 230);
   
   imagefill($img, 0, 0, $lightgray);
   
   // Target coordinates
   $maxlength = 300;
   $t_length = ($tsize > $qsize) 
                  ? $maxlength : $maxlength - 50;
   $q_length = ($qsize > $tsize) 
                  ? $maxlength : $maxlength - 50;
                  
   $tnormal = $t_length / $tsize;
   $qnormal = $q_length / $qsize; 
  
   $t_ystart = 30;
   $t_yend   = $t_ystart + 20;
  
   $t_xstart = 50;
   $t_xend   = $t_xstart + $t_length;
   $t_center = $t_xstart + ($t_length / 2);
   
   // Target labels
   $warn = " (not to scale)";
   imagestring($img, 5, $t_xstart, $t_ystart-20, $acc.$warn, $black);
   imagestring($img, 3, 5, $t_ystart+2, "Target", $black);
   
   // Draw bar representing target
   imagefilledrectangle($img, $t_xstart, $t_ystart, $t_xend, $t_yend, $gray);
   imagerectangle($img, $t_xstart, $t_ystart, $t_xend, $t_yend, $darkgray);
   
   // query coordinates
   $q_maxheight = 250;
   $q_ystart = $t_yend + 100;
   $q_yend = $q_ystart + 20;
  
   $q_xstart = $t_center - $q_length / 2;
   $q_xend = $q_xstart + $q_length;

   $q_center = ($q_xend + $q_xstart) / 2;
   $q_xwidth = $q_xend - $q_xstart;

   // Query labels
   imagestring($img, 5, $q_xstart, $q_yend+2, $name.$warn, $black);
   imagestring($img, 3, 5, $q_ystart+2, 'Query', $black);
   
   // Draw bar representing query
   imagefilledrectangle($img, $q_xstart, $q_ystart, $q_xend, $q_yend, $gray);
   imagerectangle($img ,$q_xstart, $q_ystart, $q_xend, $q_yend, $darkgray);
   
   // HSP bars will start here
   $hsp_bary = $q_yend + 20;
   
   // Draw solids for HSP alignments
   for ($ii=count($b_hits)-1; $ii>=0; $ii--) {
      // alignment 
	
   	$cur_hit = $b_hits[$ii];
   	$cur_score = intval($b_scores[$ii]);
   	
   	// set color according to score
   	$cur_color = $darkgray;
   	if ($cur_score > 200) { 
   		$cur_color = $strong;
   	} 
   	else if ($cur_score > 80 && $cur_score <= 200) { 
   		$cur_color = $moderate;
   	} 
   	else if ($cur_score > 50 && $cur_score <= 80) { 
   		$cur_color = $present;
   	} 
   	else if ($cur_score > 40 && $cur_score <= 50) { 
   		$cur_color = $weak;
   	} 
	
	   $t_start = $tnormal *  intval(strtok($cur_hit, "_")) + $t_xstart;
      $t_end = $tnormal *  intval(strtok("_")) + $t_xstart;
      $q_start = $qnormal * intval(strtok("_")) + $q_xstart;
      $q_end = $qnormal *  intval(strtok("_")) + $q_xstart;
		
      $hit1_array = array($t_start, $t_yend, $t_end, $t_yend, $q_end, 
                          $q_ystart, $q_start, $q_ystart);

	   // HSP coords
      imagefilledpolygon($img, $hit1_array, 4, $cur_color);
	
   }//each hit

   // Draw lines over fills for HSP alignments
   for ($ii=0; $ii<count($b_hits); $ii++) {
   	// alignment 
   	
   	$cur_hit = $b_hits[$ii];
   	$t_start = $tnormal *  intval(strtok($cur_hit, "_")) + $t_xstart;
      $t_end = $tnormal *  intval(strtok("_")) + $t_xstart;
      $q_start = $qnormal * intval(strtok("_")) + $q_xstart;
      $q_end = $qnormal *  intval(strtok("_")) + $q_xstart;
   		
   	$hit1_array = array($t_start, $t_yend, $t_end, $t_yend, $q_end, $q_ystart,
   	                    $q_start, $q_ystart,);
   
   	imagerectangle($img, $t_start, $t_ystart, $t_end, $t_yend, $black);
   	imagerectangle($img, $q_start, $q_ystart, $q_end, $q_yend, $black);
   	imagepolygon ($img, $hit1_array, 4, $black);

      // show HSP
      
 		imagestring($img, 3, 2, $hsp_bary, ($acc ."Hit" . $ii), $black);

   	$cur_score = intval($b_scores[$ii]);
   	
   	// set color according to score
   	$cur_color = $darkgray;
   	if ($cur_score > 200) { 
   		$cur_color = $strong;
   	} 
   	else if ($cur_score > 80 && $cur_score <= 200) { 
   		$cur_color = $moderate;
   	} 
   	else if ($cur_score > 50 && $cur_score <= 80) { 
   		$cur_color = $present;
   	} 
   	else if ($cur_score > 40 && $cur_score <= 50) { 
   		$cur_color = $weak;
   	}
   	
   	imagefilledrectangle($img, $q_start, $hsp_bary, $q_end, $hsp_bary+10, $cur_color);
      $hsp_bary += 15;
   }//each hit

   // Draw the key
   
   $xchart = 390;
   $ychart = 10;
   $fontsize = 4;
   $yinc = 20;
   $ywidth = 7;
   $xinc = 10;
   
   imagestring($img, 5, $xchart, $ychart - 5, "Scores", $black);
   
   imagestring($img, $fontsize, $xchart + $yinc + $xinc,$ychart + ($yinc * 1) + $ywidth, ">= 200" , $black);
   imagestring($img, $fontsize, $xchart + $yinc + $xinc,$ychart + ($yinc * 2) + $ywidth, "80 - 200" , $black);
   imagestring($img, $fontsize, $xchart + $yinc + $xinc,$ychart + ($yinc * 3) + $ywidth, "50 - 80" , $black);
   imagestring($img, $fontsize, $xchart + $yinc + $xinc,$ychart + ($yinc * 4) + $ywidth, "40 - 50" , $black);
   imagestring($img, $fontsize, $xchart + $yinc + $xinc,$ychart + ($yinc * 5) + $ywidth, "< 40" , $black);
   
   imagefilledRectangle($img, $xchart, $ychart + ($yinc * 1) + $xinc, $xchart + $yinc, $ychart + ($yinc * 2), $strong);
   imagefilledRectangle($img, $xchart, $ychart + ($yinc * 2) + $xinc, $xchart + $yinc, $ychart + ($yinc * 3), $moderate);
   imagefilledRectangle($img, $xchart, $ychart + ($yinc * 3) + $xinc, $xchart + $yinc, $ychart + ($yinc * 4), $present);
   imagefilledRectangle($img, $xchart, $ychart + ($yinc * 4) + $xinc, $xchart + $yinc, $ychart + ($yinc * 5), $weak);
   imagefilledRectangle($img, $xchart, $ychart + ($yinc * 5) + $xinc, $xchart + $yinc, $ychart + ($yinc * 6), $darkgray);
   
	 return $img;
  // write out images
//    header("Content-type: image/png");
//    imagepng($img);
}
?>
