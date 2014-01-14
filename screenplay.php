<?php
class Screenplay extends CI_Controller {

	function __construct(){
		parent::__construct();
			$this->load->helper(array('form', 'url','pagination'));
		$this->load->model('Screenplay_Model','SP_Model');
		$this->load->model('Friends_Model','F_Model');
		$this->load->model('Sitesettings_model','SS_Model');
		$this->load->model('Users_Model',"U_Model");
		$this->load->model('Testimonial_Model','T_Model');
		$this->load->model('Wraters_Review_Model','WR_Model');
		$this->load->model('User_Review_Model','UR_Model');
		$this->load->model('ImageUpload_Model','IMGUPLD_Model');
		$this->load->model('Smartshare_Model','Sshare_Model');
        $this->load->library('phpsession');
        $this->load->library('message');
       
       
	}
	//render dashboard here...
	function index()
	{
		if(!$this->phpsession->get('ulogid')){ redirect('/register'); }
	}
	
	//CHECK REVIEWS FOR THIS TP ARE POSTED OR NOT
	public function checkreviews()
	{
		$screenplay_id = $this->input->post('screenplay_id');
		$totalreviews = 0;
		$userreview = $this->db->where('sid',$screenplay_id)->where('status','Accepted')->select("COUNT(*) AS RCount")->get('tbl_user_reviews');
						if($userreview->num_rows() > 0)
						{
							//$data['userreview'] = $userreview->row()->ratingscore;
							$totalreviews = $userreview->row()->RCount;
						}
		echo json_encode(array('reviews'=>$totalreviews));
						
						//end of
	}
	//END OF REVIEWS
	
	function _sentnotification($userid = 0, $to = 0, $message="")
	{
		
		$notification_data = array(
						'user_id' => $userid,
						'to_email'=>$to,
						'small_notification' => 'Screenplay Deleted',
						'notification' => $message,
						'post_date' => date("Y-m-d H:i:s"),
						'notification_url' => '',
						'status' => 'active',
						'type' => 'other',
						'anchor_id' => 0
						);
						$not_id = $this->Not_Model->notificationOperations($notification_data,'addnew',0);
	}
	
	// DELETE TITLE PAGE 
	public function deletescreenplaytp()
	{
		$screenplay_id = $this->input->post('screenplay_id');
		$sidInfo = $this->SP_Model->getScreenplayById($screenplay_id);
			$result = "fail";
			if($sidInfo)
			if($sidInfo->status == 'final') 
			{
				//file_path "uploads/screenplay"
				unlink($this->config->item('base_abs_path')."uploads/screenplay/".$sidInfo->file_path);
				$data_del = array('sid'=>$screenplay_id);
				$this->SP_Model->screenplayOperations($data_del,'delete',0);
				$this->message->setMessage("Title page is deleted","SUCCESS");
				
				
				// DELETE ALL ITS REVIEW INVITATIONS AND POSTED REVIEWS
				// FIRST SEND NOTIFICATION
				$this->db->where('tpid',$screenplay_id);
				$query = $this->db->get('tbl_sent_invitation');
				$this->load->model('Notification_Model',"Not_Model");
				foreach($query->result() as $invt)
				{
					$message = "<strong>"._titlepageName($sidInfo->title,false,0,15,true)."</strong> this Title Page is deleted by owner, your review with this TP will not available";
					
					$this->_sentnotification($invt->userid, '', $message);
				}
				$this->db->where('sid',$screenplay_id);
				$this->db->delete('tbl_user_reviews');
				$this->db->flush_cache();

				// REVIEW INVITATIONS
				$this->db->where('tpid',$screenplay_id);
				$this->db->delete('tbl_sent_invitation');
				//END OF REVIEWS
				
				
			}
		echo json_encode(array('result' => 'done'));
	}
	
	
	// END OF DELETE TITLE PAGE
	
	//CHECK PROMOTIONAL CODE VALIDATION....
	public function checkvalid()
	{
		if(!$this->phpsession->get('ulogid')){ redirect('/register'); }
		
		//$this->db->delete('tbl_promo_code_current_trans', array('user_id' => $this->phpsession->get('ulogid'))); 
		$this->phpsession->save('promotionalcodeid',0);
		
		$flag = false;
		$codetext = $this->input->post('codetext');
		$this->db->where('code',$codetext);
		$query = $this->db->get('tbl_promotional_code');	   
		$discount = 0;
		$code_id = 0;
		$totaltransactions = 0;
		if($query->num_rows() > 0)
		{
			if(date('Y-m-d',strtotime($query->row()->expiry_date)) > date('Y-m-d'))
			{
				
				//CHECK USER HAS  USED THIS CODE BEFORE OR NOT..
				$code_id = $query->row()->code_id;
				$no_of_attempt = $query->row()->no_of_attempt;
				$discount = $query->row()->discount;
				$user_id = $this->phpsession->get('ulogid');
				
				$this->db->flush_cache();
				$this->db->where('code_id',$code_id);
				$this->db->where('userid',$user_id);
				$query_order = $this->db->get('tbl_wrating_payment');
				
				if($query_order->num_rows() > 0)
				{
					$flag = false;
				}
				else
				{
					$this->db->flush_cache();
					$this->db->where('code_id',$code_id);
					$query_attempt = $this->db->get('tbl_wrating_payment');
					
					$donetrasactions = $query_attempt->num_rows();
						
						
						//$this->db->flush_cache();
						//$this->db->where('code_id',$code_id);
						//$current_transactions = $this->db->get('tbl_promo_code_current_trans')->num_rows();
						$totaltransactions = $donetrasactions;// + $current_transactions;
					
						if($totaltransactions >= $no_of_attempt )
						{
							$flag = false;
						}
						else
						{
							$flag = true;
						}

				}
				//END
			}
			//else
				//echo "expired".date('Y-m-d',strtotime($query->row()->expiry_date))."/".date('Y-m-d');
		}
		
		if($flag)
		{
			/*$data = array(
			'user_id' => $this->phpsession->get('ulogid'),
			'code_id' => $code_id,
			'applied_date' => date("Y-m-d H:i:s")
			);
			$this->db->insert('tbl_promo_code_current_trans', $data); */
			
			$this->phpsession->save('promotionalcodeid',$code_id);
			
		}
		else
		{
			//$this->db->delete('tbl_promo_code_current_trans', array('user_id' => $this->phpsession->get('ulogid'))); 
			$this->phpsession->save('promotionalcodeid',0);
		}
			
						
		echo json_encode(array("result"=>$flag,"discount"=>$discount,"totaltransactions"=>$totaltransactions));
	}
   
   
	public function deletesid()
	{
			$sid = $this->input->post('sid');
			$sidInfo = $this->SP_Model->getScreenplayById($sid);
			$result = "fail";
			if($sidInfo)
			if($sidInfo->status == 'sid') 
			{
				//file_path "uploads/screenplay"
				unlink($this->config->item('base_abs_path')."uploads/screenplay/".$sidInfo->file_path);
				$data_del = array('sid'=>$sid);
				$this->SP_Model->screenplayOperations($data_del,'delete',0);
				$result = "SID is deleted";
			}
			echo $result;
	}
	
	function addsid()
	{
		if(trim($this->input->post('sctitle'))!='' && strlen(trim($_FILES['scfile']['name'])) > 0)
		{
			$insert_data = array(
			'user_id'   => $this->phpsession->get('ulogid'),
			'title'   => addslashes($this->input->post('sctitle')),
			'status'   => 'sid',
			'submit_date'    =>  date("Y-m-d H:i:s"),
			'publish_status'=>'no'
			);
			//echo $insert_data['title'];
			//this is for upload submit file
			$this->load->helper("directory");
			$this->load->helper("upload_file");
			$screenplay_id = 0;	
		
			$fileTypes = array('pdf'); // File extensions
			$fileParts = pathinfo($_FILES['scfile']['name']);
			
						
			$checkDuplicateTitle = $this->SP_Model->checkDuplicateScreenplayTitle($insert_data['title'],'addnew','');
			if($checkDuplicateTitle == "true")
			{
					if(strlen(trim($_FILES['scfile']['name'])) > 0)
					{
						
							//if(str_replace("\"","",stripcslashes($_FILES['scfile']['type'])) == "application/pdf")
							//if($_FILES['scfile']['type'] == "application/pdf")
							if (in_array(strtolower($fileParts['extension']),$fileTypes)) 
							{
								//if($_FILES["scfile"]["size"] < 8388608 )
								if($_FILES["scfile"]["size"] < 2097152 )
								{
						
									$picture_path = @upload_file($_FILES['scfile'], "uploads/screenplay", "uploads/screenplay");
									$insert_data['file_path'] = $picture_path;
									//upload end
									$sid = $this->SP_Model->screenplayOperations($insert_data,'addnew',$screenplay_id);
									redirect('myaccount');
								}
								else
								{
									$this->phpsession->save('errrormsg',"Maximum size for upload is limited to 2 MB");
									redirect('myaccount');	
								}//	
							}
							else
							{
								$this->phpsession->save('errrormsg',"Please upload pdf file only.");
								redirect('myaccount');	
							}//
						
					}
					else
					{
						$this->phpsession->save('errrormsg',"Please upload pdf file only.");
						redirect('myaccount');	
					}
			}
			else
				{
					$this->phpsession->save('errrormsg',"Screenplay title already exist!");
					redirect('myaccount');	
				}//
		}
		else
		{
			$this->phpsession->save('errrormsg',"Please fill all mandatory fields");
			redirect('myaccount');	
		}//else
		
	}
	//this is used to create new screenplay....
	function addnew()
	{
		if(!$this->phpsession->get('ulogid')){ redirect('/register'); }
		if(strlen(trim($this->input->post('title'))) > 0){
			$screenplay_id = 0;
			if($this->input->post('selectstatus')=='final')
				$publish_status = 'yes';
			else
				$publish_status = 'no';
			$insert_data = array(
				'user_id'   => $this->phpsession->get('ulogid'),
				'title'   => addslashes($this->input->post('title')),
				'status'   => $this->input->post('selectstatus'),
				'log_line'   => addslashes($this->input->post('description')),
				'submit_date'    =>  date("Y-m-d H:i:s"),
				'publish_status'=>$publish_status
				);
				
				//this is for upload submit file
				$this->load->helper("directory");
				$this->load->helper("upload_file");
				$fileTypes = array('pdf'); // File extensions
				$fileParts = pathinfo($_FILES['BrowserHidden1']['name']);
				if(strlen(trim($_FILES['BrowserHidden1']['name'])) > 0)
				{
					
					//if(str_replace("\"","",stripcslashes($_FILES['BrowserHidden1']['type'])) == "application/pdf")
					if (in_array(strtolower($fileParts['extension']),$fileTypes)) 
					{
							if($_FILES["BrowserHidden1"]["size"] < 2097152 )
							{
								$picture_path = @upload_file($_FILES['BrowserHidden1'], "uploads/screenplay", "uploads/screenplay");
								$insert_data['file_path'] = $picture_path;
								//upload end
								$sid = $this->SP_Model->screenplayOperations($insert_data,'addnew',$screenplay_id);

								//Genre's
								$selectGenre = $this->input->post('selgenre');
								$Catcount = count($selectGenre);
								for($i=0;$i < $Catcount;$i++)
								{
									$tp_category=array(
									'title_page_id' => $sid,
									'category_id' => $selectGenre[$i]
									);
									$this->SP_Model->addTitlePageGenre($tp_category,'addnew');
								}
								$this->message->setMessage("Screenplay title is added successfully","SUCCESS");
								redirect('myaccount/');
							}
							else
							{
								$this->message->setMessage("Maximum size for upload is limited to 2 MB","ERROR");
								redirect('screenplay/addnew');
							}//	
					}
					else
					{
						$this->message->setMessage("Please select pdf file only.","ERROR");
						redirect('screenplay/addnew');
					}
				}
				else
				{
					//upload end
					 $this->message->setMessage("Please upload file for your screenplay title","ERROR");
					 redirect('screenplay/addnew');
				}
				redirect('myaccount/');
		}
		else
		{
			$data="";
			$data["genre"] =  $this->SP_Model->getCategory();
			$this->load->view($this->config->item('base_template_dir').'/user/screenplay/new_screenplay', $data);
		}
	}
	
