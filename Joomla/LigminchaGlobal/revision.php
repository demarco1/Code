<?php
/**
 * Global Revision (temporary objects that are local-only and represent changes to main objects queued for routing)
 */
class LigminchaGlobalRevision extends LigminchaGlobalObject {

	function __construct( $obj) {

		// This goes first so that parent constructor will raise an error if the current uuid type doesn't match
		$this->type = LG_REVISION;
		$this->flag( LG_LOCAL, true );

		// This will load the whole object if the UUID exists
		parent::__construct( $id );

		// Make a server uuid from the current server if none supplied (this replaces the random one made by the parent constructor)
		if( $id === false ) {

			// Make a new uuid from the server's secret
			$config = JFactory::getConfig();
			$secret = $config->get( 'secret' );
			$this->obj_id = $this->hash( $secret );

			// Try and load the object data now that we know its uuid
			if( !$this->load() ) {

				// TODO: Doesn't exist, make the data structure for our new server object

				// Save our new instance to the DB
				$this->update();
			}
		}

	}
}
