<?php
/**
 * Settings for Ligmincha Brasil volunteers wiki
 */
$wgCookieDomain         = ".ligmincha.com.br";
$wgLogo                 = "/wiki/skins/Ligmincha/images/logo-ligmincha-wiki.png";
$wgRawHtml              = true;
$wgDefaultSkin          = 'monobook';
$wgLanguageCode         = 'pt-br';

// Bounce clear to https
if( !array_key_exists( 'HTTPS', $_SERVER ) || $_SERVER['HTTPS'] != 'on' ) {
	header( "Location: https://wiki.ligmincha.com.br" . $_SERVER['REQUEST_URI'] );
	exit;
}

// Bounce naked domain to account request page
if( $_SERVER['REQUEST_URI'] == '/' ) {
	header( "Location: https://wiki.ligmincha.com.br/Especial:Pedir_conta" );
	exit;
}

// Make red-link edits use Visual Editor
if( array_key_exists( 'redlink', $_GET ) && array_key_exists( 'action', $_GET ) && $_GET['action'] == 'edit' ) {
	$url = str_replace( 'action', 'veaction', $_SERVER['REQUEST_URI'] );
	header( "Location: https://wiki.ligmincha.com.br$url" );
	exit;
}

// Permissions
$wgGroupPermissions['*']['edit']                   = false;
$wgGroupPermissions['*']['read']                   = $_SERVER['REMOTE_ADDR'] == '68.168.101.71';
$wgGroupPermissions['*']['upload']                 = false;
$wgGroupPermissions['user']['upload']              = true;
$wgGroupPermissions['user']['upload_by_url']       = true;
$wgGroupPermissions['*']['createpage']             = false;
$wgGroupPermissions['user']['edit']                = true;
$wgGroupPermissions['user']['createpage']          = true;
$wgGroupPermissions['sysop']['createpage']         = true;
$wgGroupPermissions['joomla']['edit']              = true;

// User merge extension
include( "$IP/extensions/UserMerge/UserMerge.php" );
$wgGroupPermissions['bureaucrat']['usermerge'] = true;

// Confirm Account extension
include( "$IP/extensions/ConfirmAccount/ConfirmAccount.php" );
$wgWhitelistRead[] = 'Especial:Pedir conta';
$wgWhitelistRead[] = 'Special:UserLogout';
$wgConfirmAccountRequestFormItems = array(
	'UserName'        => array( 'enabled' => true ),
	'RealName'        => array( 'enabled' => true ),
	'Biography'       => array( 'enabled' => true, 'minWords' => 2 ),
	'AreasOfInterest' => array( 'enabled' => false ),
	'CV'              => array( 'enabled' => false ),
	'Notes'           => array( 'enabled' => false ),
	'Links'           => array( 'enabled' => false ),
	'TermsOfService'  => array( 'enabled' => false ),
);
$wgConfirmAccountContact = 'aran@organicdesign.co.nz';

// Wiki editor extension
wfLoadExtensions( 'WikiEditor', 'VisualEditor' );
$wgDefaultUserOptions['usebetatoolbar']            = 1;
$wgDefaultUserOptions['usebetatoolbar-cgd']        = 1;
$wgDefaultUserOptions['wikieditor-preview']        = 1;
$wgDefaultUserOptions['watchdefault']              = false;
$wgDefaultUserOptions['visualeditor-enable'] = 1; // enabled by default for all
$wgHiddenPrefs[] = 'visualeditor-enable'; // don't allow disabling
$wgVisualEditorNamespaces = array( NS_MAIN, NS_USER, NS_CATEGORY, NS_ADMIN );
$wgVirtualRestConfig['modules']['parsoid'] = array(
	'url' => 'http://localhost:8142',
	'domain' => 'ligmincha',
	'prefix' => 'ligmincha',
);

// Organic Design extensions
wfLoadExtension( 'ExtraMagic' );
wfLoadExtension( 'HighlightJS' );
wfLoadExtension( 'AjaxComments' );
$wgAjaxCommentsPollServer = -1;

include( "$IP/extensions/WebSocket/WebSocket.php" );
WebSocket::$log = __DIR__ . '/ws.log';
WebSocket::$rewrite = true;
WebSocket::$ssl_cert = '/etc/letsencrypt/live/ligmincha.com.br/fullchain.pem';
WebSocket::$ssl_key = '/etc/letsencrypt/live/ligmincha.com.br/privkey.pem';

// Make Category:Público public access
$wgHooks['UserGetRights'][] = 'wfPublicCat';
function wfPublicCat() {
	global $wgWhitelistRead;
	$title = Title::newFromText( $_REQUEST['title'] );
	if( is_object( $title ) ) {
		$id = $title->getArticleID();
		$dbr = wfGetDB( DB_SLAVE );
		if( $dbr->selectRow( 'categorylinks', '1', "cl_from = $id AND cl_to = 'Público'" ) ) {
			$wgWhitelistRead[] = $title->getPrefixedText();
		}
	}
	return true;
}

// Force users to use old changes format
$wgExtensionFunctions[] = 'wfOldChanges';
function wfOldChanges() {
	global $wgUser;
	$wgUser->setOption( 'usenewrc', false );
}

// Always give users a token cookie
$wgExtensionFunctions[] = 'wfTokenAlways';
function wfTokenAlways() {
	global $wgUser, $wgRequest;
	if( $wgUser->isLoggedIn() && !$wgRequest->getCookie( 'Token' ) ) {
		$token = $wgUser->getToken( true );
		WebResponse::setcookie( 'Token', $token );
	}
}

// Clear out the cookies from the old domain so that there's not login trouble
// (cookie domain was changed to top-level domain so that joomla can access them for SSO)
$wgExtensionFunctions[] = 'wfClearOldCookies';
function wfClearOldCookies() {
	global $wgUser, $wgCookieDomain;
	$domain = $wgCookieDomain;
	$wgCookieDomain = '';
	foreach( array( 'UserID', 'UserName', 'Token', 'LoggedOut', '_session' ) as $k ) {
		$wgUser->clearCookie( $k );
	}
	$wgCookieDomain = $domain;
}

// Set up a private sysop-only Admin namespace
define( 'NS_ADMIN', 1020 );
$wgExtraNamespaces[NS_ADMIN]     = 'Admin';
$wgExtraNamespaces[NS_ADMIN + 1] = 'Admin_talk';
Hooks::register( 'ParserFirstCallInit', 'wfProtectAdminNamespace' );
function wfProtectAdminNamespace( Parser $parser ) {
	global $wgTitle, $wgUser, $wgOut, $mediaWiki;
	if( is_object( $wgTitle) && $wgTitle->getNamespace() == NS_ADMIN && !in_array( 'bureaucrat', $wgUser->getEffectiveGroups() ) ) {
		if( is_object( $mediaWiki ) ) $mediaWiki->restInPeace();
		$wgOut->disable();
		wfResetOutputBuffers();
		header( "Location: https://wiki.ligmincha.com.br/Página_principal" );
	}
	return true;
}

// Set "remember me" on by default
Hooks::register( 'UserLoginForm', 'wfRememberMe' );
function wfRememberMe( &$template ) {
	$template->data['remember'] = true;
	return true;
}