	//publish screenplay
	function publish($sid=0)
	{
		if(!$this->phpsession->get('ulogid')){ redirect('/register'); }
		if($sid==0){redirect('myaccount/');}else{
			
			if(strlen(trim($this->input->post('logline'))) > 0){
				$screenplay_id = $sid;
				
				$update_data = array(
				'user_id'   => $this->phpsession->get('ulogid'),
				'log_line'   => $this->input->post('logline'),
				'status'   => 'final',
				'publish_status'   => 'yes',
				'plot'   => $this->input->post('plottext'),
				'publish_date'    =>  date("Y-m-d H:i:s"),
				'add_keyword'	=>	($this->input->post('keyword')=="Y"?"Y":"N"),
				'keywords'	=>	$this->input->post('keywords')
			
				);
				//this is for upload submit file
				$this->load->helper("directory");
				$this->load->helper("upload_file");
				if(strlen(trim($this->input->post('filenamehidd'))) > 0)
				{
						$file_path = directory_cleanname(time().$this->input->post('filenamehidd'));
						$update_data['file_path'] = $file_path;
						copy($this->config->item('base_abs_path')."uploads/screenplay1/".$this->input->post('filenamehidd'),$this->config->item('base_abs_path')."uploads/screenplay/".$file_path);
						unlink($this->config->item('base_abs_path')."uploads/screenplay1/".$this->input->post('filenamehidd'));
				}
				// IMAGE OF SCREENPLAY IS COMMENTED....HERE
				/*
				if(strlen(trim($_FILES['BrowserHidden']['name'])) > 0)
				{
					  if($_FILES['BrowserHidden']['size'] < 2097152)
					  {
						  if (exif_imagetype($_FILES['BrowserHidden']['tmp_name']) == IMAGETYPE_JPEG
						  || exif_imagetype($_FILES['BrowserHidden']['tmp_name']) == IMAGETYPE_PNG
						  || exif_imagetype($_FILES['BrowserHidden']['tmp_name']) == IMAGETYPE_GIF
						  ) 
							{					  
								$picture_path = @upload_file($_FILES['BrowserHidden'], "uploads/screenplay/image_files", "uploads/screenplay/image_files");
								$update_data['image_file'] = $picture_path;
								//upload end
							}
							else
							{
								$this->message->setMessage("Image with extention png, gif & jpeg are allowed.","ERROR");
								redirect('screenplay/publish/'.$screenplay_id);
							}
					  }
					  else
					  {
						 $this->message->setMessage("Image size is larger than 2MB is not allowed.","ERROR");
						  redirect('screenplay/publish/'.$screenplay_id);
					  }	
				}*/
				
				
					    $this->SP_Model->screenplayOperations($update_data,'update',$screenplay_id);
					    $selectGenre2 = $this->input->post('selgenre');
					    $Catcount = count($selectGenre2);
					    $this->SP_Model->deleteTitlePageGenre($screenplay_id);
					    for($i=0;$i < $Catcount;$i++)
					    {
							$tp_category=array(
							'title_page_id' => $screenplay_id,
							'category_id' => $selectGenre2[$i]
							);
							$this->SP_Model->addTitlePageGenre($tp_category,'addnew');
						}
						
						$this->message->setMessage("Your screenplay Title Page was published!","SUCCESS");
				/*else
				{
				//upload end
					 $this->message->setMessage("Please upload image file for your screenplay title.","ERROR");
					 redirect('screenplay/publish/'.$screenplay_id);
				}*/
				if($this->input->post('getWrated')=="true")
						redirect('screenplay/getwrated/'.$sid);
				else
						redirect('screenplay/title/'.$sid);
			}
			else{			
				$data["screenplay"] =  $this->SP_Model->getScreenplayById($sid);
				
				if($data["screenplay"])
				{
					
					if($data["screenplay"]->user_id==$this->phpsession->get('ulogid'))
					{
						
						$data["genre"] =  $this->SP_Model->getCategory($sid);
						$this->load->view($this->config->item('base_template_dir').'/user/screenplay/publish_screenplay', $data);
					}
					else
						redirect('myaccount');
				}//if
				else
					redirect('myaccount');
			}
		}
	}
	
	public function edittp($sid=0)
	{
		if(!$this->phpsession->get('ulogid')){ redirect('/register'); }
		if($sid==0){redirect('myaccount/');}else{
			
			if(strlen(trim($this->input->post('logline'))) > 0){
				$screenplay_id = $sid;
				$update_data = array(
				'user_id'   => $this->phpsession->get('ulogid'),
				'log_line'   => $this->input->post('logline'),
				'plot'   => $this->input->post('plottext'),
				'add_keyword'	=>	($this->input->post('keyword')=="Y"?"Y":"N"),
				'keywords'	=>	$this->input->post('keywords')
			
				);
				
				if($this->input->post('selectstatus')!='')
				{
					if($this->input->post('selectstatus')=='final')
						$update_data['publish_status'] = 'yes';
					else	
						$update_data['publish_status'] = 'no';
					$update_data['status'] = $this->input->post('selectstatus');
				}//if
				//this is for upload submit file
				$this->load->helper("directory");
				$this->load->helper("upload_file");
				if(strlen(trim($this->input->post('filenamehidd'))) > 0)
				{
						$file_path = directory_cleanname(time().$this->input->post('filenamehidd'));
						$update_data['file_path'] = $file_path;
						copy($this->config->item('base_abs_path')."uploads/screenplay1/".$this->input->post('filenamehidd'),$this->config->item('base_abs_path')."uploads/screenplay/".$file_path);
						unlink($this->config->item('base_abs_path')."uploads/screenplay1/".$this->input->post('filenamehidd'));
				}
				// IMAGE OF SCREENPLAY IS COMMENTED....HERE
				/*if(strlen(trim($_FILES['BrowserHidden']['name'])) > 0)
				{
					  if($_FILES['BrowserHidden']['size'] < 2097152)
					  {
						  if (exif_imagetype($_FILES['BrowserHidden']['tmp_name']) == IMAGETYPE_JPEG
						  || exif_imagetype($_FILES['BrowserHidden']['tmp_name']) == IMAGETYPE_PNG
						  || exif_imagetype($_FILES['BrowserHidden']['tmp_name']) == IMAGETYPE_GIF
						  ) 
							{						  
								$picture_path = @upload_file($_FILES['BrowserHidden'], "uploads/screenplay/image_files", "uploads/screenplay/image_files");
								$update_data['image_file'] = $picture_path;
								//upload end
							}
							else
							{
								$this->message->setMessage("Image with extention png, gif & jpeg are allowed.","ERROR");
								redirect('screenplay/edittp/'.$screenplay_id);
							}
					  }
					  else
					  {
						 $this->message->setMessage("Image size is larger than 2MB is not allowed.","ERROR");
						  redirect('screenplay/edittp/'.$screenplay_id);
					  }
				}
				*/
				
					    $this->SP_Model->screenplayOperations($update_data,'update',$screenplay_id);
					    $selectGenre2 = $this->input->post('selgenre');
					    $Catcount = count($selectGenre2);
					    $this->SP_Model->deleteTitlePageGenre($screenplay_id);
					    for($i=0;$i < $Catcount;$i++)
					    {
							$tp_category=array(
							'title_page_id' => $screenplay_id,
							'category_id' => $selectGenre2[$i]
							);
							$this->SP_Model->addTitlePageGenre($tp_category,'addnew');
						}
						
						$this->message->setMessage("Your screenplay was successfully edited","SUCCESS");
						redirect('screenplay/edittp/'.$sid);
			}
			else{			
				$data["screenplay"] =  $this->SP_Model->getScreenplayById($sid);
				
				if($data["screenplay"])
				{
					
					if($data["screenplay"]->user_id==$this->phpsession->get('ulogid'))
					{
						
						$data["genre"] =  $this->SP_Model->getCategory($sid);
						$this->load->view($this->config->item('base_template_dir').'/user/screenplay/edittp_screenplay', $data);
					}
					else
						redirect('myaccount');
				}//if
				else
					redirect('myaccount');
			}
		}
	}
	
	//used to edit screenplay for draft screenplay only
	public function edit($edit_id=0)
	{
		if(!$this->phpsession->get('ulogid')){ redirect('/register'); }
		if(strlen(trim($this->input->post('title'))) > 0){
		if($this->input->post('selectstatus')=='final')
				$publish_status = 'yes';
			else
				$publish_status = 'no';
			$update_data = array(
				'user_id'   => $this->phpsession->get('ulogid'),
				'title'   => $this->input->post('title'),
				'status'   => $this->input->post('selectstatus'),
				'log_line'   => $this->input->post('description'),
				'publish_status'=>$publish_status
				);
				
				//this is for upload submit file
				$this->load->helper("directory");
				$this->load->helper("upload_file");
				if(strlen(trim($_FILES['BrowserHidden']['name'])) > 0)
				{
					if($_FILES['BrowserHidden']['type'] == "application/pdf")
					{
						$picture_path = @upload_file($_FILES['BrowserHidden'], "uploads/screenplay", "uploads/screenplay");
						$update_data['file_path'] = $picture_path;
						//upload end
					}
					else
					{
						$this->message->setMessage("Please select pdf file only.","ERROR");
						redirect('screenplay/edit');
					}	
				}
					$sid = $this->SP_Model->screenplayOperations($update_data,'update',$edit_id);
					
					//Genre's
					$selectGenre = $this->input->post('selgenre');
					$Catcount = count($selectGenre);
					$this->SP_Model->deleteTitlePageGenre($edit_id);
					for($i=0;$i < $Catcount;$i++)
					{
						$tp_category=array(
						'title_page_id' => $edit_id,
						'category_id' => $selectGenre[$i]
						);
						$this->SP_Model->addTitlePageGenre($tp_category,'addnew');
					}
					$this->message->setMessage("Screenplay title is updated successfully.","SUCCESS");
					redirect('version/createnew/'.$edit_id);
		}
		else
		{
			$data['genre'] = $this->SP_Model->getCategory($edit_id);
			$data["screenplay"] =  $this->SP_Model->getScreenplayById($edit_id);
			$data['edit_id'] = $edit_id;
			$this->load->view($this->config->item('base_template_dir').'/user/screenplay/edit_screenplay', $data);
		}
		
	}
	
