<?php
$db = JFactory::getDbo();
$id = $_GET['cat'];

// If the id is not numeric, treat it as an alias and get the numeric id fro that
if( !is_numeric( $id ) ) {
	$query = $db->getQuery( true );
	$query->select( 'id' );
	$query->from( '#__categories' );
	$query->where( 'alias="' . $id . '"' );
	$db->setQuery( (string)$query );	
	if(	$res = $db->loadObjectList() ) {
		foreach( $res as $row ) $id = $row->id;
	} else {
		echo "No catgegory found with alias \"$id\"!";
		$id = false;
	}
}

// Render the articles found in the category from it's numeric ID
if( $id ) {
	$query = $db->getQuery( true );
	$query->select( '*' );
	$query->from( '#__content' );
	$query->where( 'catid="' . $id . '"' );
	$db->setQuery( (string)$query );
	$res = $db->loadObjectList();
	echo '<div class="fullissue">';
	foreach( $res as $row ){
		echo '<h3>' . $row->title . '</h3>';
		echo $row->fulltext;
	}
	echo '</div>';
}
?>
