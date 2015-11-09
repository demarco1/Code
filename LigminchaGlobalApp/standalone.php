<?php
ini_set( 'error_reporting', E_ALL );
ini_set( 'display_errors', true );

// This tells the system that we're running the database without the Joomla framework present
define( 'LG_STANDALONE', true );

// Load the Fake Joomla environment and all the common classes from the Joomla extension
// - changes coming in from the app are saved directly into the distributed db table
// - changes destined to the app are sent from the Joomla via the WebSocket daemon not from here
// - although we can send the initial servers, users and sessions from here
$common = dirname( __DIR__ ) . '/Joomla/LigminchaGlobal/common';
require_once( "$common/distributed.php" );
require_once( "$common/object.php" );
require_once( "$common/sync.php" );
require_once( "$common/server.php" );
require_once( "$common/user.php" );
require_once( "$common/session.php" );
require_once( "$common/version.php" );
require_once( "$common/log.php" );
require_once( "$common/sso.php" );

// Instantiate the main classes
// - note that if there is any incoming sync data, this will process it (and reroute if necessary) and exit
new LigminchaGlobalSSO();
new LigminchaGlobalDistributed();

// Make SSO session ID and some other config available to client-side
$session = LigminchaGlobalSession::getCurrent() ? LigminchaGlobalSession::getCurrent()->id : 0;
$wgOut->addJsConfigVars( 'session', $session );
$wgOut->addJsConfigVars( 'wsPort', 1729 );
$wgOut->addJsConfigVars( 'wsRewrite', true );

// We can pre-load some templates here
$wgOut->addJsConfigVars( 'templates', array(
	'global-toolbar' => file_get_contents( __DIR__ . '/templates/global-toolbar.html' )
) );