	public function paymentsuccess()
	{
		if(!$this->phpsession->get('ulogid')){ redirect('/register'); }
			$data="";
			$sid = $this->phpsession->get('payment_sid');
			$promotionalcodeid = $this->phpsession->get('promotionalcodeid');
				if($sid > 0)
				{
					if($this->phpsession->get('payment_gross') > 0)
					{
						$curdate = date('Y-m-d H:i:s');
						$userinfo = $this->U_Model->getUserById($this->phpsession->get('ulogid'));
						$screenplayinfo = $this->SP_Model->getScreenplayById($sid);
						$wrated_status = array(
						'payment_status' => 'done',
						'paymentdate' =>$curdate,
						'payment'=>$this->phpsession->get('payment_gross')
						);
						
						
						$this->SP_Model->screenplayOperations($wrated_status,'update',$sid);
						
						$insert_db['sid'] = $sid;
						$insert_db['userid'] =$this->phpsession->get('ulogid');
						$insert_db['totalamt'] = $this->phpsession->get('payment_gross');
						$insert_db['datepaid'] = $curdate;
						$insert_db['orderstatus'] = 'paid';
						$insert_db['transaction_id'] = $this->phpsession->get('txn_id');
						$insert_db['payer_email'] = $this->phpsession->get('payer_email');
						$insert_db['code_id'] = $this->phpsession->get('promotionalcodeid');
						$this->phpsession->save('promotionalcodeid',0);
						$this->db->insert('tbl_wrating_payment',$insert_db);
						$this->db->delete('tbl_promo_code_current_trans', array('user_id' => $this->phpsession->get('ulogid'),'code_id'=>$this->phpsession->get('promotionalcodeid'))); 
						//Order Confirmation Email
						$this->load->model('Systememail_Model','SE_Model');   
						//get email template from admin
						$admin_email= $this->SE_Model->getAdminEmails();
						$mail_content= $this->SE_Model->getEmailById(25);   
						
						//Email Sending Code
						$this->load->library('email');
						$this->email->from($admin_email->value,'Wraters');
						$this->email->to($userinfo->email);
						$this->email->subject($mail_content->subject);
						$message = $mail_content->message;
						
						$emailPath = $this->config->item('base_abs_path')."templates/".$this->config->item('base_template_dir');
						$email_template =  file_get_contents($emailPath.'/email/email.html');
						$message = str_replace("[[user]]", ucfirst($userinfo->first_name), $message);
						$message = str_replace("[[screenplay]]", $screenplayinfo->title, $message);
						$message = str_replace("[[transaction_id]]", $this->phpsession->get('txn_id'), $message);
						$message = str_replace("[[amount]]",'$'.$this->phpsession->get('payment_gross'), $message);
						$email_template = str_replace("[[EMAIL_HEADING]]", $mail_content->subject, $email_template);
						$email_template = str_replace("[[SITE_NAME]]", $this->config->item('base_site_name'), $email_template);
						$email_template = str_replace("[[footer]]", $this->config->item('base_site_name'), $email_template);
						
						$email_template = str_replace("[[EMAIL_CONTENT]]",(utf8_encode($message)), $email_template);              
						$email_template = str_replace("[[footer]]", $this->config->item('base_site_name'), $email_template);
						$email_template = str_replace("[[SITEROOT]]", $this->config->item('base_url'), $email_template);
						$email_template = str_replace("[[LOGO]]",$this->config->item('base_url')."templates/".$this->config->item('base_template_dir'), $email_template);
						$this->email->message(html_entity_decode($email_template));
						
						if(!send_ses_mail($userinfo->email,'no-reply@wraters.com',$admin_email->value,$mail_content->subject,$email_template))
						$this->email->send(); 
						
						//CLEAR PAYMENT SESSION VARS HERE... 
						$this->phpsession->clear('payment_gross');
						$this->phpsession->clear('payment_sid');
						$this->message->setMessage("Your payment is successfull, Screenplay will be Wrated soon.","SUCCESS");
					}
					redirect('screenplay/title/'.$sid);
				}
				else
				redirect('myaccount');
	}
	
	public function comingsoon()
	{
		$data = array('');
		$this->load->view($this->config->item('base_template_dir').'/user/screenplay/comingsoon', $data);
	}
	
	public function getWrated($sid=0)
	{
		if(!$this->phpsession->get('ulogid')){ redirect('/register'); }
		//redirect('screenplay/comingsoon');
			$data="";
			if($sid > 0)
			{
					$data['sidTitlepage'] = $this->SP_Model->getScreenplayById($sid);
					$data['userInfo'] = $this->U_Model->getUserById($this->phpsession->get('ulogid'));
					$data['sidGenre'] = $this->SP_Model->getSidGenre($sid);
					$data['wratedfees'] = $this->SS_Model->getSettingValueByType('WRATED_FEES');
					$data['paypalEmail'] = $this->SS_Model->getSettingValueByType('PAYPAL_EMAIL');
					$data['wratedbenefits'] = $this->SS_Model->getSettingValueByType('WRATED_BENEFITS');
					$data['testimonials'] =  $this->T_Model->gettestimonialRecords();
					$data['edit_sid'] = $sid;
					
					//retrive statistical Analytics
						/*invatee sent*/
						$invitations = $this->db->where('tpid',$sid)->count_all_results('tbl_sent_invitation');
						//$invitations = $invitations + $this->db->where('sid',$sid)->count_all_results('tbl_smart_share');
						$data['invitations']=$invitations;
						//end
						//screenplay view count
						$totleviews = $this->db->where('tp_id',$sid)->where("(page = 'siteuser' or page = 'smartshare')")->count_all_results('tbl_user_visited');
						$data['totleviews'] = $totleviews;
						//end
						//user profile visit count
						$profileVisit = $this->db->where('tp_id',$this->phpsession->get('ulogid'))->where("page = 'profile'")->count_all_results('tbl_user_visited');
						$data['profileVisit']=$profileVisit;
						//end
						//count tp pdf file download by visiter
						$downloadCount = $this->db->where('tp_id',$sid)->where("(page = 'tp_download' OR page = 'smartshare_tp_downlo')")->count_all_results('tbl_user_visited');
						$data['downloadCount']=$downloadCount;
						//end
						//screenplay USER REVIEW RATING SCORE
						$userreview = $this->db->where('sid',$sid)->where('status','Accepted')->select_avg('ratingscore')->select("COUNT(*) AS RCount")->get('tbl_user_reviews');
						if($userreview->num_rows() > 0)
						{
							$data['userreview'] = $userreview->row()->ratingscore;
							$data['RCount'] = $userreview->row()->RCount;
						}
						else
						{
							$data['userreview'] = 0;
							$data['RCount'] = 0;
						}
						//end of user rating
					
					
					$this->load->view($this->config->item('base_template_dir').'/user/screenplay/getwrated', $data);
			}
			else
			redirect('myaccount');
						
	}
	
	function changefile()
	{
		//$targetFolder = '/projects/wraters/uploads/screenplay1'; // Relative to the root
		$targetFolder = 'uploads/screenplay1'; // Relative to the root
		
		$verifyToken = md5('unique_salt' . $_POST['timestamp']);

		if (!empty($_FILES) && $_POST['token'] == $verifyToken) {
			$tempFile = $_FILES['Filedata']['tmp_name'];
			$targetPath =  $this->config->item('base_abs_path') .$targetFolder;//$_SERVER['DOCUMENT_ROOT']
			$targetFile = rtrim($targetPath,'/') . '/'.$_FILES['Filedata']['name'];// .$verifyToken
			// Validate the file type
			/*
			 * $fileTypes = array('application/pdf'); // File extensions
				$fileParts = $_FILES['Filedata']['type'];
				echo $fileParts;
				if (in_array($fileParts,$fileTypes)) {
			 * */
			//$_FILES['BrowserHidden']['type'])) == "application/pdf
			$fileTypes = array('pdf'); // File extensions
			$fileParts = pathinfo($_FILES['Filedata']['name']);
			if (in_array($fileParts['extension'],$fileTypes)) {
				move_uploaded_file($tempFile,$targetFile);
				echo 1;
			} else {
				echo 'Invalid file type';
			}
		}
	}
	
	public function download($sid=0)
	{
		$sidTitlepage = $this->SP_Model->getScreenplayById($sid);
		//DOWNLOAD PERMISSION FOR THIS SCREENPLAY WITH CURRENT USER.
		$flagDownload = 0;
		$this->load->helper('download');
		if(file_exists($this->config->item('base_abs_path').'uploads/screenplay/'.$sidTitlepage->file_path) && $sidTitlepage->file_path != "")
		{
			
			$data = file_get_contents($this->config->item('base_abs_path').'uploads/screenplay/'.$sidTitlepage->file_path); // Read the file's contents
			
			$name = $sidTitlepage->file_path;
			//do visite entry here...
			$varuserid = $this->phpsession->get('ulogid');
			if($varuserid > 0)
			{
				if($sidTitlepage->user_id != $varuserid)
				{
					$this->Statistics_Model->countUserVisited($sid,$varuserid,'tp_download');
					$flagDownload = $this->SP_Model->checkDownloadPermission($sid,$varuserid,'siteuser'); 
				}
				else
					$flagDownload = 1;
			}
			else
			{
				$varuserid = $this->phpsession->get('GuestId');
				$smartshareCode = $this->uri->segment(4);
				if($sidTitlepage->user_id != $varuserid)
				{
					$this->Statistics_Model->countUserVisited($sid,$varuserid,'smartshare_tp_download');
					$update_data = array('status' => 'downloaded');
					$this->db->update('tbl_smart_share',$update_data,array('share_code' => $smartshareCode));
					$flagDownload = $this->SP_Model->checkDownloadPermission($sid,$varuserid,'smartshare'); 
					
				}
			}
			
			if($flagDownload)
				force_download($name, $data);
		}
		else
		{
			$this->message->setMessage("Sorry, this file is not exist","ERROR");
			redirect('screenplay/title/'.$sid);
			
		}
		
		
		//end of visit
	}
	
