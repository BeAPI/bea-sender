<?php
class Bea_Sender_Admin_Table extends WP_List_Table {
	/**
	 * Method to define all your cols in your table
	 *
	 * @return array() $auth_order
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	private static $auth_order = array( 'id', 'add_date', 'scheduled_from','current_status', 'success', 'failed' );

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	public function __construct( ) {
		parent::__construct( 
			array( 
				'singular' => 'campaign', // singular name of the listed records
				'plural' => 'campaigns', // plural name of the listed records
				'ajax' => false	// does this table support ajax?
			) 
		);

		//Check if user wants delete item in row
		$this->checkDelete( );
	}

	/**
	 * Method when no url redirect founded
	 *
	 * @return string
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	function no_items( ) {
		_e( 'No campaigns found.', 'bea_sender' );
	}
	
	function get_views() {
		$base_url = add_query_arg( array( 'page' => 'bea_sender' ), admin_url( 'tools.php' ) );
		
		$add  = array( 'all' => sprintf( __( '<a href="%s" class="%s" >All</a>', 'bea_sender' ), $base_url, self::current_view( 'all' ) ) );
		foreach( Bea_Sender_Campaign::getAuthStatuses() as $status ) {
			$add[$status] = sprintf( '<a href="%s" class="%s" >%s</a>', add_query_arg( array( 'current_status' => $status ), $base_url ), self::current_view( $status ), Bea_Sender_Client::getStatus( $status ) );
		}
		return $add;
	}
	
	private static function current_view( $slug ) {
		if( isset( $_GET['current_status'] ) && $_GET['current_status'] === $slug ) {
			return 'current';
		} elseif( $slug == 'all' && !isset( $_GET['current_status'] ) ) {
			return 'current';
		}
		return '';
	}

	/**
	 * This method display default column
	 *
	 * @return string $item[ $column_name ]
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case 'id' :
			case 'from_name' :
			case 'from' :
			case 'subject' :
				return $item[$column_name];
			break;
			case 'add_date' :
			case 'scheduled_from' :
				return mysql2date( 'd/m/Y H:m:i' ,$item[ $column_name ] );
			break;
			case 'todo' :
				return self::getCampaignTodo( $item['id'] );
			break;
			case 'current_status' :
				return Bea_Sender_Client::getStatus($item[ $column_name ]);
			break;
			case 'success' :
				return self::getCampaignSend( $item['id'] );
			break;
			case 'failed' :
				return self::getCampaignFailed( $item['id'] );
			break;
			default :
				return print_r( $item, true );
				//Show the whole array for troubleshooting purposes
				break;
		}
	}

	/**
	 * Decide which columns to activate the sorting functionality on
	 * @return array $sortable, the array of columns that can be sorted by the user
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	function get_sortable_columns( ) {
		return array( 
		'id' 				=> array( 'id', false ),
		'current_status' 	=> array( 'current_status', false ),
		'add_date' 			=> array( 'add_date', false ), 
		'scheduled_from' 	=> array( 'scheduled_from', false ),
		);
	}

	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @return array $columns, the array of columns to use with the table
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	function get_columns( ) {
		return array( 'cb' => '<input type="checkbox" />', 'id' => __( 'ID', 'bea_sender' ), 'current_status' => __( 'Status', 'bea_sender' ), 'add_date' => __( 'Date added', 'bea_sender' ), 'scheduled_from' => __( 'Scheduled from', 'bea_sender' ), 'from' => __( 'From', 'bea_sender' ), 'from_name' => __( 'From name', 'bea_sender' ), 'subject' => __( 'Subject', 'bea_sender' ), 'todo' => __( 'Emails to send', 'bea_sender' ),'success' => Bea_Sender_Client::getStatus( 'send' ), 'failed' => Bea_Sender_Client::getStatus( 'failed' ), );
	}

	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @return array() $query
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	function prepareQuery( ) {
		global $wpdb;

		// If no sort, default to title
		$_orderby = (!empty( $_GET[ 'orderby' ] ) && in_array( $_GET[ 'orderby' ], self::$auth_order )) ? $_GET[ 'orderby' ] : 'id';
		// If no order, default to asc
		$_order = (!empty( $_GET[ 'order' ] ) && in_array( $_GET[ "order" ], array( 'asc', 'desc' ) )) ? $_GET[ "order" ] : 'asc';
		$order_by = " ORDER BY $_orderby $_order";

		// Make the order
		$limit = $wpdb->prepare( ' LIMIT %d,%d', ($this->get_pagenum( ) == 1 ? 0 : $this->get_pagenum( )), $this->get_items_per_page( 'bea_s_per_page', BEA_SENDER_PPP ) );
		
		// fitlering by status
		$filter = self::get_status_filter();
		
		return $wpdb->get_results( "
		SELECT 
			c.id, 
			c.current_status, 
			c.add_date, 
			c.scheduled_from, 
			c.from, 
			c.from_name, 
			c.subject
		FROM 
		$wpdb->bea_s_campaigns as c
		JOIN $wpdb->bea_s_re_ca as reca ON c.id = reca.id_campaign
		WHERE 1 = 1
		$filter
		GROUP BY c.id
		$order_by
		$limit", ARRAY_A );
	}

	private static function getCampaignTodo( $c_id ) {
		global $wpdb;
		return self::getCampaignStatusCount( $c_id, 'pending' );
	}
	
	private static function getCampaignFailed( $c_id ) {
		global $wpdb;
		return self::getCampaignStatusCount( $c_id, 'failed' );
	}
	
	private static function getCampaignSend( $c_id ) {
		global $wpdb;
		return self::getCampaignStatusCount( $c_id, 'send' );
	}
	
	private static function getCampaignStatusCount( $c_id, $status ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( "
		SELECT 
			COUNT( reca.id ) as failed
		FROM $wpdb->bea_s_re_ca as reca
		WHERE 1 = 1 
		AND reca.current_status = %s
		AND reca.id_campaign = %d", $status, $c_id ) );
	}

	/**
	 * This method count total items in table
	 *
	 * @return integer Count(id)
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	function totalItems( ) {
		global $wpdb;
		$filter = self::get_status_filter();
		return (int)$wpdb->get_var( "SELECT COUNT( id ) FROM $wpdb->bea_s_campaigns WHERE 1 = 1 $filter" );
	}
	
	private static function get_status_filter() {
		global $wpdb;
		return  !isset( $_GET['current_status'] ) || empty( $_GET['current_status'] ) || !in_array( $_GET['current_status'], Bea_Sender_Campaign::getAuthStatuses() ) ? '' : $wpdb->prepare( ' AND c.current_status = %s', $_GET['current_status'] ) ;
	}

	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 *
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	function prepare_items( ) {
		$this->_column_headers = array( $this->get_columns( ), array( ), $this->get_sortable_columns( ) );

		// Get total items
		$total_items = $this->totalItems( );
		$elements_per_page = $this->get_items_per_page( 'bea_s_per_page', BEA_SENDER_PPP );

		// Set the pagination
		$this->set_pagination_args( array( 'total_items' => $total_items, //WE have to calculate the total number of items
		'per_page' => $elements_per_page, //WE have to determine how many items to show on a page
		'total_pages' => ceil( $total_items / $elements_per_page ) ) );

		$this->items = $this->prepareQuery( );
	}

	/**
	 * Add checkbox input on ID column for delete action
	 *
	 * @return string
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="id[]" value="%s" />', $item[ 'id' ] );
	}

	/**
	 * Get an associative array ( option_name => option_title ) with the list
	 * of bulk actions available on this table.
	 *
	 * @return array() $actions
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	function get_bulk_actions( ) {
		return array( 'delete' => __( 'Delete', 'bea_sender' ) );
	}

	/**
	 * Check if user wants delete item on manage page
	 *
	 * @return string $status and $message
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	function checkDelete( ) {
		if( !isset( $_GET[ 'page' ] ) || $_GET[ 'page' ] != 'bea_sender' || !isset( $_GET[ 'action' ] ) ) {
			return false;
		}

		$action = $this->current_action( );
		if( empty( $action ) || !array_key_exists( $action, $this->get_bulk_actions( ) ) || !isset( $_GET[ 'id' ] ) || empty( $_GET[ 'id' ] ) ) {
			add_settings_error( 'bea_sender', 'settings_updated', __( 'Oups! You probably forgot to tick campaigns to delete?', 'bea_sender' ), 'error' );
			return false;
		}

		check_admin_referer( 'bulk-campaigns' );

		$_GET[ 'id' ] = array_map( 'absint', $_GET[ 'id' ] );

		switch ( $action ) {
			case 'delete' :
				$total = 0;
				foreach( $_GET['id'] as $c_id ) {
					$result = 0;
					$c = new Bea_Sender_Campaign( $c_id );
					if( $c->isData() !== true  ) {
						$message_code = 0;
					} else{
						$result = $c->deleteCampaign();
						$total += $result;
						if( $result == 0 ) {
							$message_code = 1;
						} else {
							$message_code = 2;
						}
					}
				}
				
				wp_redirect( add_query_arg( array( 'page' => 'bea_sender', 'message-code' => $message_code, 'message-value' => $total ), admin_url( 'tools.php' ) ) );
				exit( );
				break;
			default :
				break;
		}

		return true;
	}
}
