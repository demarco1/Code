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
		global $lgScript;
		$value = is_array( $value ) ? str_replace( '\\', '\\\\', json_encode( $value ) ) : addslashes( $value );
		$lgScript .= "window.mw.data.$name='$value';\n";
	}

	// For wfMessage()
	function text() {
		return $this->msgKey;
	}

}
function wfMessage( $msgkey ) {
	return new MediaWiki( $msgKey );
}
global $wgExtensionCredits, $wgExtensionMessagesFiles, $wgOut, $wgResourceModules, $wgExtensionAssetsPath, $lgScript, $wgDBname, $wgDBprefix;
$lgScript = '';
$wgExtensionCredits = array( 'other' => array() );
$wgExtensionMessagesFiles = array();
$wgOut = new MediaWiki();
$wgOut->addJsConfigVars( 'wgServer', 'http://' . $_SERVER['HTTP_HOST'] );
$wgResourceModules = array();
$wgExtensionAssetsPath = '';

// These are just used to form the WebSocket message prefix filter
if( !isset( $wgDBname ) ) {
	$wgDBname = 'Ligmincha';
	$wgDBprefix = 'Global';
}