	function mypage($sid=0)
	{
		
		if(!$this->phpsession->get('ulogid')){ redirect('/register'); }
			$data="";
			$data['sidTitlepage'] = $this->SP_Model->getScreenplayById($sid);
			if(!$data['sidTitlepage']){redirect('/myaccount/');}
			if($data['sidTitlepage']->publish_status=='yes')
			{
				$flag = 0;
				$data['visible']  = 0;
				$data['download']  = 0;
				
				if($data['sidTitlepage'])
				{
					$userid = $data['sidTitlepage']->user_id;
					//retrive statistical Analytics
					/*invatee sent*/
					$invitations = $this->db->where('tpid',$sid)->where('status','reviewing')->count_all_results('tbl_sent_invitation');
					//$invitations = $invitations + $this->db->where('sid',$sid)->count_all_results('tbl_smart_share');
					$data['invitations']=$invitations;
					//end
					//screenplay view count
					$totleviews = $this->db->where('tp_id',$sid)->where("(page = 'siteuser' or page = 'smartshare')")->count_all_results('tbl_user_visited');// or page = 'smartshare'
					$data['totleviews'] = $totleviews;
					//end
					//user profile visit count
					$profileVisit = $this->db->where('tp_id',$userid)->where("page = 'profile'")->count_all_results('tbl_user_visited');
					$data['profileVisit']=$profileVisit;
					//end
					//count tp pdf file download by visiter
					$downloadCount = $this->db->where('tp_id',$sid)->where("(page = 'tp_download' OR page = 'smartshare_tp_downlo')")->count_all_results('tbl_user_visited');// OR page = 'smartshare_tp_downlo'
					$data['downloadCount']=$downloadCount;
				
					//end
					
					//screenplay USER REVIEW RATING SCORE
					$userreview = $this->db->where('sid',$sid)->where('status','Accepted')->select_avg('ratingscore')->select("COUNT(*) AS RCount")->get('tbl_user_reviews');
					if($userreview->num_rows() > 0)
					{
						$data['userreview'] = $userreview->row()->ratingscore;
						$data['RCount'] = $userreview->row()->RCount;
					}
					else
					{
						$data['userreview'] = 0;
						$data['RCount'] = 0;
					}
						
					/*$userreviewCount = $this->db->where('sid',$sid)->select_avg('ratingscore')->get('tbl_user_reviews');
					if($userreview->num_rows() > 0)
					{
						$data['userreview'] = $userreview->row()->ratingscore;
					}
					else
						$data['userreview'] = 0;	*/
					//end
					//CHECK WHERE SCREENPLAY IS WRATED OR NOT IF YES THEN SHOW WRATERS REVIEW....
					$data['wraters_review'] = 0;
					if($data['sidTitlepage']->wrated_status == "yes")
					{
						$wraters_review = $this->WR_Model->getWratedReviewByReviewedSid($data['sidTitlepage']->sid);
						
						if($wraters_review)
						{
							$data['wraters_review'] = $wraters_review;
						}
					}
					//END OF REVIEW
					
					
					if($userid!=$this->phpsession->get('ulogid'))
					{
						if($data['sidTitlepage']->flagsettings==0) // To me only set
						{
							$this->phpsession->save('tpaccessid',$sid);
							$message = "Hi, I would like to review this screenplay but no longer have access";
							$this->phpsession->save('tpaccessmessage',$message);
							redirect('myaccount');
						}//if
						else if($data['sidTitlepage']->flagsettings==1) // Everyone
						{
						
							if($data['sidTitlepage']->everyvisible==1 || $data['sidTitlepage']->everydownload==1)
							{
								$flag = 1;
								$data['visible']  = $data['sidTitlepage']->everyvisible;
								$data['download']  = $data['sidTitlepage']->everydownload;
								
							}
							else
							{
								$this->phpsession->save('tpaccessid',$sid);
								$message = "Hi, I would like to review this screenplay but no longer have access";
								$this->phpsession->save('tpaccessmessage',$message);
								redirect('myaccount');
							}
								
						}
						else if ($data['sidTitlepage']->flagsettings==2)
						{
							$email = $this->U_Model->getUserimpdetails($this->phpsession->get('ulogid'))->email;
							
							$invitationsent = $this->SP_Model->getscreenplayinvitedetails($email,$userid,$data['sidTitlepage']->sid);
							
							if($invitationsent)
							{
								if($invitationsent->status=='Pending acceptance')
								{
									$message = "You havn't accepted invitation , please go to review invitation and accept it";
									$this->phpsession->save('tperrorsid',$sid);
									$this->phpsession->save('tperrormessage',$message);
									//redirect('screenplay/managereviews');
									redirect('myaccount');
								}//if
								if($invitationsent->status=='revoke')
								{
									$message = "your access has been revoked for this screenplay";
									$this->phpsession->save('tperrorsid',$sid);
									$this->phpsession->save('tperrormessage',$message);
									redirect('myaccount');
								}
								if($invitationsent->status=='reviewing')
								{
									$data['visible'] = $invitationsent->visible;
									$data['download']= $invitationsent->download;
									
									if($data['visible']==1 || $data['download']==1)
										$flag = 1;
									else
									{
										$this->phpsession->save('tpaccessid',$sid);
										$message = "Hi, I would like to review this screenplay but no longer have access";
										$this->phpsession->save('tpaccessmessage',$message);
										redirect('myaccount');
									}
								}//if
							}//if($invitationsent)
							else
							{
								$message = "You are not invited to review this screenplay";
								$this->phpsession->save('tperrorsid',$sid);
								$this->phpsession->save('tperrormessage',$message);
								redirect('myaccount');
							}//else
						}//if($userid!=$this->phpsession->get('ulogid'))
					}//if($data['sidTitlepage'])
					
					if($userid==$this->phpsession->get('ulogid'))
					{
						$flag =1;
						$data['visible']  = 1;
						$data['download']  = 1;
					}//if
					if($flag == 1)
					{
						$data['userInfo'] = $this->U_Model->getUserById($data['sidTitlepage']->user_id);
						$data['sidGenre'] = $this->SP_Model->getSidGenre($sid);
						
						//do visite entry here...
						$varuserid = $this->phpsession->get('ulogid');
						if($data['sidTitlepage']->user_id != $varuserid)
							$this->Statistics_Model->countUserVisited($sid,$varuserid,'siteuser');
						//end of visit
						
						$this->load->view($this->config->item('base_template_dir').'/user/screenplay/titlepage', $data);
					}//
					else
						redirect('myaccount');
				}//
				else
					redirect('/myaccount');
			}
			else
				redirect('/myaccount');
	}
	
	public function getdownloadcount()
	{
			$sid = $this->input->post('sid');
			$downloadCount = $this->db->where('tp_id',$sid)->where("(page = 'tp_download' OR page = 'smartshare_tp_downlo')")->count_all_results('tbl_user_visited');// OR page = 'smartshare_tp_downlo'
			$totleviews = $this->db->where('tp_id',$sid)->where("(page = 'siteuser' or page = 'smartshare')")->count_all_results('tbl_user_visited');// or page = 'smartshare'
				
			echo json_encode(array("downloadCount"=>$downloadCount,"screenplayview"=>$totleviews));
	}
	public function smartsharepage($smartshareCode)
	{
			$ssUserInfo = $this->Sshare_Model->getSmartShareUserInfo($smartshareCode);
			if($ssUserInfo)
			{
				$data="";
				$data['sidTitlepage'] = $this->SP_Model->getScreenplayById($ssUserInfo->sid);
				if($data['sidTitlepage'])
				{
					$data['userInfo'] = $this->U_Model->getUserById($data['sidTitlepage']->user_id);
					$data['sidGenre'] = $this->SP_Model->getSidGenre($ssUserInfo->sid);
					//do visite entry here...
					$this->Statistics_Model->countUserVisited($ssUserInfo->sid,$ssUserInfo->smart_share_id,'smartshare');
					
					//end of visit
					$this->load->view($this->config->item('base_template_dir').'/user/screenplay/titlepage', $data);
				}//
				else
					redirect('/register');
				}
			else
			{
				redirect('/register');
				}
	}
	//retrive screenplay for public profile page on demand of ajax call
	public function getScreenplayHTML()
	{
		$result = "fail";
		$screenplay_user_id =  $this->input->post('userid');
		$screenplay_id = $this->input->post('screenplay_id');
		$screenplay =  $this->SP_Model->getScreenplayById($screenplay_id);
		$userInfo = $this->U_Model->getUserById($screenplay_user_id);
	    $sidGenre = $this->SP_Model->getSidGenre($screenplay_id);
		$screenplayStr="";
		
		if($screenplay)
		{
	
		$screenplayStr .="	  <div class='content-middle'>";
		$screenplayStr .="		<div class='fl eastspace4'> <img width='235' height='170' src='".$this->config->item('base_upload_dir').'screenplay/image_files/screenplay.png'."'> </div>";
		if($screenplay->ratingscore!="")
		$screenplayStr .="		<div class='fr'> <img width='55' height='55' src='".$this->config->item('base_images_dir')."logo1.png'> <span class='rating1'>".$screenplay->ratingscore."</span> </div>";
		
		$screenplayStr .="		<div class='clr'></div>";
		$screenplayStr .="	  </div>";
		$screenplayStr .="	  <div class='content1'>";
		$screenplayStr .="		<div class='fl' style='width:235px;'>";
		$screenplayStr .="		  <h5 class='txt4 bw'>".$screenplay->title."</h5>";
		$screenplayStr .="		  <div class='listing'> ";
					$y = 1;
					 foreach($sidGenre->result() as $genre)
					   {
						   if($y!=1)
						   $screenplayStr .="<abbr >|</abbr>";
						   $screenplayStr .="<a href='javascript:void(0);' >".$genre->category."</a>";
						   $y++;
					   }
		$screenplayStr .=" </div>";
		$screenplayStr .="		</div>";
		$cnt = $this->db->where('sid',$screenplay->sid)->count_all_results('tbl_user_reviews');
		if($cnt > 0)
		{	
			if($cnt == 1)
				$r = 'Review';
			else
				$r = 'Reviews';
			$screenplayStr .="		<div class='fr content-review'> (".$cnt.") ".$r;
			
		}
		
		$screenplayStr .="	<br/>	  ".($screenplay->wrated_status=='yes'?'Wrated':'Not Wrated')." </div>";
		$screenplayStr .="		<div class='clr'></div>";
		$screenplayStr .="	  </div>";
		$screenplayStr .="	  <div class='description bw' style='font-style:italic;'>
		".
			$screenplay->log_line
		."
		</div>";
		
		$result = "done";
		}
		echo json_encode(array("result"=>$result,"screenplayStr"=>$screenplayStr));
	}//end of getScreeenplayHtml
	
	function get_break_string_words($string,$charLimit = 20) {
    //$string = preg_replace('/\s+/', ' ', trim($string));
    $words = explode(" ", $string);
    $wcount = count($words);
    
    $newString = "";
    for($i=0;$i<$wcount;$i++)
    {
		if(strlen($words[$i]) > $charLimit)
		{
			//$newString .=  " ";
			$newString .= wordwrap($words[$i], $charLimit, " ", true);
		}
		else
		{
			$newString .=  " ";
			$newString .= $words[$i];
		}
	}
		return $newString;
	}
	
	function tp_settings($sid=0)
	{
		if(!$this->phpsession->get('ulogid')){ redirect('/register'); }
		$data['sidTitlepage'] = $this->SP_Model->getScreenplayById($sid);
		if($data['sidTitlepage'])
		{
			if($data['sidTitlepage']->user_id==$this->phpsession->get('ulogid') && $data['sidTitlepage']->publish_status=='yes')
			{
				$data['userinfo'] = $this->U_Model->getUserById($this->phpsession->get('ulogid'));
				$data["invitessend"] = $this->db->where('tpid',$sid)->count_all_results('tbl_sent_invitation');
				$data["invitesaccepted"] = $this->db->where('tpid',$sid)->where("status != 'pending acceptance'")->count_all_results('tbl_sent_invitation');
				
				
				//screenplay view count
					$totleviews = $this->db->where('tp_id',$sid)->where("(page = 'siteuser' or page = 'smartshare')")->count_all_results('tbl_user_visited');// or page = 'smartshare'
					$data['totleviews'] = $totleviews;
					//end
					//user profile visit count
					$profileVisit = $this->db->where('tp_id',$data['sidTitlepage']->user_id)->where("page = 'profile'")->count_all_results('tbl_user_visited');
					$data['profileVisit']=$profileVisit;
					//end
					//count tp pdf file download by visiter
					$downloadCount = $this->db->where('tp_id',$sid)->where("(page = 'tp_download' OR page = 'smartshare_tp_downlo')")->count_all_results('tbl_user_visited');// OR page = 'smartshare_tp_downlo'
					$data['downloadCount']=$downloadCount;
					//end
				
				
				$data['reviewreceived'] = $this->SP_Model->getNumReviewAccetpted($sid);
				$this->load->view($this->config->item('base_template_dir').'/user/screenplay/tp_privacy_settings', $data);	
			}//if
			else
				redirect('myaccount');
		}//if
	}//
	
	function changestatus()
	{
		$flag = 0;
		if(!$this->phpsession->get('ulogid'))
			$flag = 0;
		else
		{
			$this->load->model('Notification_Model',"Not_Model");
			$tpid = $this->input->post('tpid');
			$sidpage= $this->SP_Model->getScreenplayById($tpid);
			$status = $this->input->post('status');
			$email = $this->U_Model->getUserimpdetails($this->phpsession->get('ulogid'))->email;
			if($status=='accept')
			{
				$this->db->select('status');
				$this->db->where('tpid',$tpid);
				$this->db->where('userid',$this->phpsession->get('ulogid'));
				$query = $this->db->get('tbl_sent_invitation');
				if($query->num_rows() > 0)
				{
					if($query->row()->status == 'reviewing')
						return 1;
					else if($query->row()->status == 'Decline')	
						return 1;
				}
				else
					return 0;
				
				$flag = 1;
				$status = 'reviewing';
				 $req_status = $this->F_Model->checkfriends($this->phpsession->get('ulogid'),$sidpage->user_id);
				 if($req_status)
				 {
				 	$update_db['request_status'] = 'confirm';
				 	$this->db->update('tbl_friends',$update_db,array('friends_id'=>$req_status->friends_id));
				 }
				 else
				 {
					$insert_db['requested_from_id'] = $sidpage->user_id;
					$insert_db['requested_id'] = $this->phpsession->get('ulogid');
					$insert_db['request_status'] = 'confirm';
					$insert_db['post_date'] = date('Y-m-d H:i:s');
					$this->db->insert('tbl_friends',$insert_db);
				}//else
			
				$this->db->update('tbl_sent_invitation',array('status'=>$status,'accepted_date'=>date('Y-m-d H:i:s'),'userid`'=>$this->phpsession->get('ulogid')),array('tpid'=>$tpid,'userid'=>$this->phpsession->get('ulogid')));
				//$this->message->setMessage(" Successfully updated ","SUCCESS");
			}//if
			else
			{
				
				$this->db->select('isreviewed');
				$this->db->where('tpid',$tpid);
				$this->db->where('userid',$this->phpsession->get('ulogid'));
				$query = $this->db->get('tbl_sent_invitation');
				if($query->num_rows() > 0)
				{
					if($query->row()->isreviewed == 1)
						return 0;
				}
				else
					return 0;
				
				$inv_id = $this->_get_invitation_id($tpid,$this->phpsession->get('ulogid'));
					
				$status = 'Decline';
				$flag = 2;
				$details = $this->U_Model->getUserimpdetails($this->phpsession->get('ulogid'));
				$notification_data = array(
				'user_id' => $sidpage->user_id,
				'small_notification' => $details->first_name." ".$details->last_name." has declined your invitation to review ",
				'notification' => $details->first_name." ".$details->last_name." has declined your invitation to review "._titlepageName($sidpage->title,false,0,15,true),
				'post_date' => date("Y-m-d H:i:s"),
				'notification_url' => $this->config->item('base_url').'myaccount/profile/'.clean_url($details->username),
				'status' => 'active',
				'type' => 'other',
				'anchor_id' => 0
				);
				$not_id = $this->Not_Model->notificationOperations($notification_data,'addnew',0);
				$this->db->delete('tbl_sent_invitation',array('userid'=>$this->phpsession->get('ulogid'),'tpid'=>$tpid));
			}
			
			
		}//else
		
		echo $flag;
	}//function
	
