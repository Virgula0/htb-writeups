<?php
/*
// "AJAX Vote" Plugin for Joomla! 1.0.x - Version 1.1
// License: http://www.gnu.org/copyleft/gpl.html
// Authors: George Chouliaras - Fotis Evangelou - Luca Scarpa
// Copyright (c) 2006 - 2007 JoomlaWorks.gr - http://www.joomlaworks.gr
// Project page at http://www.joomlaworks.gr - Demos at http://demo.joomlaworks.gr
// ***Last update: October 25th, 2007***
// modified for jdownloads by Arno Betz - www.jdownloads.com - 2022-12-18 for Joomla 4
*/

// Set flag that this is a parent file
define( '_JEXEC', 1 );


    /* Initialize Joomla framework */
    define('JPATH', dirname(__FILE__) );
    define( 'DS', DIRECTORY_SEPARATOR );

    $parts = explode( DS, JPATH );  
    $j_root =  implode( DS, $parts ) ;

    $x = array_search ( 'components', $parts  );
    $path = '';

    for($i=0; $i < $x; $i++){
        $path = $path.$parts[$i].DS; 
    }
    
    // remove last DS
    $path = substr($path, 0, -1);
    
    if (!defined('JPATH_BASE')){
        define('JPATH_BASE', $path );
    }    
    
    // Run the application
    // Required Files 
    require_once JPATH_BASE . '/includes/defines.php';
    require_once JPATH_BASE . '/includes/framework.php';

    // Boot the DI container
    $container = \Joomla\CMS\Factory::getContainer();

    /*
     * Alias the session service keys to the web session service as that is the primary session backend for this application
     *
     * In addition to aliasing "common" service keys, we also create aliases for the PHP classes to ensure autowiring objects
     * is supported.  This includes aliases for aliased class names, and the keys for aliased class names should be considered
     * deprecated to be removed when the class name alias is removed as well.
     */
    $container->alias('session.web', 'session.web.site')
        ->alias('session', 'session.web.site')
        ->alias('JSession', 'session.web.site')
        ->alias(\Joomla\CMS\Session\Session::class, 'session.web.site')
        ->alias(\Joomla\Session\Session::class, 'session.web.site')
        ->alias(\Joomla\Session\SessionInterface::class, 'session.web.site');

    // Instantiate the application.
    $app = $container->get(\Joomla\CMS\Application\SiteApplication::class);

    // Set the application as global app
    \Joomla\CMS\Factory::$application = $app;
    
    use Joomla\CMS\Factory;
    use Joomla\CMS\MVC\Model\BaseDatabaseModel;
    use Joomla\CMS\Table\Table;

    switch ($_GET['task']){
	    case 'vote':
            recordVote();
            break;
	    case 'show':
            showVotes();
            break;
    }

    function recordVote() {
        
        $database = Factory::getDBO();
	    
	    $user_rating 	= intval( $_GET['user_rating'] );
	    $cid 			= intval( $_GET['cid'] );
	    
	    if (($user_rating >= 1) and ($user_rating <= 5)) {
            if(!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $currip = $_SERVER['HTTP_CLIENT_IP']; // share internet
            } elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $currip = $_SERVER['HTTP_X_FORWARDED_FOR']; // pass from proxy
            } else {
                $currip = $_SERVER['REMOTE_ADDR'];
            }
        
		    $query = "SELECT *"
		    . "\n FROM #__jdownloads_ratings"
		    . "\n WHERE file_id = " . (int) $cid
		    ;
		    $database->setQuery( $query );
		    $votesdb = NULL;
            $votesdb = $database->loadObject();
		    if (!$votesdb){
			    $query = "INSERT INTO #__jdownloads_ratings ( file_id, lastip, rating_sum, rating_count )"
			    . "\n VALUES ( " . (int) $cid . ", " . $database->Quote( $currip ) . ", " . (int) $user_rating . ", 1 )";
			    $database->setQuery( $query );
			    $database->execute() or die( $database->stderr() );
		    } else {
			    if ($currip != ($votesdb->lastip)) {
				    $query = "UPDATE #__jdownloads_ratings"
				    . "\n SET rating_count = rating_count + 1, rating_sum = rating_sum + " . (int) $user_rating . ", lastip = " . $database->Quote( $currip )
				    . "\n WHERE file_id = " . (int) $cid
				    ;
				    $database->setQuery( $query );
				    $database->execute() or die( $database->stderr() );
			    } else {
				    echo 0;
				    exit();
			    }
		    }
		    echo 1;
	    }
    }

    function getPercentage (){
    
        $database = Factory::getDBO();
	    $result = 0;
	    
	    $id = intval( $_GET['cid'] );
	    
	    $database->setQuery('SELECT * FROM #__jdownloads_ratings WHERE file_id='. (int) $id);
	    $database->loadObject($vote);
	    
	    if($vote->rating_count != 0){
		    $result = number_format(intval($vote->rating_sum) / intval( $vote->rating_count ), 2) *100;
	    }
	
	    echo $result;	
    }
?>