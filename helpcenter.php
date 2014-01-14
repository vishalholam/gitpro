<?php
class HelpCenter extends CI_Controller {
	function __construct(){
		parent::__construct();
		$this->load->helper(array('form', 'url', 'securl'));
		$this->load->model('Faq_Model','F_Model');
		$this->load->model('Help_Model','H_Model');
		$this->load->library('message');
		$this->load->library('phpsession');
	}
	
	function page($faq_category_id=8)
	{
			$faq_category_ids = explode('-',$faq_category_id); 
			$data['faq_categories']=$this->F_Model->getFAQcategoriesByStatus('Active');
			$data['active']=$faq_category_ids[0];
			$data['faqdata'] = $this->F_Model->getFAQByCategoryId($faq_category_ids[0]);
			$data['helpdata'] = $this->H_Model->getHELPByCategoryId($faq_category_ids[0]);
			$data['menutab'] = "faq";
			$this->load->view($this->config->item('base_template_dir').'/user/cms/faq',$data);
	}
	
	
   function categories(){
		if(!$this->phpsession->get('ciAdmId')){ redirect('r/k/admin'); }
		$data = array('faqcategories' => '', 'PAGING' => '' , 'search' => '-');
		$this->load->helper(array('pagination'));
		$array = $this->uri->uri_to_assoc(3);
		$pages = (@$array['pages']?$array['pages']:1);
		$page = (@$array['page']?$array['page']:0);
		$data['search'] = (@$array['search']?$array['search']:'-');
		if(strlen(trim($this->input->post('submit'))) > 0){
			$data = array();
			$faq_category_id = @implode(",",$this->input->post('checbox_ids'));
			$action =  $this->input->post('action');
			$message = $this->F_Model->faqCategoryOperations(array('faq_category_id' => $faq_category_id),$action);
			$this->message->setMessage($message,"SUCCESS");
		}
		$PAGE = $page;
		$PAGE_LIMIT = $this->F_Model->countFaqCategoryRecords(); //$data['search']; //25;
		$DISPLAY_PAGES = 25;
		$PAGE_LIMIT_VALUE = ($PAGE - 1) * $PAGE_LIMIT;
		// Get posted search value in variables
		$data['search'] = (@$this->input->post('search')?trim(strtolower(@$this->input->post('search'))):@$data['search']);
		// Count total cms records
		$total = $this->F_Model->countFaqCategoryRecords();
		$PAGE_TOTAL_ROWS = $total;
		$PAGE_URL = $this->config->item('base_url').'helpcenter/categories/search/';//.$data['search'];
		$data['PAGING'] = pagination_assoc($PAGE_TOTAL_ROWS,$PAGE_LIMIT,$DISPLAY_PAGES,$PAGE_URL,$page,$pages);
		//	Pagination end

		// Get all cms records
		$data['faqcategories'] = $this->F_Model->getFaqCategoryRecords($PAGE,$PAGE_LIMIT);
     
       // set variable to show active menu 
      $data['menutab'] = 'master';
      $data['menuitem'] = 'faqcategories';
		$this->load->view($this->config->item('base_template_dir').'/admin/faq/categories', $data);
	}//end categories
	#----------------------------------------
	# admin site FAQ Category add modify
	#----------------------------------------
	public function faqcategoryaddmodify($edit_id=0){
		if(!$this->phpsession->get('ciAdmId')){	redirect('r/k/admin');	}

		if(strlen(trim($this->input->post('Submit'))) > 0){
			if($edit_id > 0){
				//	update record
				$data = array(
									'faq_name'	=>	$this->input->post('faq_name'),
									'faq_status' =>	$this->input->post('faq_status')
				);
				$this->F_Model->faqCategoryOperations($data,'update',$edit_id);
				
				$this->message->setMessage("FAQ Category updated successfully","SUCCESS");
				redirect('/helpcenter/categories/');
			}else{
				//	insert record
				$data = array(
									'faq_name'	=>	$this->input->post('faq_name'),
									'faq_status'			=>	$this->input->post('faq_status')
				);
				$inserted_id = $this->F_Model->faqCategoryOperations($data,'addnew');
				if($inserted_id > 0){
					
					$this->message->setMessage("FAQ Category added successfully","SUCCESS");
					redirect('/helpcenter/categories/');
				}
			}
		}
		$data = array(
						'edit_id'		=> '',
						'faq_name' => '',
						'faq_status' => ''
		);
		if($edit_id > 0){
			$faqcategory = $this->F_Model->getFAQCategoryById($edit_id);
			$data = array(
							'edit_id'		=> $edit_id,
							'faq_name'		=>	$faqcategory->faq_name,
							'faq_status'	=>	$faqcategory->faq_status
			);
		}
		
		 // set variable to show active menu 
      $data['menutab'] = 'master';
      $data['menuitem'] = 'faqcategories';
		$this->load->view($this->config->item('base_template_dir').'/admin/faq/add_modify_categories',$data);
	}//end faqcategoryaddmodify
	#----------------------------------------
	#	Display FAQ at admin site
	#----------------------------------------