	function _get_invitation_id($tpid,$userid)
	{
		$this->db->select('id');
				$this->db->where('tpid',$tpid);
				$this->db->where('userid',$userid);
				$inv_id = $this->db->get('tbl_sent_invitation');
				
				if($inv_id->num_rows())
					return $inv_id = $inv_id->row()->id;
				else
					return '';
	}
	function acceptedinvitation()
	{
		$email = $this->U_Model->getUserimpdetails($this->phpsession->get('ulogid'))->email;
		$accepted = $this->SP_Model->getreviewinvitation($this->phpsession->get('ulogid'),'reviewing',3,0);
		$str = '';
		if($accepted ->result())
		{
			foreach($accepted->result() as $detail)
			{
				$from = $detail->invitefrom;
				$tpid = $detail->tpid;
				$sidTitlepage = $this->SP_Model->getScreenplayById($tpid);
				$inviteby = $this->U_Model->getUserimpdetails($from);
				
				if($inviteby->avtar_image=='')
					$img = 'screenplay-image.png';
				else
					$img = 'screenplay-image.png';
					
				$status = $inviteby->profilestatus;
					if($status =='public')
					$link = "<a href='".$this->config->item('base_url')."myaccount/profile/".clean_url($inviteby->username)."'>".ucfirst($inviteby->first_name)." ".ucfirst($inviteby->last_name)."</a>";
					else
						$link = "<a href='javascript:void(0);'>".ucfirst($inviteby->first_name)." ".ucfirst($inviteby->last_name)."</a>";
						
				$tplink = "<a class='user-txt fl' href='".$this->config->item('base_url')."screenplay/title/".$sidTitlepage->sid."'>"._titlepageName($sidTitlepage->title,false,0,25)."</a>";	
				$button = "<input type='button' name='btnview' id='btnview' value='Review' class='small-grn-button-bg01' onclick=\"window.location.href='".$this->config->item('base_url')."screenplay/title/".$tpid."'\" />";
				$str.="<div class='user-main'><div class='image-box3 fl' ><img width='47' height'66'  src='".$this->config->item('base_url')."uploads/screenplay/image_files/".$img."'><div class='sctext bw'>".(strlen($sidTitlepage->title) > 15 ? wordwrap(substr($sidTitlepage->title,0,15),6,"<br/>",true)."..." : wordwrap(substr($sidTitlepage->title,0,15),6,"<br/>",true))."</div></div><div class='user-main-rgt fl'>".$tplink ."<div class='clr'></div><p class='txt-invite  bw' style='width:346px;'><a href='#' >".$link."</a></p><p class='txt-invite'>".$button."</p> </div><div class='clr'></div></div>";
			}//foreach
		}//if
		else
			$str.="<div class='image-box fl'>No invitation..</div>";
		
		echo $str;
	}//function
	
	function updateprivacy()
	{
		if(!$this->phpsession->get('ulogid')){ redirect('/register'); }
		$tpid = $this->input->post('tpid');
		$dosettings = $this->input->post('dosettings');
		$visible = $this->input->post('visible');
		$download = $this->input->post('download');
		$sidTitlepage = $this->SP_Model->getScreenplayById($tpid);
		if($sidTitlepage->user_id==$this->phpsession->get('ulogid'))
		{
			if($dosettings==1) //To me only
			{
				$update_db['everyvisible'] = 0;
				$update_db['everydownload'] = 0;
				$update_db['invitevisible'] = 0;
				$update_db['invitedownload'] = 0;
				$update_db['flagsettings'] = 0;
				$this->db->update('tbl_screenplay',$update_db,array('sid'=>$tpid));
				$this->message->setMessage("Privacy settings updated.  Your Title Page is completely private.","SUCCESS");
			
			}//of
			elseif($dosettings==2) //Everyone
			{
				$update_db['everyvisible'] = $visible;
				$update_db['everydownload'] = $download;
				$update_db['invitevisible'] = 0;
				$update_db['invitedownload'] = 0;
				$update_db['flagsettings'] = 1;
				$this->db->update('tbl_screenplay',$update_db,array('sid'=>$tpid));
				$this->message->setMessage("Privacy settings updated.  Please enable Visible and Download settings to allow users to review your screenplay.","SUCCESS");
				
			}//else if
			else //Invited Reviewers
			{
				$update_db['everyvisible'] = 0;
				$update_db['everydownload'] =0;
				$update_db['invitevisible'] = $visible;
				$update_db['invitedownload'] = $download;
				$update_db['flagsettings'] = 2;
				$this->db->update('tbl_screenplay',$update_db,array('sid'=>$tpid));
				$this->message->setMessage("Privacy settings updated.  Please enable Visible and Download settings to allow invited reviewers to review your screenplay.","SUCCESS");
				
			}//else
			
			
		}//if		
	}//function
	
	
	
	function setaccessdate()
	{
		$tpid = $this->input->post('tpid');
		$txteditmsg = $this->input->post('txteditmsg');
		$sidTitlepage = $this->SP_Model->getScreenplayById($tpid);
		$values = $this->input->post('values');
		$accessdate = $this->input->post('accessdate');
		$array1 = explode('-',$accessdate);
		$ar1= $array1[2].'-'.$array1[0].'-'.$array1[1].' 23:59:59';
		
		if($sidTitlepage->user_id==$this->phpsession->get('ulogid'))
		{
			$this->db->update('tbl_sent_invitaion',array('till_access_date'=>$ar1),array('sid'=>$tpid));
			$tpuserinfo = $this->U_Model->getUsershortInfo($this->phpsession->get('ulogid'));
			//get email template from admin
			$link1 = $this->config->item('base_url')."myaccount/";
			$link = "<a href='".$link1."'>Click here to go</a>";
			$this->load->model('Systememail_Model','SE_Model');
			$admin_email= $this->SE_Model->getAdminEmails();
			$mail_content= $this->SE_Model->getEmailById(7); 
			$this->load->library('email');
			for($i=0;$i<count($values);$i++)
			{
				if(isset($values[$i]))
				{
					$userid = $values[$i];
					$userinfo = $this->U_Model->getUsershortInfo($userid);
					$to = $userinfo->email;
					$this->email->from($admin_email->value,'Wraters');
					$this->email->to($to);
					$this->email->subject($mail_content->subject);
					$message = $mail_content->message;
					$emailPath = $this->config->item('base_abs_path')."templates/".$this->config->item('base_template_dir');
					$email_template =  file_get_contents($emailPath.'/email/email.html');
					$message = str_replace("[[user]]",$userinfo->first_name, $message);
					$message = str_replace("[[tpuser]]",$tpuserinfo->first_name.' '.$tpuserinfo->last_name, $message);
					$message = str_replace("[[title]]",$sidTitlepage->title, $message);
					$message = str_replace("[[message]]",$txteditmsg, $message);
					$message = str_replace("[[accessdate]]",$sidTitlepage->accessdate, $message);
					$message = str_replace("[[click_here_to_go]]", $link, $message);
					$email_template = str_replace("[[EMAIL_HEADING]]", $mail_content->subject, $email_template);
					$email_template = str_replace("[[SITE_NAME]]", $this->config->item('base_site_name'), $email_template);
					$email_template = str_replace("[[footer]]", $this->config->item('base_site_name'), $email_template);
					$email_template = str_replace("[[EMAIL_CONTENT]]",(utf8_encode($message)), $email_template);              
					$email_template = str_replace("[[footer]]", $this->config->item('base_site_name'), $email_template);
					$email_template = str_replace("[[SITEROOT]]", $this->config->item('base_url'), $email_template);
					$email_template = str_replace("[[LOGO]]",$this->config->item('base_url')."templates/".$this->config->item('base_template_dir'), $email_template);
					$this->email->message(html_entity_decode($email_template));
					
					if(!send_ses_mail($to,'no-reply@wraters.com',$admin_email->value,$mail_content->subject,$email_template))
						$this->email->send(); 
					
					echo "Settings Saved!";
				}//if
			}//for
			
		}//if		
		
	}
	
	function revokeaccess()
	{
		$tpid = $this->input->post('tpid');
		$txtrevokemsg = $this->input->post('txtrevokemsg');
		$sidTitlepage = $this->SP_Model->getScreenplayById($tpid);
		$values = $this->input->post('values');
		
		$this->load->model('Systememail_Model','SE_Model');
		$admin_email= $this->SE_Model->getAdminEmails();
		$mail_content= $this->SE_Model->getEmailById(9); 
		$this->load->library('email');
		$tpuserinfo = $this->U_Model->getUsershortInfo($this->phpsession->get('ulogid'));
		for($i=0;$i<count($values);$i++)
		{
			if(isset($values[$i]))
			{
				$userid = $values[$i];
				$userinfo = $this->U_Model->getUsershortInfo($userid);
				$to = $userinfo->email;
				
				
				$insert_db['sid'] = $tpid;
				$insert_db['userid'] = $userid;
				$insert_db['email'] = $to;
				$insert_db['byid'] = $this->phpsession->get('ulogid');
				$insert_db['date_submit'] = date('Y-m-d H:i:s');
				$this->db->insert('tbl_revoke_access',$insert_db);				
				
				$this->db->delete('tbl_sent_invitation',array('invitefrom'=>$this->phpsession->get('ulogid'),'tpid'=>$tpid,'userid'=>$userid));
				//$update_db['status'] = 'revoke';
				//$this->db->update('tbl_sent_invitation',$update_db,array('email'=>trim($userinfo->email),'invitefrom'=>$this->phpsession->get('ulogid'),'tpid'=>$tpid));
				$this->email->from($admin_email->value,'Wraters');
				$this->email->to($to);
				$subject = str_replace("[[screenplay]]",$sidTitlepage->title, $mail_content->subject);
				$this->email->subject($subject);
				$message = $mail_content->message;
				$emailPath = $this->config->item('base_abs_path')."templates/".$this->config->item('base_template_dir');
				$email_template =  file_get_contents($emailPath.'/email/email.html');
				$message = str_replace("[[user]]",$userinfo->first_name, $message);
				$message = str_replace("[[tpuser]]",$tpuserinfo->first_name.' '.$tpuserinfo->last_name, $message);
				$message = str_replace("[[screenplay]]",$sidTitlepage->title, $message);
				$message = str_replace("[[title]]",$sidTitlepage->title, $message);
				$message = str_replace("[[message]]",$txtrevokemsg, $message);
				$email_template = str_replace("[[EMAIL_HEADING]]", $mail_content->subject, $email_template);
				$email_template = str_replace("[[SITE_NAME]]", $this->config->item('base_site_name'), $email_template);
				$email_template = str_replace("[[footer]]", $this->config->item('base_site_name'), $email_template);
				$email_template = str_replace("[[EMAIL_CONTENT]]",(utf8_encode($message)), $email_template);              
				$email_template = str_replace("[[footer]]", $this->config->item('base_site_name'), $email_template);
				$email_template = str_replace("[[SITEROOT]]", $this->config->item('base_url'), $email_template);
				$email_template = str_replace("[[LOGO]]",$this->config->item('base_url')."templates/".$this->config->item('base_template_dir'), $email_template);
				$this->email->message(html_entity_decode($email_template));
				
				if(!send_ses_mail($to,'no-reply@wraters.com',$admin_email->value,$subject,$email_template))
						$this->email->send(); 
				
				echo "Message Sent!";
			}//if
		}//for
	}//function
	
