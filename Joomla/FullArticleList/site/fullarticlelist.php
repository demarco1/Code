<?php
$db = JFactory::getDbo();
$id = $_GET['cat'];
$query = $db->getQuery(true);
$query->select('*');
$query->from('#__content');
$query->where('catid="'.$id.'"');
$db->setQuery((string)$query);
$res = $db->loadObjectList();
foreach($res as $r){
    echo '<h3>'.$r->title.'</h3>';
    print_r($r);
}
?>
