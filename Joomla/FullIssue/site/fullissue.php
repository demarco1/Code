<?php
$db = JFactory::getDbo();
$id = array_key_exists( 'cat', $_GET ) ? $_GET['cat'] : false;

// If the id is not numeric, treat it as an alias and get the numeric id fro that
if( $id && !is_numeric( $id ) ) {
	$query = $db->getQuery( true );
	$query->select( 'id' )->from( '#__categories' )->where( 'alias="' . $id . '"' );
	$db->setQuery( $query );	
	if(	$row = $db->loadObject() ) $id = $row->id;
	else {
		echo "No catgegory found with alias \"$id\"!";
		$id = false;
	}
}

if( $id ) {

	// Get category info and set page title
	$query = $db->getQuery( true );
	$query->select( '*' )->from( '#__categories' )->where( "id=$id" );
	$db->setQuery( $query );
	$row = $db->loadObject();
	$cat = $row->title;
	$document = JFactory::getDocument();
	$document->setTitle( "$cat (full issue)" );

	// Crumbs
	$app = JFactory::getApplication();
	$pathway = $app->getPathway();
	$path = $pathway->getPathway();
	$item1 = new stdClass();
	$item1->name = 'Fullissue';
	$path[] = $item1;
	$item2 = new stdClass();
	$item2->name = $cat;
	$path[] = $item2;
	$pathway->setPathway( $path );

	// Render the articles found in the category from it's numeric ID
	$query = $db->getQuery( true );
	$query->select( '*' )->from( '#__content' )->where( 'catid="' . $id . '"' )->order( 'ordering' );
	$db->setQuery( $query );
	$res = $db->loadObjectList();
	echo '<div class="fullissue">';
	foreach( $res as $row ){
		echo '<h3>' . $row->title . '</h3>';
		echo $row->fulltext;
	}
	echo '</div>';
}
?>