	function admin(){
		if(!$this->phpsession->get('ciAdmId')){ redirect('r/k/admin'); }
		$data = array('faqs' => '', 'PAGING' => '' , 'search' => '-');
		$this->load->helper(array('pagination'));
		$array = $this->uri->uri_to_assoc(3);
		$pages = (@$array['pages']?$array['pages']:1);
		$page = (@$array['page']?$array['page']:1);
		$data['search'] = (@$array['search']?$array['search']:'-');
		if(strlen(trim($this->input->post('submit'))) > 0){
			$faq_id = @implode(",",$this->input->post('checbox_ids'));
			$action =  $this->input->post('action');
			$message = $this->F_Model->faqOperations(array('faq_id' => $faq_id),$action);
			
			$this->message->setMessage($message,"SUCCESS");
		}
		$PAGE = $page;
		$PAGE_LIMIT = $this->F_Model->countFaqRecords($data['search']); //25;
		$DISPLAY_PAGES = 25;
		$PAGE_LIMIT_VALUE = ($PAGE - 1) * $PAGE_LIMIT;
		// Get posted search value in variables
		$data['search'] = ($this->input->post('search')?trim(strtolower($this->input->post('search'))):$data['search']);
		// Count total cms records
		$total = $this->F_Model->countFaqRecords($data['search']);
		$PAGE_TOTAL_ROWS = $total;
		$PAGE_URL = $this->config->item('base_url').'helpcenter/admin/search/'.$data['search'];
		$data['PAGING'] = pagination_assoc($PAGE_TOTAL_ROWS,$PAGE_LIMIT,$DISPLAY_PAGES,$PAGE_URL,$page,$pages);
		//	Pagination end
		// Get all cms records
		$data['faqs'] = $this->F_Model->getFaqRecords($PAGE_LIMIT_VALUE,$PAGE_LIMIT, $data['search']);
      
       // set variable to show active menu 
      $data['menutab'] = 'master';
      $data['menuitem'] = 'faq';
		$this->load->view($this->config->item('base_template_dir').'/admin/faq/faq',$data);
	}//end admin
	#----------------------------------------
	# admin site FAQ add modify
	#----------------------------------------
	public function faqaddmodify($edit_id=0){
		if(!$this->phpsession->get('ciAdmId')){	redirect('r/k/admin');	}
		if(strlen(trim($this->input->post('Submit'))) > 0){
			if($edit_id > 0){
				//	update record
				$data = array(
									'question_txt'	=>	$this->input->post('question_txt'),
									'answer_txt' =>	$this->input->post('answer_txt'),
									'faq_cat_nm' =>	$this->input->post('faq_cat_nm'),
									'status' =>	$this->input->post('status')
				);
				$this->F_Model->faqOperations($data,'update',$edit_id);
				
				$this->message->setMessage("FAQ updated successfully","SUCCESS");
					redirect('/helpcenter/admin');
			}else{
				//	insert record
				$data = array(
									'question_txt'	=>	$this->input->post('question_txt'),
									'answer_txt' =>	$this->input->post('answer_txt'),
									'faq_cat_nm' =>	$this->input->post('faq_cat_nm'),
									'status' =>	$this->input->post('status')
				);
				$inserted_id = $this->F_Model->faqOperations($data,'addnew');
				if($inserted_id > 0){
					$this->message->setMessage("FAQ added successfully","SUCCESS");
					redirect('/helpcenter/admin');
				}
			}
		}
		$data = array(
							'edit_id' => '',
							'question_txt'	=>	'',
							'answer_txt' =>	'',
							'faq_cat_nm' =>	'',
							'status' =>	''
		);
		if($edit_id > 0){
			$faq = $this->F_Model->getFAQById($edit_id);
			$data = array(
								'edit_id'		=> $edit_id,
								'question_txt'		=>	$faq->question_txt,
								'answer_txt'	=>	$faq->answer_txt,
								'faq_cat_nm' => $faq->faq_cat_nm,
								'status' => $faq->status
			);
		}
		$data['categories'] = $this->F_Model->getFAQcategoriesByStatus('Active');
		/*$fckeditorConfig = array(
										'instanceName' => 'answer_txt',
										'BasePath' => $this->config->item('base_url').'system/plugins/fckeditor/',
										'ToolbarSet' => 'Default',
										'Width' => '100%',
										'Height' => '400',
										'Value' => $data['answer_txt']		);*/
		//$this->load->library('fckeditor', $fckeditorConfig);
		
		// set variable to show active menu 
      $data['menutab'] = 'master';
      $data['menuitem'] = 'faq';
		$this->load->view($this->config->item('base_template_dir').'/admin/faq/add_modify_faq',$data);
	}//end faqaddmodify
}//end class Faq

/* End of file faq.php */
/* Location: ./system/application/controllers/faq.php */
?>
