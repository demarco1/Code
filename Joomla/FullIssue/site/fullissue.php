<?php
$db = JFactory::getDbo();
$id = $_GET['cat'];

// If the id is not numeric, treat it as an alias and get the numeric id fro that
if( !is_numeric( $id ) ) {
	$query = $db->getQuery( true );
	$query->select( 'id' )->from( '#__categories' )->where( 'alias="' . $id . '"' );
	$db->setQuery( $query );	
	if(	$res = $db->loadObjectList() ) {
		foreach( $res as $row ) $id = $row->id;
	} else {
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
	print_r($row);
	$document = JFactory::getDocument();
	$document->setTitle( $row->title );

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
