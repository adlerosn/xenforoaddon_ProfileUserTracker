<?php

if(!isset($ProfileUserTrackercallbackloaded)){
	$ProfileUserTrackercallbackloaded=False;
}

class ProfileUserTracker_callback
{
	public static function load_class($class, array &$extend)
	{
		global $ProfileUserTrackercallbackloaded;
		if(!$ProfileUserTrackercallbackloaded){
			$ProfileUserTrackercallbackloaded=True;
			$extend[] = 'ProfileUserTracker_agent';
		}
	}
}