	function reminduser()
	{
		$tpid = $this->input->post('tpid');
		$txtremindmsg = $this->input->post('txtremindmsg');
		$sidTitlepage = $this->SP_Model->getScreenplayById($tpid);
		$values = $this->input->post('values');
		$this->load->model('Notification_Model',"Not_Model");
		$this->load->model('Systememail_Model','SE_Model');
		$admin_email= $this->SE_Model->getAdminEmails();
		$mail_content= $this->SE_Model->getEmailById(10); 
		$this->load->library('email');
		$tpuserinfo = $this->U_Model->getUsershortInfo($this->phpsession->get('ulogid'));
		for($i=0;$i<count($values);$i++)
		{
			if(isset($values[$i]))
			{
				$userid = $values[$i];
				$userinfo = $this->U_Model->getUsershortInfo($userid);
				$to = $userinfo->email;
				
				$this->email->from($admin_email->value,'Wraters');
				$this->email->to($to);
				$subject = str_replace("[[screenplay]]",$sidTitlepage->title, $mail_content->subject);
				$this->email->subject($subject);
				$message = $mail_content->message;
				$emailPath = $this->config->item('base_abs_path')."templates/".$this->config->item('base_template_dir');
				$email_template =  file_get_contents($emailPath.'/email/email.html');
				$message = str_replace("[[user]]",$userinfo->first_name, $message);
				$message = str_replace("[[tpuser]]",$tpuserinfo->first_name.' '.$tpuserinfo->last_name, $message);
				$message = str_replace("[[title]]",$sidTitlepage->title, $message);
				$message = str_replace("[[message]]",$txtremindmsg, $message);
				$email_template = str_replace("[[EMAIL_HEADING]]",$subject, $email_template);
				$email_template = str_replace("[[SITE_NAME]]", $this->config->item('base_site_name'), $email_template);
				$email_template = str_replace("[[footer]]", $this->config->item('base_site_name'), $email_template);
				$email_template = str_replace("[[EMAIL_CONTENT]]",(utf8_encode($message)), $email_template);              
				$email_template = str_replace("[[footer]]", $this->config->item('base_site_name'), $email_template);
				$email_template = str_replace("[[SITEROOT]]", $this->config->item('base_url'), $email_template);
				$email_template = str_replace("[[LOGO]]",$this->config->item('base_url')."templates/".$this->config->item('base_template_dir'), $email_template);
				$this->email->message(html_entity_decode($email_template));
				
				if(!send_ses_mail($to,$tpuserinfo->email,'no-reply@wraters.com',$subject,$email_template))
						$this->email->send(); 
				
				$inv_id = $this->_get_invitation_id($tpid,$this->phpsession->get('ulogid'));
				$notification_data = array(
				'user_id' => $userid,//(First Name Last Name) has reminded you to review (Screenplay Title)
				'small_notification' => $tpuserinfo->first_name." ".$tpuserinfo->last_name." has reminded you to review <strong>"._titlepageName($sidTitlepage->title,true,$sidTitlepage->sid,true)."</strong>",
				//'notification' => html_entity_decode($tpuserinfo->first_name." ".$tpuserinfo->last_name." is waiting for you to post last review on screenplay <strong>"._titlepageName($sidTitlepage->title,true,$sidTitlepage->sid)."</strong>"),
				'notification' => $tpuserinfo->first_name." ".$tpuserinfo->last_name." has reminded you to review <strong>"._titlepageName($sidTitlepage->title,true,$sidTitlepage->sid,true)."</strong>",
				
				'post_date' => date("Y-m-d H:i:s"),
				'notification_url' => $this->config->item('base_url').'screenplay/title/'.$tpid,
				'status' => 'active',
				'type' => 'other',
				'anchor_id' => 0
				);
				$not_id = $this->Not_Model->notificationOperations($notification_data,'addnew',0);
				
				
			}//if
		}//for
		echo "Message Sent!";
	}//reminduser
	
	function resendtouser()
	{
		$tpid = $this->input->post('tpid');
		$txtresendmsg = $this->input->post('txtresendmsg');
		$sidTitlepage = $this->SP_Model->getScreenplayById($tpid);
		$values = $this->input->post('values');
		$this->load->model('notification_model','Not_Model');
		$this->load->model('Systememail_Model','SE_Model');
		$admin_email= $this->SE_Model->getAdminEmails();
		$mail_content= $this->SE_Model->getEmailById(11); 
		$this->load->library('email');
		$tpuserinfo = $this->U_Model->getUsershortInfo($this->phpsession->get('ulogid'));
		for($i=0;$i<count($values);$i++)
		{
			if(isset($values[$i]))
			{
				$userid = $values[$i];
			
				$invitationdetails = $this->SP_Model->getscreenplayinvitedetailsbyid($userid,$this->phpsession->get('ulogid'),$tpid);
				if($invitationdetails->status=='Pending acceptance' || $invitationdetails->status=='decline')
				{
					$userinfo = $this->U_Model->getUsershortInfo($userid);
					$to = $userinfo->email;
					
					$this->email->from($admin_email->value,'Wraters');
					$this->email->to($to);
					$subject = str_replace("[[screenplay]]",$sidTitlepage->title, $mail_content->subject);
					$this->email->subject($subject);
					$message = $mail_content->message;
					$emailPath = $this->config->item('base_abs_path')."templates/".$this->config->item('base_template_dir');
					$email_template =  file_get_contents($emailPath.'/email/email.html');
					$message = str_replace("[[user]]",$userinfo->first_name, $message);
					$message = str_replace("[[tpuser]]",$tpuserinfo->first_name.' '.$tpuserinfo->last_name, $message);
					$message = str_replace("[[title]]",$sidTitlepage->title, $message);
					$message = str_replace("[[message]]",$txtresendmsg, $message);
					$email_template = str_replace("[[EMAIL_HEADING]]",$subject, $email_template);
					$email_template = str_replace("[[SITE_NAME]]", $this->config->item('base_site_name'), $email_template);
					$email_template = str_replace("[[footer]]", $this->config->item('base_site_name'), $email_template);
					$email_template = str_replace("[[EMAIL_CONTENT]]",(utf8_encode($message)), $email_template);              
					$email_template = str_replace("[[footer]]", $this->config->item('base_site_name'), $email_template);
					$email_template = str_replace("[[SITEROOT]]", $this->config->item('base_url'), $email_template);
					$email_template = str_replace("[[LOGO]]",$this->config->item('base_url')."templates/".$this->config->item('base_template_dir'), $email_template);
					$this->email->message(html_entity_decode($email_template));
					
					if(!send_ses_mail($to,$tpuserinfo->email,'no-reply@wraters.com',$subject,$email_template))
						$this->email->send(); 
					
					$this->db->delete('tbl_sent_invitation',array('invitefrom'=>$this->phpsession->get('ulogid'),'tpid'=>$tpid,'userid'=>$userid));
						
					if($sidTitlepage->flagsettings==1)
					{
						$insert_db['tpid'] = $tpid;
						$insert_db['userid'] =$userid;
						$insert_db['invitefrom'] = $this->phpsession->get('ulogid');
						$insert_db['email'] = $to;
						$insert_db['status'] ='reviewing';
						$insert_db['visible'] =1;
						$insert_db['download'] =1;
						$insert_db['invite_date'] = date('Y-m-d H:i:s');
						$this->db->insert('tbl_sent_invitation',$insert_db);
						$inv_id = $this->db->insert_id();
						$link = "<a href='".$this->config->item('base_url')."myaccount/screenplay/invitation'>"._titlepageName($sidTitlepage->title,false,0,15,true)."</a>";
						//$link = _titlepageName($sidTitlepage->title);
						//(First Name Last Name) has invited you to review (Screenplay Name)
						$message = $tpuserinfo->first_name." ".$tpuserinfo->last_name." has invited you to review <strong>".$link."</strong>";
						
						$notification_data = array(
						'user_id' => $userid,
						'to_email'=>$to,
						'small_notification' => 'Screenplay Invitation',
						'notification' => $message,
						'post_date' => date("Y-m-d H:i:s"),
						'notification_url' => $this->config->item('base_url').'myaccount/screenplay/invitation/'.$inv_id,
						'status' => 'active',
						'type' => 'other',
						'anchor_id' => 0
						);
						$not_id = $this->Not_Model->notificationOperations($notification_data,'addnew',0);
					}//if
					else if($sidTitlepage->flagsettings==2)
					{
						$this->db->delete('tbl_sent_invitation',array('invitefrom'=>$this->phpsession->get('ulogid'),'tpid'=>$tpid,'userid'=>$userid));
							
							
						$insert_db['tpid'] = $tpid;
						$insert_db['userid'] =$userid;
						$insert_db['invitefrom'] = $this->phpsession->get('ulogid');
						$insert_db['email'] = $to;
						$insert_db['status'] ='Pending acceptance';
						$insert_db['visible'] =1;
						$insert_db['download'] =1;
						$insert_db['invite_date'] = date('Y-m-d H:i:s');
						$this->db->insert('tbl_sent_invitation',$insert_db);
						$inv_id = $this->db->insert_id();
						$link = "<a href='".$this->config->item('base_url')."myaccount/screenplay/invitation'>"._titlepageName($sidTitlepage->title,false,0,15,true)."</a>";
						//$link = _titlepageName($sidTitlepage->title);
						$message = $tpuserinfo->first_name." ".$tpuserinfo->last_name." has invited you to review <strong>".$link."</strong>";
						//$message.="<p>".$link."</p>";
						
						$notification_data = array(
						'user_id' => $userid,
						'to_email'=>$to,
						'notification' => $message,
						'small_notification' => 'Screenplay Invitation',
						'post_date' => date("Y-m-d H:i:s"),
						'notification_url' => $this->config->item('base_url').'myaccount/screenplay/invitation/'.$inv_id,
						'status' => 'active',
						'type' => 'other',
						'anchor_id' => 0
						);
						$not_id = $this->Not_Model->notificationOperations($notification_data,'addnew',0);
					}//if
				}//if
			}//if
		}//for
		
		
	}//resendtouser
	
	function managereviews()
	{
		if(!$this->phpsession->get('ulogid')){ redirect('/register'); }
		$data['reviewrecieved'] = $this->SP_Model->getreceivedReviews($this->phpsession->get('ulogid'));
		$data['reviewposted'] = $this->SP_Model->getpostedReviews($this->phpsession->get('ulogid'));
		$this->load->view($this->config->item('base_template_dir').'/user/screenplay/review_mgmt',$data);	
	}
	
	function manageconnections()
	{
		if(!$this->phpsession->get('ulogid')){ redirect('/register'); }
		$data['reviewrecieved'] = 0;
		$data['reviewposted'] = 0;
		$this->load->view($this->config->item('base_template_dir').'/user/screenplay/connection_mgmt',$data);	
	}
	
