<?php
/**
 * This is a fake MediaWiki environment so that the WebSocket classes can function stand-alone
 */
define( 'MEDIAWIKI', true );

class MediaWiki {

	var $msgKey;

	function __construct( $msgKey = false ) {
		$this->msgKey = $msgKey;
	}

	function addModules( $ext ) {
	}

	function addJsConfigVars( $name, $value ) {
		global $script;
		$value = str_replace( '\\', '\\\\', json_encode( $value ) );
		print_r($value);
		$script .= "\nwindow.mw.data.$name='$value';";
	}

	// For wfMessage()
	function text() {
		return $this->msgKey;
	}

}
function wfMessage( $msgkey ) {
	return new MediaWiki( $msgKey );
}
global $wgExtensionCredits, $wgExtensionMessagesFiles, $wgOut, $wgResourceModules, $wgExtensionAssetsPath;
$wgExtensionCredits = array( 'other' => array() );
$wgExtensionMessagesFiles = array();
$wgOut = new MediaWiki();
$wgOut->addJsConfigVars( 'wgServer', 'http://' . $_SERVER['HTTP_HOST'] );
$wgResourceModules = array();
$wgExtensionAssetsPath = '';

// These are just used to form the message prefix filter
$wgDBname = 'Ligmincha';
$wgDBprefix = 'Global';
