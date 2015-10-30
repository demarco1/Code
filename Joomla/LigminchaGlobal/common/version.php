<?php
/**
 * Global Version item
 */
class LigminchaGlobalVersion extends LigminchaGlobalObject {

	function __construct( $version = false ) {
		$this->type = LG_VERSION;
		parent::__construct();
		if( $version ) {
			$this->tag = $version;
			$this->update();
		}
	}

	/**
	 * Make a new object given an id
	 */
	public static function newFromId( $id, $type = false ) {
		return parent::newFromId( $id, LG_VERSION );
	}

	/**
	 * Add this type to $cond
	 */
	public static function select( $cond = array() ) {
		$cond['type'] = LG_VERSION;
		return parent::select( $cond );
	}

	public static function selectOne( $cond = array() ) {
		$cond['type'] = LG_VERSION;
		return parent::selectOne( $cond );
	}
}