	function updatesettings()
	{
		if(!$this->phpsession->get('ulogid')){ redirect('/register'); }
		$tpid = $this->input->post('tpid');
		$updateit = $this->input->post('updateit');
		$id = $this->input->post('id');
		$setto = $this->input->post('setto');
		$sidTitlepage = $this->SP_Model->getScreenplayById($tpid);
		
		$this->db->update('tbl_sent_invitation',array($updateit=>$setto),array('id'=>$id));
		echo "Settings Saved";
	}//
	
	function addreview($tpid)
	{
		$ar = explode('-',$tpid);
		if(isset($ar[0]))
		{
			$sid = base64_decode($ar[0]);
			$data['sidTitlepage'] = $this->SP_Model->getScreenplayById($sid);
			$flag = 0;
			$data['visible']  = 0;
			$data['download']  = 0;
			
			
			if($data['sidTitlepage'])
			{
				$userid = $data['sidTitlepage']->user_id;
				if($userid!=$this->phpsession->get('ulogid'))
				{
					if($data['sidTitlepage']->flagsettings==0) // To me only set
					{
						$this->phpsession->save('tpaccessid',$sid);
						$message = "Hello, I don't have permission to review your screenplay, please give permission to access it";
						$this->phpsession->save('tpaccessmessage',$message);
						redirect('myaccount');
					}//if
					else if($data['sidTitlepage']->flagsettings==1) // Everyone
					{
					
						if($data['sidTitlepage']->everyvisible==1 || $data['sidTitlepage']->everydownload==1)
						{
							$flag = 1;
							$data['visible']  = $data['sidTitlepage']->everyvisible;
							$data['download']  = $data['sidTitlepage']->everydownload;
							
						}
						else
						{
							$this->phpsession->save('tpaccessid',$sid);
							$message = "Hello, I don't have permission to review your screenplay, please give permission to access it";
							$this->phpsession->save('tpaccessmessage',$message);
							redirect('myaccount');
						}
							
					}
					else if ($data['sidTitlepage']->flagsettings==2)
					{
						$email = $this->U_Model->getUserimpdetails($this->phpsession->get('ulogid'))->email;
						
						$invitationsent = $this->SP_Model->getscreenplayinvitedetails($email,$userid,$data['sidTitlepage']->sid);
						
						if($invitationsent)
						{
							if($invitationsent->status=='Pending acceptance')
							{
								$message = "You havn't accepted invitation , please go to review invitation and accept it";
								$this->phpsession->save('tperrorsid',$sid);
								$this->phpsession->save('tperrormessage',$message);
								redirect('myaccount');
							}//if
							if($invitationsent->status=='revoke')
							{
								$message = "your access has been revoked for this screenplay";
								$this->phpsession->save('tperrorsid',$sid);
								$this->phpsession->save('tperrormessage',$message);
								redirect('myaccount');
							}
							if($invitationsent->status=='reviewing')
							{
								$data['visible'] = $invitationsent->visible;
								$data['download']= $invitationsent->download;
								
								if($data['visible']==1 || $data['download']==1)
									$flag = 1;
								else
								{
									$this->phpsession->save('tpaccessid',$sid);
									$message = "Hello, I don't have permission to review your screenplay, please give permission to access it";
									$this->phpsession->save('tpaccessmessage',$message);
									redirect('myaccount');
								}
							}//if
						}//if($invitationsent)
						else
						{
							$message = "You are not invited to review this screenplay";
							$this->phpsession->save('tperrorsid',$sid);
							$this->phpsession->save('tperrormessage',$message);
							redirect('myaccount');
						}//else
					}//else
				}//if($userid!=$this->phpsession->get('ulogid'))
				
				$data['sidGenre'] = $this->SP_Model->getSidGenre($sid);
				if($flag==1)
				{
					$checkexist = $this->SP_Model->checkreviewexists($sid,$this->phpsession->get('ulogid'));
					if(!$checkexist)
					{
						//retrive statistical Analytics
						/*invatee sent*/
						$invitations = $this->db->where('tpid',$sid)->count_all_results('tbl_sent_invitation');
						$invitations = $invitations + $this->db->where('sid',$sid)->count_all_results('tbl_smart_share');
						$data['invitations']=$invitations;
						//end
						//screenplay view count
						$totleviews = $this->db->where('tp_id',$sid)->where("(page = 'siteuser' or page = 'smartshare')")->count_all_results('tbl_user_visited');
						$data['totleviews'] = $totleviews;
						//end
						//user profile visit count
						$profileVisit = $this->db->where('tp_id',$userid)->where("page = 'profile'")->count_all_results('tbl_user_visited');
						$data['profileVisit']=$profileVisit;
						//end
						//count tp pdf file download by visiter
						$downloadCount = $this->db->where('tp_id',$sid)->where("(page = 'tp_download' OR page = 'smartshare_tp_downlo')")->count_all_results('tbl_user_visited');
						$data['downloadCount']=$downloadCount;
						//end
						//screenplay USER REVIEW RATING SCORE
						$userreview = $this->db->where('sid',$sid)->where('status','Accepted')->select_avg('ratingscore')->select("COUNT(*) AS RCount")->get('tbl_user_reviews');
						if($userreview->num_rows() > 0)
						{
							$data['userreview'] = $userreview->row()->ratingscore;
							$data['RCount'] = $userreview->row()->RCount;
						}
						else
						{
							$data['userreview'] = 0;
							$data['RCount'] = 0;
						}
						//end of user rating
						$data['userInfo'] = $this->U_Model->getUserById($data['sidTitlepage']->user_id);
						$this->load->view($this->config->item('base_template_dir').'/user/screenplay/add_review',$data);	
					}
					else
					{
						$this->message->setMessage("Already added review!!","ERROR");
						redirect('screenplay/title/'.$sid);
					}
				}
				else
				redirect('myaccount');
			}//
			else
				redirect('/myaccount');
			
		}//if
	}
	
	function savereview()
	{
		if(!$this->phpsession->get('ulogid')){ redirect('/'); }
		$checkexist = $this->SP_Model->checkreviewexists($this->input->post('sid'),$this->phpsession->get('ulogid'));
		
		if(!$checkexist)
		{
			$insert_db['userid'] = $this->phpsession->get('ulogid');
			$insert_db['sid'] = $this->input->post('sid');
			$insert_db['ratingscore'] = $this->input->post('rating');
			$insert_db['review'] = $this->input->post('review');
			$insert_db['logline'] = $this->input->post('logline');
			$insert_db['status'] = 'pending';
			$insert_db['submit_date'] = date("Y-m-d H:i:s");
			$this->db->insert('tbl_user_reviews',$insert_db);
			$rev_id = $this->db->insert_id();
			$userinfo = $this->U_Model->getUserimpdetails($this->phpsession->get('ulogid'));
			$sidinfo = $this->SP_Model->getScreenplayById($this->input->post('sid'));
				$notification = $userinfo->first_name.' '.$userinfo->last_name. " has reviewed <strong>"._titlepageName($sidinfo->title,false,0,15,true)."</strong>";
				
			$this->load->model('notification_model','Not_Model');
			$notification_data = array(
				'user_id' => $sidinfo->user_id,
				'notification' => $notification,
				'post_date' => date("Y-m-d H:i:s"),
				'notification_url' => $this->config->item('base_url').'screenplay/managereviews/'.$rev_id,
				'status' => 'active',
				'type' => 'other',
				'anchor_id' =>0
			);
			$not_id = $this->Not_Model->notificationOperations($notification_data,'addnew',0);
			
			//UPDATE INVITATION ISREVIEWED TO 1
			$updatereviewinv = array("isreviewed" => 1);
			$this->db->where('tpid', $this->input->post('sid'));
			$this->db->where('userid', $this->phpsession->get('ulogid'));
			$this->db->update("tbl_sent_invitation",$updatereviewinv);
			//END
			
			//$update_db['status'] = 'reviewed';
			//$this->db->update('tbl_sent_invitation',$update_db,array('tpid'=>$this->input->post('sid'),'userid'=>$this->phpsession->get('ulogid')));
			$this->message->setMessage("Review submitted.  To manage your reviews, visit the Review Management page.","SUCCESS");
		}
		else
			$this->message->setMessage("Already added review!!","ERROR");
			
		redirect("screenplay/title/".$this->input->post('sid'));
	}//savereview
	
	 function acceptreview()
	 {
	 	$this->load->model('notification_model','Not_Model');
	 	$tpid = $this->input->post('tpid');
		$reviewid = $this->input->post('reviewid');
		$sidinfo = $this->SP_Model->getScreenplayById($tpid);
		$reviewinfo = $this->SP_Model->getUserReviewById($reviewid);
		if($sidinfo->user_id == $this->phpsession->get('ulogid'))
		{
			$this->db->update('tbl_user_reviews',array('status'=>'Accepted'),array('reviewid'=>$reviewid));
			$userinfo = $this->U_Model->getUserimpdetails($this->phpsession->get('ulogid'));
			$notification = "Your review of <strong>"._titlepageName($sidinfo->title,false,0,15,true)."</strong> has been accepted by ".$userinfo->first_name.' '.$userinfo->last_name;
			$notification_data = array(
				'user_id' => $reviewinfo->userid,
				'notification' => $notification,
				'post_date' => date("Y-m-d H:i:s"),
				'notification_url' => $this->config->item('base_url').'screenplay/title/'.$tpid."#userreview",
				//'notification_url' => $this->config->item('base_url').'screenplay/managereviews/'.$reviewid,
				'status' => 'active',
				'type' => 'other',
				'anchor_id' =>0
			);
			$not_id = $this->Not_Model->notificationOperations($notification_data,'addnew',0);
			$this->message->setMessage("Review Added!","SUCCESS");
		}//if
	 }//acceptreview
	 
	 function declineresubmitaction()
	 {
	 	$this->load->model('notification_model','Not_Model');
	 	$tpid = $this->input->post('tpid');
		$reviewid = $this->input->post('reviewid');
		$sidinfo = $this->SP_Model->getScreenplayById($tpid);
		$reviewinfo = $this->SP_Model->getUserReviewById($reviewid);
		$this->db->update('tbl_user_reviews',array('status'=>'Accepted'),array('reviewid'=>$reviewid));
			$userinfo = $this->U_Model->getUserimpdetails($this->phpsession->get('ulogid'));
			
			$notification = $userinfo->first_name.' '.$userinfo->last_name. " has declined to modify the review of <strong>"._titlepageName($sidinfo->title,false,0,15,true)."</strong>";
			$notification_data = array(
				'user_id' => $sidinfo->user_id,
				'notification' => $notification,
				'post_date' => date("Y-m-d H:i:s"),
				'notification_url' => $this->config->item('base_url').'screenplay/managereviews/'.$reviewid,
				'status' => 'active',
				'type' => 'other',
				'anchor_id' =>0
			);
			$not_id = $this->Not_Model->notificationOperations($notification_data,'addnew',0);
			$this->message->setMessage("Review modification declined","SUCCESS");
	 }//acceptreview
	 
	 function deletereview()
	 {
			$this->load->model('notification_model','Not_Model');
			$tpid = $this->input->post('tpid');
			$reviewid = $this->input->post('reviewid');
			//get reviewer id
			$reviewer_id = $this->db->select('userid')->where('reviewid',$reviewid)->get('tbl_user_reviews')->row()->userid;
			$sidinfo = $this->SP_Model->getScreenplayById($tpid);
			// delete review invitation
			$this->db->delete('tbl_sent_invitation',array('tpid'=>$tpid,'invitefrom'=>$this->phpsession->get('ulogid')));
			//end of del.
			//delete review posted by user
			$this->db->flush_cache();
			$this->db->delete("tbl_user_reviews",array('reviewid'=>$reviewid));
			//end of del.
			$this->message->setMessage("Review rejected.  You may request a new review from this user on the Get Reviewed page.","SUCCESS");
			
			$userinfo = $this->U_Model->getUserimpdetails($this->phpsession->get('ulogid'));
			//(First Name Last Name) has not accepted your review of (Screenplay Name)
			$notification = $userinfo->first_name.' '.$userinfo->last_name. " has not accepted your review of <strong>"._titlepageName($sidinfo->title,false,0,15,true)."</strong>";
			$notification_data = array(
				'user_id' => $reviewer_id ,
				'notification' => $notification,
				'post_date' => date("Y-m-d H:i:s"),
				'notification_url' => '',
				'status' => 'active',
				'type' => 'other',
				'anchor_id' =>0
			);
			$not_id = $this->Not_Model->notificationOperations($notification_data,'addnew',0);
			echo "done";
	}
	 
