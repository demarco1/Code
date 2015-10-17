<?php
ini_set( 'error_reporting', E_ALL );
ini_set( 'display_errors', true );

// This is a standalone script to give the JS app a database-connection in the back end
define( 'LG_STANDALONE', true );

// Then load all the distributed classes
$common = dirname( __DIR__ ) . '/Joomla/LigminchaGlobal/common';
require_once( "$common/sso.php" );
require_once( "$common/standalone.php" );
require_once( "$common/distributed.php" );
require_once( "$common/object.php" );
require_once( "$common/sync.php" );
require_once( "$common/server.php" );
require_once( "$common/user.php" );
require_once( "$common/session.php" );
require_once( "$common/log.php" );

// SSO: Check if this session has an SSO cookie and make the current session and user from it if so
//LigminchaGlobalSSO::makeSessionFromCookie();

// Instantiate the distributed class
//new LigminchaGlobalDistributed();

// Send accumulated revisions
//LigminchaGlobalDistributed::sendQueue();

// Todo: only return this page if there are no parameters

?><!DOCTYPE html>
<html lang="en">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<head>
		<title>LigminchaGlobalApp</title>
		<link rel="stylesheet" href="styles/main.css" />
	</head>
	<body>
		<!-- App HTML structure -->
		<section id="todoapp">
			<header id="header">
				<h1>Todos</h1>
				<input id="new-todo" placeholder="What needs to be done?" autofocus>
			</header>
		<section id="main">
			<ul id="todo-list"></ul>
			</section>
		</section>

		<!-- Templates -->
		<script type="text/template" id="item-template">
			<div class="view">
				<input class="toggle" type="checkbox" <%= completed ? 'checked' : '' %>>
				<label><%- title %></label>
				<input class="edit" value="<%- title %>">
				<button class="destroy">remove</button>
			</div>
		</script>  

		<!-- Scripts -->
		<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?sensor=false"></script>
		<script type="text/javascript" src="resources/jquery.js"></script>
		<script type="text/javascript" src="resources/underscore.js"></script>
		<script type="text/javascript" src="resources/backbone.js"></script>
		<script type="text/javascript" src="resources/websocket.js"><!-- WebSocket object from the MW:WebSockets extension --></script>
		<script type="text/javascript" src="main.js"><!-- Main app code --></script>
	</body>
</html>
