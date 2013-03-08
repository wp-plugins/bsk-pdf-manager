<?php
class BSKPDFManagerDashboard {

	var $_bsk_pdf_manager_OBJ = NULL;
	var $_bsk_pdf_manager_OBJ_categories = NULL;
	var $_bsk_pdf_manager_OBJ_category = NULL;
	var $_bsk_pdf_manager_OBJ_pdfs = NULL;
	var $_bsk_pdf_manager_OBJ_pdf = NULL;
	
	var $_obj_init_args = array();

	public function __construct( $checklist_obj ) {
		global $wpdb;
		
		$this->_bsk_pdf_manager_OBJ = $checklist_obj;
		
		$this->_obj_init_args['categories_db_tbl_name'] = $this->_bsk_pdf_manager_OBJ->_bsk_pdf_manager_cats_tbl_name;
		$this->_obj_init_args['pdfs_db_tbl_name'] = $this->_bsk_pdf_manager_OBJ->_bsk_pdf_manager_pdfs_tbl_name;
		$this->_obj_init_args['pdf_upload_path'] = $this->_bsk_pdf_manager_OBJ->_bsk_pdf_manager_upload_path;
		$this->_obj_init_args['pdf_upload_folder'] = $this->_bsk_pdf_manager_OBJ->_bsk_pdf_manager_upload_folder;
		$this->_obj_init_args['management_obj'] = $this;
		
		require_once( 'bsk-pdf-manager-categories.php' );
		require_once( 'bsk-pdf-manager-category.php' );
		require_once( 'bsk-pdf-manager-pdfs.php' );	
		require_once( 'bsk-pdf-manager-pdf.php' );
		
		$this->_bsk_pdf_manager_OBJ_category = new BSKPDFManagerCategory( $this->_obj_init_args );		
		$this->_bsk_pdf_manager_OBJ_pdf = new BSKPDFManagerPDF( $this->_obj_init_args );	
		
		
		add_action("admin_menu", array( $this, 'bsk_pdf_manager_dashboard_menu' ) );	
	}
	
	function bsk_pdf_manager_dashboard_menu() {
	
		if ( !$this->bsk_pdf_manager_current_user_can() ){
			return;
		}
		
		$authorized_level = 'level_10';
		
		add_menu_page('BSK PDF Manager', 'BSK PDF Manager', $authorized_level, 'bsk-pdf-manager');
		add_submenu_page( 'bsk-pdf-manager',
						  'Categories', 
						  'Categories',
						  $authorized_level, 
						  'bsk-pdf-manager', 
						  array($this, 'bsk_pdf_manager_categories') );

		add_submenu_page( 'bsk-pdf-manager', 
						  'PDF Documents', 
						  'PDF Documents', 
						  $authorized_level, 
						  'bsk-pdf-manager-pdfs', 
						  array($this, 'bsk_pdf_manager_pdfs') );						  
	}
	
	function bsk_pdf_manager_categories(){
		global $current_user;
		
		if (!$this->bsk_pdf_manager_current_user_can()){
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}

		$this->_bsk_pdf_manager_OBJ_categories = new BSKPDFManagerCategories( $this->_obj_init_args );

		$categories_curr_view = 'list';
		if(isset($_GET['view']) && $_GET['view']){
			$categories_curr_view = trim($_GET['view']);
		}
		if(isset($_POST['view']) && $_POST['view']){
			$categories_curr_view = trim($_POST['view']);
		}
		
		if ($categories_curr_view == 'list'){
			//Fetch, prepare, sort, and filter our data...
			$this->_bsk_pdf_manager_OBJ_categories->prepare_items();
			
			$category_add_new_page = admin_url( 'admin.php?page=bsk-pdf-manager' );
			$category_add_new_page = add_query_arg( 'view', 'addnew', $category_add_new_page );
	
			echo '<div class="wrap">
					<div id="icon-edit" class="icon32"><br/></div>
					<h2>BSK PDF Categories<a href="'.$category_add_new_page.'" class="add-new-h2">Add New</a></h2>
					<form id="bsk-pdf-manager-categories-form-id" method="post">
						<input type="hidden" name="page" value="bsk-pdf-manager" />';
						$this->_bsk_pdf_manager_OBJ_categories->search_box( 'search', 'bsk-pdf-manager' );
						$this->_bsk_pdf_manager_OBJ_categories->views();
						$this->_bsk_pdf_manager_OBJ_categories->display();
			echo '  </form>
				  </div>';
		}else if ( $categories_curr_view == 'addnew' || $categories_curr_view == 'edit'){
			$category_id = -1;
			if(isset($_GET['categoryid']) && $_GET['categoryid']){
				$category_id = trim($_GET['categoryid']);
			}	
			echo '<div class="wrap">
					<div id="icon-edit" class="icon32"><br/></div>
					<h2>BSK PDF Category</h2>
					<form id="bsk-pdf-manager-category-edit-form-id" method="post">
					<input type="hidden" name="page" value="bsk-pdf-manager" />
					<input type="hidden" name="view" value="list" />';
					$this->_bsk_pdf_manager_OBJ_category->bsk_pdf_manager_category_edit( $category_id );
			echo   '<p style="margin-top:20px;"><input type="button" id="bsk_pdf_manager_category_save" class="button-primary" value="Save" /></p>'."\n";
			echo '</div>';
		}
	}
	