	 function rejectreview()
	 {
	 	$this->load->model('notification_model','Not_Model');
	 	$tpid = $this->input->post('tpid');
		$reviewid = $this->input->post('reviewid');
		$rejectreason = $this->input->post('rejectreason');
		$sidinfo = $this->SP_Model->getScreenplayById($tpid);
		$resubmit = $this->input->post('resubmit');
		$reviewinfo = $this->SP_Model->getUserReviewById($reviewid);
		if($sidinfo->user_id == $this->phpsession->get('ulogid'))
		{
			$this->db->update('tbl_user_reviews',array('status'=>'rejected','reason'=>$rejectreason,'resubmit'=>$resubmit),array('reviewid'=>$reviewid));
			
			$userinfo = $this->U_Model->getUserimpdetails($this->phpsession->get('ulogid'));
			
			if($resubmit==1)
				$notification = $userinfo->first_name.' '.$userinfo->last_name. " has requested a modification of your review of <strong>"._titlepageName($sidinfo->title,false,0,15,true)."</strong>";
			else
				$notification = 'Author '.$userinfo->first_name.' '.$userinfo->last_name. " has removed your review of <strong>"._titlepageName($sidinfo->title,false,0,15,true)."</strong>";
				
				
			$notification_data = array(
				'user_id' => $reviewinfo->userid,
				'notification' => $notification,
				'post_date' => date("Y-m-d H:i:s"),
				'notification_url' => $this->config->item('base_url').'screenplay/managereviews/'.$reviewid,
				'status' => 'active',
				'type' => 'other',
				'anchor_id' =>0
			);
			$not_id = $this->Not_Model->notificationOperations($notification_data,'addnew',0);
			if($resubmit==1)
			$this->message->setMessage("Review modification request sent","SUCCESS");
			else
			$this->message->setMessage("This review has been removed from your Title Page","SUCCESS");
		}//if
	 }//acceptreview
	 
	 function deletefromlistreview()
	 {
	 	$tpid = $this->input->post('tpid');
		$insert_db['reviewid'] = $this->input->post('reviewid');
		$insert_db['userid'] = $this->phpsession->get('ulogid');
		$insert_db['submit_date'] = date('Y-m-d H:i:s');
		$this->db->insert('tbl_revdelete_list',$insert_db);
	 }//acceptreview
	 
	 function removereview()
	 {
	 	$sid = $this->input->post('sid');
		$reviewid = $this->input->post('reviewid');
		
		$this->db->delete('tbl_user_reviews',array('reviewid'=>$reviewid));
	 }//removereview
	 
	 
	 function resubmitreview($tpid)
	{
		$ar = explode('-',$tpid);
		if(isset($ar[0]))
		{
			$sid = base64_decode($ar[0]);
			$data['sidTitlepage'] = $this->SP_Model->getScreenplayById($sid);
			$flag = 0;
			$data['visible']  = 0;
			$data['download']  = 0;
			
		
			if($data['sidTitlepage'])
			{
				
				$userid = $data['sidTitlepage']->user_id;
				if($userid!=$this->phpsession->get('ulogid'))
				{
					if($data['sidTitlepage']->flagsettings==0) // To me only set
					{
						$this->phpsession->save('tpaccessid',$sid);
						$message = "Hello, I don't have permission to review your screenplay, please give permission to access it";
						$this->phpsession->save('tpaccessmessage',$message);
						redirect('myaccount');
					}//if
					else if($data['sidTitlepage']->flagsettings==1) // Everyone
					{
					
						if($data['sidTitlepage']->everyvisible==1 || $data['sidTitlepage']->everydownload==1)
						{
							$flag = 1;
							$data['visible']  = $data['sidTitlepage']->everyvisible;
							$data['download']  = $data['sidTitlepage']->everydownload;
							
						}
						else
						{
							$this->phpsession->save('tpaccessid',$sid);
							$message = "Hello, I don't have permission to review your screenplay, please give permission to access it";
							$this->phpsession->save('tpaccessmessage',$message);
							redirect('myaccount');
						}
							
					}
					else if ($data['sidTitlepage']->flagsettings==2)
					{
							
						$email = $this->U_Model->getUserimpdetails($this->phpsession->get('ulogid'))->email;
						
						$invitationsent = $this->SP_Model->getscreenplayinvitedetails($email,$userid,$data['sidTitlepage']->sid);
						if($invitationsent)
						{
							
							if($invitationsent->status=='Pending acceptance')
							{
								$message = "You havn't accepted invitation , plase go to review invitation and accept it";
								$this->phpsession->save('tperrorsid',$sid);
								$this->phpsession->save('tperrormessage',$message);
								redirect('myaccount');
							}//if
							if($invitationsent->status=='revoke')
							{
								$message = "your access has been revoked for this screenplay";
								$this->phpsession->save('tperrorsid',$sid);
								$this->phpsession->save('tperrormessage',$message);
								redirect('myaccount');
							}
							
							
							
							if($invitationsent->status=='reviewing')
							{
							
								$data['visible'] = $invitationsent->visible;
								$data['download']= $invitationsent->download;
								
								if($data['visible']==1 || $data['download']==1)
									$flag = 1;
								else
								{
									$this->phpsession->save('tpaccessid',$sid);
									$message = "Hello, I don't have permission to review your screenplay, please give permission to access it";
									$this->phpsession->save('tpaccessmessage',$message);
									redirect('myaccount');
								}
							}//if
						}//if($invitationsent)
						else
						{
							$message = "You are not invited to review this screenplay";
							$this->phpsession->save('tperrorsid',$sid);
							$this->phpsession->save('tperrormessage',$message);
							redirect('myaccount');
						}//else
					}//else
				}//if($userid!=$this->phpsession->get('ulogid'))
				$data['sidGenre'] = $this->SP_Model->getSidGenre($sid);
				if($flag==1)
				{
					
					$reviewdetails = $this->SP_Model->getreviewdetails($sid,$this->phpsession->get('ulogid'));
					if($reviewdetails)
					{
						
						if($reviewdetails->resubmit==1)
						{
							$data['reviewdetails'] = $reviewdetails;
							//retrive statistical Analytics
						/*invatee sent*/
						$invitations = $this->db->where('tpid',$sid)->count_all_results('tbl_sent_invitation');
						$invitations = $invitations + $this->db->where('sid',$sid)->count_all_results('tbl_smart_share');
						$data['invitations']=$invitations;
						//end
						//screenplay view count
						$totleviews = $this->db->where('tp_id',$sid)->where("(page = 'siteuser' or page = 'smartshare')")->count_all_results('tbl_user_visited');
						$data['totleviews'] = $totleviews;
						//end
						//user profile visit count
						$profileVisit = $this->db->where('tp_id',$userid)->where("page = 'profile'")->count_all_results('tbl_user_visited');
						$data['profileVisit']=$profileVisit;
						//end
						//count tp pdf file download by visiter
						$downloadCount = $this->db->where('tp_id',$sid)->where("(page = 'tp_download' OR page = 'smartshare_tp_downlo')")->count_all_results('tbl_user_visited');
						$data['downloadCount']=$downloadCount;
						//end
						//screenplay USER REVIEW RATING SCORE
						$userreview = $this->db->where('sid',$sid)->where('status','Accepted')->select_avg('ratingscore')->select("COUNT(*) AS RCount")->get('tbl_user_reviews');
						if($userreview->num_rows() > 0)
						{
							$data['userreview'] = $userreview->row()->ratingscore;
							$data['RCount'] = $userreview->row()->RCount;
						}
						else
						{
							$data['userreview'] = 0;
							$data['RCount'] = 0;
						}
						//end of user rating
						$data['userInfo'] = $this->U_Model->getUserById($data['sidTitlepage']->user_id);
							
							//$this->load->view($this->config->item('base_template_dir').'/user/screenplay/resubmit_review',$data);	
							$this->load->view($this->config->item('base_template_dir').'/user/screenplay/re_submit_review',$data);	
						}//of
						else
							redirect('mytaccount');
					}
					else
					{
						redirect('myaccount');
					}
				}
				else
				redirect('myaccount');
			}//
			else
				redirect('/myaccount');
			
		}//if
	}
	
	function renewreview()
	{
		if(!$this->phpsession->get('ulogid')){ redirect('/'); }
		
		$reviewid = $this->input->post('reviewid');
		$this->db->delete('tbl_user_reviews',array('reviewid'=>$reviewid));
		$checkexist = $this->SP_Model->checkreviewexists($this->input->post('sid'),$this->phpsession->get('ulogid'));
		
		if(!$checkexist)
		{
			$insert_db['userid'] = $this->phpsession->get('ulogid');
			$insert_db['sid'] = $this->input->post('sid');
			$insert_db['ratingscore'] = $this->input->post('rating');
			$insert_db['review'] = $this->input->post('review');
			$insert_db['logline'] = $this->input->post('logline');
			$insert_db['status'] = 'pending';
			$insert_db['submit_date'] = date("Y-m-d H:i:s");
			$this->db->insert('tbl_user_reviews',$insert_db);
			$rev_id = $this->db->insert_id();
			//$update_db['status'] = 'reviewed';
			//$this->db->update('tbl_sent_invitation',$update_db,array('tpid'=>$this->input->post('sid'),'userid'=>$this->phpsession->get('ulogid')));
			$sidinfo = $this->SP_Model->getScreenplayById($this->input->post('sid'));
			$userinfo = $this->U_Model->getUserimpdetails($this->phpsession->get('ulogid'));
			$notification = $userinfo->first_name.' '.$userinfo->last_name. " has re-submited review on <strong>"._titlepageName($sidinfo->title,false,0,15,true)."</strong> Title page";
				
			$this->load->model('notification_model','Not_Model');
			$notification_data = array(
				'user_id' => $sidinfo->user_id,
				'notification' => $notification,
				'post_date' => date("Y-m-d H:i:s"),
				'notification_url' => $this->config->item('base_url').'screenplay/managereviews/'.$rev_id,
				'status' => 'active',
				'type' => 'other',
				'anchor_id' =>0
			);
			$not_id = $this->Not_Model->notificationOperations($notification_data,'addnew',0);
			
			//UPDATE INVITATION ISREVIEWED TO 1
			$updatereviewinv = array("isreviewed" => 1);
			$this->db->where('tpid', $this->input->post('sid'));
			$this->db->where('userid', $this->phpsession->get('ulogid'));
			$this->db->update("tbl_sent_invitation",$updatereviewinv);
			//END
			
			//$update_db['status'] = 'reviewed';
			//$this->db->update('tbl_sent_invitation',$update_db,array('tpid'=>$this->input->post('sid'),'userid'=>$this->phpsession->get('ulogid')));
			$this->message->setMessage("Review added successfully.  To manage your reviews, visit the Review Management page","SUCCESS");
			
		}
		else
			$this->message->setMessage("Already added review!!","ERROR");
			
		redirect("screenplay/title/".$this->input->post('sid'));
	}//savereview
	
	function visidownloadsettings()
	{
		$type = $this->input->post('type');
		$tpid = $this->input->post('tpid');
		$values = implode(',',$this->input->post('values'));
		$setto =$this->input->post('setto');
		
		$this->db->set($type,$setto);	
		$this->db->where_in('id',$this->input->post('values'));
		$this->db->update('tbl_sent_invitation');
	}//function
	
	
}
