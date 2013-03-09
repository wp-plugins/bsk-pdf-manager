<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class BSKPDFManagerCategories extends WP_List_Table {
   
	var $_categories_db_tbl_name = '';
	var $_pdfs_db_tbl_name = '';
	var $_pdfs_upload_path = '';
	var $_pdfs_upload_folder = '';
	var $_bsk_pdf_manager_managment_obj = NULL;
   
   
    function __construct( $args = array() ) {
        
        //Set parent defaults
        parent::__construct( array( 
            'singular' => 'bsk-pdf-manager-categories',  //singular name of the listed records
            'plural'   => 'bsk-pdf-manager-categories', //plural name of the listed records
            'ajax'     => false                          //does this table support ajax?
        ) );
       
	   $this->_categories_db_tbl_name = $args['categories_db_tbl_name'];
	   $this->_pdfs_db_tbl_name = $args['pdfs_db_tbl_name'];
	   $this->_pdfs_upload_path = $args['pdf_upload_path'];
	   $this->_pdfs_upload_folder = $args['pdf_upload_folder'];
	   $this->_bsk_pdf_manager_managment_obj = $args['management_obj'];
	   
	   $this->_pdfs_upload_path = $this->_pdfs_upload_path.$this->_pdfs_upload_folder;
	   
    }

    function column_default( $item, $column_name ) {
        switch( $column_name ) {
			case 'id':
				echo $item['id'];
				break;
			case 'cat_title':
				echo $item['cat_title'];
				break;
            case 'last_date':
                echo $item['last_date'];
                break;
        }
    }
   
    function column_cb( $item ) {
        return sprintf( 
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            esc_attr( $this->_args['singular'] ),
            esc_attr( $item['id'] )
        );
    }

    function get_columns() {
    
        $columns = array( 
			'cb'        		=> '<input type="checkbox"/>',
			'id'				=> 'ID',
            'cat_title'     	=> 'Title',
            'last_date' 		=> 'Date'
        );
        
        return $columns;
    }
   
    function get_views() {
		//$views = array('filter' => '<select name="a"><option value="1">1</option></select>');
		
        return $views;
    }
   
    function get_bulk_actions() {
    
        $actions = array( 
            'delete'=> 'Delete'
        );
        
        return $actions;
    }

    function process_bulk_action() {
		global $wpdb;
		
		$categories_id = isset( $_POST['bsk-pdf-manager-categories'] ) ? $_POST['bsk-pdf-manager-categories'] : false;
		if ( !$categories_id || !is_array( $categories_id ) || count( $categories_id ) < 1 ){
			return;
		}
		$ids = implode(',', $categories_id);
		$ids = trim($ids);
		$sql = 'DELETE FROM `'.$this->_categories_db_tbl_name.'` WHERE id IN('.$ids.')';
		$wpdb->query( $sql );

		//when one category deleted all lists under it will be removed also
		$sql = 'SELECT * FROM `'.$this->_pdfs_db_tbl_name.'` WHERE cat_id IN('.$ids.')';
		$pdfs = $wpdb->get_results( $sql );
		if ($pdfs && count($pdfs) > 0){
			foreach($pdfs as $pdf){
				if ( $pdf->file_name && file_exists($this->_pdfs_upload_path.$pdf->file_name) ){
					unlink($this->_pdfs_upload_path.$pdf->file_name);
				}
			}
		}
		
		$sql = 'DELETE FROM `'.$this->_pdfs_db_tbl_name.'` WHERE cat_id IN('.$ids.')';
		$wpdb->query( $sql );
    }

    function categories_data() {
		global $wpdb;
		
        // check to see if we are searching
        if( isset( $_POST['s'] ) ) {
            $search = trim( $_POST['s'] );
        }
		
		$sql = 'SELECT * FROM '.
		       $this->_categories_db_tbl_name.' AS c';

		$whereCase = $search ? ' c.cat_title LIKE "%'.$search.'%"' : '';
		$orderCase = ' ORDER BY c.cat_title ASC';
		$whereCase = $whereCase ? ' WHERE '.$whereCase : '';
		
		$catgories = $wpdb->get_results($sql.$whereCase.$orderCase);
		
		if (!$catgories || count($catgories) < 1){
			return NULL;
		}
		$base = admin_url( 'admin.php?page=bsk-pdf-manager' );
		
		$categories_data = array();
		foreach ( $catgories as $category ) {
			$category_edit_page = add_query_arg( array('view' => 'edit', 
													   'categoryid' => $category->id),
												 $base );
			$categories_data[] = array( 
				'id' 				=> $category->id,
				'cat_title'     	=> '<a href="'.$category_edit_page.'">'.$category->cat_title.'</a>',
				'last_date'			=> $category->last_date,
			);
		}
		
		return $categories_data;
    }

    function prepare_items() {
       
        /**
         * First, lets decide how many records per page to show
         */
        $per_page = 20;
        $data = array();
		
        add_thickbox();

        $columns = $this->get_columns();
        $hidden = array(); // no hidden columns
       
        $this->_column_headers = array( $columns, $hidden );
       
        $this->process_bulk_action();
       
        $data = $this->categories_data();
   
        $current_page = $this->get_pagenum();
    
        $total_items = count( $data );
       
	    if ($total_items > 0){
        	$data = array_slice( $data,( ( $current_page-1 )*$per_page ),$per_page );
		}
       
        $this->items = $data;

        $this->set_pagination_args( array( 
            'total_items' => $total_items,                  // We have to calculate the total number of items
            'per_page'    => $per_page,                     // We have to determine how many items to show on a page
            'total_pages' => ceil( $total_items/$per_page ) // We have to calculate the total number of pages
        ) );
        
    }
   
}