	function bsk_pdf_manager_pdfs(){
		global $current_user;
		
		if (!$this->bsk_pdf_manager_current_user_can()){
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		
		$this->_bsk_pdf_manager_OBJ_pdfs = new BSKPDFManagerPDFs( $this->_obj_init_args );
		
		$lists_curr_view = 'list';
		if(isset($_GET['view']) && $_GET['view']){
			$lists_curr_view = trim($_GET['view']);
		}
		if(isset($_POST['view']) && $_POST['view']){
			$lists_curr_view = trim($_POST['view']);
		}
		
		if ($lists_curr_view == 'list'){
			//Fetch, prepare, sort, and filter our data...
			$this->_bsk_pdf_manager_OBJ_pdfs->prepare_items();
			$add_new_page = admin_url( 'admin.php?page=bsk-pdf-manager-pdfs' );
			$add_new_page = add_query_arg( 'view', 'addnew', $add_new_page );
	
			echo '<div class="wrap">
					<div id="icon-edit" class="icon32"><br/></div>
					<h2>BSK PDF Documents<a href="'.$add_new_page.'" class="add-new-h2">Add New</a></h2>
					<form id="bsk-pdf-manager-pdfs-form-id" method="post">
						<input type="hidden" name="page" value="bsk-pdf-manager-pdfs" />
						<input type="hidden" name="view" value="list" />';
						$this->_bsk_pdf_manager_OBJ_pdfs->search_box( 'search', 'bsk-pdf-manager-pdfs' );
						$this->_bsk_pdf_manager_OBJ_pdfs->views();
						$this->_bsk_pdf_manager_OBJ_pdfs->display();
			echo '  </form>
				  </div>';
		}else if ( $lists_curr_view == 'addnew' || $lists_curr_view == 'edit'){
			$pdf_id = -1;
			if(isset($_GET['pdfid']) && $_GET['pdfid']){
				$pdf_id = trim($_GET['pdfid']);
			}	
			echo '<div class="wrap">
					<div id="icon-edit" class="icon32"><br/></div>
					<h2>BSK PDF Document</h2>
					<form id="bsk-pdf-manager-pdfs-form-id" method="post" enctype="multipart/form-data">
					<input type="hidden" name="page" value="bsk-pdf-manager-pdfs" />
					<input type="hidden" name="view" value="list" />';
					$this->_bsk_pdf_manager_OBJ_pdf->pdf_edit( $pdf_id );
			echo '  <p style="margin-top:20px;"><input type="button" id="bsk_pdf_manager_pdf_save_form" class="button-primary" value="Save" /></p>'."\n";
			echo '	</form>
				  </div>';
		}
	}
	
	function bsk_pdf_manager_current_user_can(){
		global $current_user;
		
		if ( current_user_can('level_10') ){
			return true;
		}else{
			/*
			//get role;
			$user_roles = $current_user->roles;
			$user_role = array_shift($user_roles);
			
			if ($user_role == 'spcial role'){
				return true;
			}
			*/
		}
		return false;
	}
	
}
