<?php ob_start();
class Admin extends CI_Controller{
   private $msg = '';
   private $user = '';
   #----------------------------------------
   // Intialise constructor
   #----------------------------------------
   public function __construct(){
      parent::__construct();
      $this->load->helper(array('form', 'url'));
      $this->load->model('State_Model','S_Model');
      $this->load->model('Users_Model',"U_Model");
      $this->load->model('metatags_Model', 'MT_Model');
      $this->load->model('ImageUpload_Model','IMGUPLD_Model');
      $this->load->model('sitesettings_model','SS_Model');
      $this->load->model('Newsletter_Model','NL_Model');
	  $this->load->library('phpsession');
	  $this->load->library('message');
	  
	  //Mailchimp Newsletter lib.
	  $sitesettings = $this->SS_Model->getSiteSettingsDetails();
	 $MAILCHIMP = array(
					'apikey' => $sitesettings[13],
					'secure' => FALSE   // Optional (defaults to FALSE)
				);
        		$this->load->library('MCAPI', $MAILCHIMP, 'mail_chimp');

//        $this->load->model('Adminsubcathelp_model','ASH_Model');
//        $this->load->model('Management_Model',"M_Model");

	$this->tinyMce = '
			<!-- TinyMCE -->
			<script type="text/javascript" src="'. base_url().'js/tiny_mce/tiny_mce.js"></script>
			<script type="text/javascript">
				tinyMCE.init({
					// General options
					mode : "textareas",
					theme : "simple"
				});
			</script>
			<!-- /TinyMCE -->
			';
   }//end admin

    

   public function home(){
       if($this->phpsession->get('ciAdmId')<1){   redirect($this->config->item('base_url').'r/k/admin');
      }else{               
	if($this->phpsession->get('admUserTypeId') == 1)
	{	  
      //Total online users
      $data['total_live_users']= $this->U_Model->countUsersByUserTypeId('yes');
      $data['total_active_users']= $this->U_Model->countUsersByUserTypeId('','active');
      $this->load->model('State_Model','S_Model');     
      $data['total_writers'] = $this->U_Model->countuserssRecords();
	  $data['total_ips'] = $this->U_Model->countipRecords();
	   $data['total_users'] = $this->U_Model->countallRecords();
       $data['todays_users'] = $this->U_Model->countTodaysuserssRecords();
	 }
	 else
	 {
		$data['totalreviews']= $this->db->where('staff_id',$this->phpsession->get('ciAdmId'))->count_all_results('tbl_staff_review');
		$data['totalpending']= $this->U_Model->getScreenplayForStaffCount($this->phpsession->get('ciAdmId'));
	 }  
 // set variable to show active menu 
      $data['menutab'] = 'dashboard';
      $data['menuitem'] = 'dashboard';
      $this->load->view($this->config->item('base_template_dir').'/admin/_home', $data);
      }
   }//end index

   #----------------------------------------
   // Admin login
   #----------------------------------------

   public function login(){  
      if($this->phpsession->get('ciAdmId') > 0){ redirect($this->config->item('base_url').'admin/home'); }
      if(strlen(trim($this->input->post('btnLogin'))) > 0){
         // Check admin login
         $usersInfo = $this->U_Model->checkAdminLogin(mysql_real_escape_string($this->input->post('loginname')), md5($this->input->post('password')));
         if ($usersInfo == FALSE){
               $this->msg = "<span class='error'>Incorrect login lnformation, please enter valid information.</span>";
               $this->phpsession->save('msg', $this->msg);
               redirect('r/k/admin');
         }else{         
            // Set admin login information in session
            $this->phpsession->save('admLgn', TRUE);
            $this->phpsession->save('ciAdmId', $usersInfo->userid);
            $this->phpsession->save('duAdmFname', $usersInfo->first_name);
            $this->phpsession->save('duAdmLname', $usersInfo->last_name);            
            $this->phpsession->save('admUserTypeId', $usersInfo->usertypeid);            
            // Set subadmin login information in session
            $this->phpsession->save('duPersonTypeId', $usersInfo->access_level);
            redirect($this->config->item('base_url').'admin/home');
         }
      }
      $this->load->view($this->config->item('base_template_dir').'/admin/login');
   }//end login
   
   public function stafflogin(){  
      if($this->phpsession->get('ciAdmId') > 0){ redirect($this->config->item('base_url').'admin/home'); }
      if(strlen(trim($this->input->post('btnLogin'))) > 0){
         // Check admin login
         $usersInfo = $this->U_Model->checkStaffLogin(mysql_real_escape_string($this->input->post('loginname')), md5($this->input->post('password')));
         if ($usersInfo == FALSE){
               $this->msg = "<span class='error'>Incorrect login lnformation, please enter valid information.</span>";
               $this->phpsession->save('msg', $this->msg);
               redirect('r/k/staff');
         }else{         
            // Set admin login information in session
            $this->phpsession->save('admLgn', TRUE);
            $this->phpsession->save('ciAdmId', $usersInfo->userid);
            $this->phpsession->save('duAdmFname', $usersInfo->first_name);
            $this->phpsession->save('duAdmLname', $usersInfo->last_name);            
            $this->phpsession->save('admUserTypeId', $usersInfo->usertypeid);            
            // Set subadmin login information in session
            $this->phpsession->save('duPersonTypeId', $usersInfo->access_level);
            redirect($this->config->item('base_url').'staff/home');
         }
      }
      $this->load->view($this->config->item('base_template_dir').'/admin/stafflogin');
   }//end login

   #----------------------------------------
   // Admin logout
   #----------------------------------------

   public function logout(){
      $this->phpsession->clear();
      redirect('r/k/admin');
   }//end logout

#----------------------------------------
   // Staff logout
   #----------------------------------------

   public function stafflogout(){
      $this->phpsession->clear();
      redirect('r/k/staff');
   }//end logout

   #----------------------------------------
   // Admin forgot password
   #----------------------------------------
   public function forgotpassword($type = ""){
	   $data["pagetype"] = $type;
      $this->load->view($this->config->item('base_template_dir').'/admin/forgot_password',$data);
      //redirect('admin/forgotpassword');
   }//end forgotpassword

   
   public function ipapprove($user_id = 0)
   {
	    $data = array(
				'isapprove'   => 'yes'
				);
	    $this->U_Model->users_operations($data,'update',$user_id);
	    $this->_sendverificationlink($user_id);
		
		redirect('admin/industrialprofessional');
   }
   
   function _sendverificationlink($inserted_id)
{
	if($inserted_id > 0){
                  $userinfo = $this->U_Model->getUsershortInfo($inserted_id);                                
                  $this->load->model('SystemEmail_Model','SE_Model');
                    $password = $this->input->post('password');
            //-- create account activation code
			 $activationcode = md5($inserted_id * 32765);
					   // Create link for verification
			 $link = "<a href='".$this->config->item('base_url')."register/registerverify/".$activationcode."'>".$this->config->item('base_url')."register/registerverify/".$activationcode."</a>";
               //--add activation code to table & use it while verification
               $this->U_Model->update_Activation_Code($inserted_id, $activationcode);   
               //--load systememail model for sending email ot user for verification
               $this->load->model('Systememail_Model','SE_Model');   
               //get email template from admin
               $admin_email= $this->SE_Model->getAdminEmails();
               $mail_content= $this->SE_Model->getEmailById(1);   

               //Email Sending Code
               $this->load->library('email');
               $this->email->from($admin_email->value,'Wraters');
               $this->email->to($this->input->post('email'));
               $this->email->subject($mail_content->subject);
               $message = $mail_content->message;

               $emailPath = $this->config->item('base_abs_path')."templates/".$this->config->item('base_template_dir');
               $email_template =  file_get_contents($emailPath.'/email/email.html');
               $message = str_replace("[[username]]", ucfirst($this->input->post('fname')), $message);
               $message = str_replace("[[email]]", $this->input->post('email'), $message);
               $message = str_replace("[[fullname]]", ucfirst($this->input->post('fname'))." ".ucfirst($this->input->post('lname')), $message);
               $message = str_replace("[[password]]", $password, $message);
               $message = str_replace("[[link]]", $link, $message);
               $email_template = str_replace("[[EMAIL_HEADING]]", $mail_content->subject, $email_template);
               $email_template = str_replace("[[SITE_NAME]]", $this->config->item('base_site_name'), $email_template);
               $email_template = str_replace("[[footer]]", $this->config->item('base_site_name'), $email_template);
               $email_template = str_replace("[[EMAIL_CONTENT]]",(utf8_encode($message)), $email_template);              
               $email_template = str_replace("[[footer]]", $this->config->item('base_site_name'), $email_template);
               $email_template = str_replace("[[SITEROOT]]", $this->config->item('base_url'), $email_template);
               $email_template = str_replace("[[LOGO]]",$this->config->item('base_url')."templates/".$this->config->item('base_template_dir'), $email_template);
               $this->email->message(html_entity_decode(stripcslashes($email_template)));
				//echo stripcslashes($email_template);die;
               //$this->email->send();   
               
               if(!send_ses_mail($this->input->post('email'),'no-reply@wraters.com',$admin_email->value,$mail_content->subject,$email_template))
               		$this->email->send();   
               //echo $this->email->print_debugger(); 
               //die;
		   }
	   }  
   
   
   #----------------------------------------
   // Show all users at admin side
   #----------------------------------------

   public function users($userid=0){
	  if(!$this->phpsession->get('ciAdmId')){   redirect($this->config->item('base_url').'r/k/admin');   }
	
      if($userid > 0){
         //resend verification mail to user
         $___user = $this->U_Model->getUserById($userid);

         $activationcode = $___user->activationcode;
         $verification_code=$userid * 32765;
         // Create link for verification
         $link = "<a href='".$this->config->item('base_url')."register/register_verify/".$activationcode."'>".$this->config->item('base_url')."register/register_verify/".$activationcode."</a>";

         $this->load->model('SystemEmail_Model','SE_Model');

         $admin_email= $this->SE_Model->getAdminEmails();
         $mail_content= $this->SE_Model->getEmailById(1);
//print_r($mail_content); exit;
         //Email Sending Code
         $this->load->library('email');
         $this->email->from($admin_email->value,'Wraters');
         $this->email->to($___user->email);  // 
         $this->email->subject($mail_content->subject);

         $message = str_replace("[link]", $link, $mail_content->message);
         $message = str_replace("[[username]]", $___user->first_name, $message);
         $message = str_replace("[[email]]", $___user->email, $message);
         $message = str_replace("[[verificationcode]]", $verification_code, $message);
         $message = str_replace("[[password]]", base64_decode($___user->base_password), $message);
         
         
         $content_message = str_replace("[sitename]", $this->config->item('base_site_name'), $message);

         $emailPath = $this->config->item('base_abs_path')."templates/".$this->config->item('base_template_dir');
         $email_template =  file_get_contents($emailPath.'/email/email.html');
         

         $email_template = str_replace("[[EMAIL_HEADING]]", $mail_content->subject, $email_template);
         $email_template = str_replace("[[EMAIL_CONTENT]]", utf8_encode($content_message), $email_template);
         $email_template = str_replace("[[SITEROOT]]", $this->config->item('base_url'), $email_template);
         $email_template = str_replace("[[LOGO]]",$this->config->item('base_url')."templates/".$this->config->item('base_template_dir'), $email_template);

         $this->email->message(html_entity_decode(($email_template)));
         //echo html_entity_decode(($email_template));

         $this->email->send();

         //$this->phpsession->save('success_msg',"Verification email successfully send to '".$___user->first_name." ".$___user->last_name."'.");
		 $this->message->setMessage("Verification email successfully send to '".$___user->first_name." ".$___user->last_name."'.", "SUCCESS" );	
         redirect($this->config->item('base_url')."admin/users/");
         
      }

      $data = array('users' => '', 'PAGING' => '', 'search' => '-','signup'=>'-','orderby'=>'-','fn'=>'-','status'=>'-');
      $this->load->helper(array('pagination'));
      $array = $this->uri->uri_to_assoc(3);

      $pages = (@$array['pages']?$array['pages']:1);
      $page = (@$array['page']?$array['page']:1);

      $orderb = (@$array['orderby']?@$array['orderby']:"asc");             $data['orderby']      = $orderb;
      $fn = (@$array['fn']?@$array['fn']:"first_name");                    $data['fn']           = $fn;
      $status = (@$array['status']?@$array['status']:"-");                 $data['status']       = $status;  
      $isverified = (@$array['isverified']?@$array['isverified']:"-");     $data['isverified']   = $isverified;    
      $orderby = $fn." ".$orderb;

      $data['search'] = (@$array['search']?$array['search']:'-');
      $data['signup'] = (@$array['signup']?$array['signup']:'-');

      
      if(strlen(trim($this->input->post('submit'))) > 0){ 
            $user_ids = implode(",",$this->input->post('checbox_ids'));
           $action =  $this->input->post('action');  
            $message = $this->U_Model->users_operations(array('user_ids' => $user_ids),$action);
		
			
            // echo  $this->db->last_query();exit;
            //$this->phpsession->save('success_msg',$message);
            $this->message->setMessage($message,"SUCCESS");
      }

      $PAGE = $page;
      $PAGE_LIMIT =  $this->U_Model->countUsersBySearch($data['search'],$data['signup'],$status,$isverified); //20;
      $DISPLAY_PAGES = 25;
      $PAGE_LIMIT_VALUE = ($PAGE - 1) * $PAGE_LIMIT;

      if($this->input->post('signup')!='')
      {
       $data['signup'] = $this->input->post('signup');     
      }
     
      // Get posted search value in variables
     
      $data['search'] = ($this->input->post('search')?trim($this->input->post('search')):$data['search']);
      $data['signup']= ($this->input->post('signup')?trim($this->input->post('signup')):$data['signup']);
      
      // Count total users
      $total = $this->U_Model->countUsersBySearch($data['search'],$data['signup'],$status,$isverified);
//print_r($this->db->last_query());
      $PAGE_TOTAL_ROWS = $total;
      $PAGE_URL = $this->config->item('base_url').'admin/users/fn/'.$fn.'/orderby/'.$orderb.'/search/'.$data['search'].'/signup/'.$data['signup'].'/status/'.$status.'/isverified/'.$isverified;
      $data['PAGING'] = pagination_assoc($PAGE_TOTAL_ROWS,$PAGE_LIMIT,$DISPLAY_PAGES,$PAGE_URL,$page,$pages);
      // Pagination end
      
      // Get all users
      $data['users'] = $this->U_Model->users($PAGE_LIMIT_VALUE,$PAGE_LIMIT,$data['search'],$orderby,$data['signup'],$status,$isverified);

      // set variable to show active menu 
      $data['menutab'] = 'network';
      $data['menuitem'] = 'users';
      $this->load->view($this->config->item('base_template_dir').'/admin/users', $data);

   }//end users



	public function unsubcribeNewsletter()
	{
		//Add user to mailchimp list (START)
					 
						if($this->input->post('newsletter') == 'yes'  && $this->input->post('oldnewsletter') != "yes")
						{
							$merges = array(
								'FNAME' => ucwords($this->input->post('fname')),
								'LNAME' => ucwords($this->input->post('lname'))
							);
							$mailchimp_lists = $this->NL_Model->getListAssociatedKey();
							
							if($mailchimp_lists->num_rows()>0)
							{
								foreach($mailchimp_lists->result() as $l)
								{
									$list_id = $l->key;
									//$list_id = '870676594a';
									$listemail = $this->input->post('email');
									$this->mail_chimp->listSubscribe($list_id,$listemail,$merges,'html',true);
									//echo $list_id."|".$listemail."|".print_r($merges);
									//echo "yes subcribing";die;
									//listUnsubscribe same like listSubscribe
								}
							}
						}
						elseif($this->input->post('newsletter') != 'yes' && $this->input->post('oldnewsletter') == "yes")
						{
							$merges = array(
								'FNAME' => ucwords($this->input->post('fname')),
								'LNAME' => ucwords($this->input->post('lname'))
							);
							$mailchimp_lists = $this->NL_Model->getListAssociatedKey();
							
							if($mailchimp_lists->num_rows()>0)
							{
								foreach($mailchimp_lists->result() as $l)
								{
									$list_id = $l->key;
									//$list_id = '870676594a';
									$listemail = $this->input->post('email');
									$this->mail_chimp->listUnsubscribe($list_id,$listemail,$merges,'html',true);
									//echo $list_id."|".$listemail."|".print_r($merges);
									//echo "No Unsubcribing";die;
									//echo "listUnsubscribe";die;
									//listUnsubscribe same like listSubscribe
								}
							}
						}
						//Add user to mailchimp list (END)
	}
   #----------------------------------------
   // admin site users operation addnew update
   #----------------------------------------

   public function usersnew($edit_id=0){
       if(!$this->phpsession->get('ciAdmId')){   redirect($this->config->item('base_url').'r/k/admin');   }
      
      if(strlen(trim($this->input->post('Submit'))) > 0){
         if($edit_id > 0){
         
            // update record
            $data = array(
							'displayname'   => $this->input->post('displayname'),
                           'first_name'   => $this->input->post('fname'),
                           'last_name'    => $this->input->post('lname'),
                           'email'        => $this->input->post('email'),
                           'username'     => $this->input->post('username'),   
                           'country_id'   => $this->input->post('country'),
                           'state'        => $this->input->post('state'),
                           'city'         => $this->input->post('city'),
                           'zipcode'      => $this->input->post('zipcode'),
                           'title'      => $this->input->post('title'),
                           'username'      => $this->input->post('username'),
                           'homepage'      => $this->input->post('homepage'),
                           'bio'      => $this->input->post('bio')//,
                          // 'newsletter' => $this->input->post('newsletter')
                           
            );
			
			
             if(strlen(trim($this->input->post('password'))) > 0){
               //$data = array_merge($data,array('password' => md5($this->input->post('password')),'base_password' => base64_encode($this->input->post('password'))));
			   $data['password'] =  md5($this->input->post('password'));
			   $data['base_password'] =  base64_encode($this->input->post('password'));
            }
            //this is for upload profile image
				$this->load->helper("directory");
				$this->load->helper("upload_file");
			
				if(strlen(trim($_FILES['profileimage']['name'])) > 0)
				{
					 if($_FILES['profileimage']['size'] < 2097152)
					  {
					  $picture_path = @upload_file($_FILES['profileimage'], "uploads/usersphoto", "usersphoto");
					  $data['avtar_image'] = $picture_path;
					  $this->phpsession->save('uprofileimage', $picture_path);		
					 
					 
					  $updateresult = $this->U_Model->users_operations($data,'update',$edit_id);
					  //$this->unsubcribeNewsletter();
						$this->message->setMessage("User info updated!!","SUCCESS");
						redirect('admin/users');
					  }
					  else
					  {
						  $this->message->setMessage("Image size is larger than 2MB is not allowed.","ERROR");
						  redirect('admin/users');
						  
					  }
				}
				else
				{
					  $updateresult = $this->U_Model->users_operations($data,'update',$edit_id);
					  //$this->unsubcribeNewsletter();
					  $this->message->setMessage("User info updated!!","SUCCESS");
					  redirect('admin/users');
				}
				//upload end
				           
         }else{

            // insert record
            $inserted_id="";
            if(strlen(trim($this->input->post('email'))) > 0 && strlen(trim($this->input->post('password'))) > 0){          
            
               $dob = explode("-",$this->input->post('dob'));
               $data = array(
								'displayname'   => $this->input->post('displayname'),
                              'first_name'   => $this->input->post('fname'),
                              'last_name'    => $this->input->post('lname'),
                              'email'        => $this->input->post('email'),
                              'username'     => $this->input->post('username'),
                              'password'     => md5($this->input->post('password')),
                              'base_password' => base64_encode($this->input->post('password')),
                              'usertypeid'   => 2,
                              'status'       => 'active',
                              'country_id'      => $this->input->post('country'),
                              'state'        => $this->input->post('state'),
                              'city'         => $this->input->post('city'),
                              'zipcode'      => $this->input->post('zipcode'),
                              'isverified'   => 'yes',
                              'verified_date'=> date("Y-m-d H:i:s"),
                              'signup_date'  => date("Y-m-d H:i:s"),
                              'title'      => $this->input->post('title'),
							  'username'      => $this->input->post('username'),
							  'homepage'      => $this->input->post('homepage'),
							  'bio'      => $this->input->post('bio')//,
							 // 'newsletter' => $this->input->post('newsletter')
               );              

               if($this->phpsession->get('inserted_id') == ""){ 
				//this is for upload profile image
				$this->load->helper("directory");
				$this->load->helper("upload_file");
				if(strlen(trim($_FILES['profileimage']['name'])) > 0)
				{
					if($_FILES['profileimage']['size'] < 2097152)
					  {
					  $picture_path = @upload_file($_FILES['profileimage'], "uploads/usersphoto", "usersphoto");
					  $data['avtar_image'] = $picture_path;
					  $this->phpsession->save('uprofileimage', $picture_path);		
					  // insert user...
					  $inserted_id = $this->U_Model->users_operations($data,'addnew');
					  }
					  else
					  {
						  $this->message->setMessage("Image size is larger than 2MB is not allowed.","ERROR");
					  }
				}
				else
				{
					$inserted_id = $this->U_Model->users_operations($data,'addnew');
				}
				//upload end
					
                    if($inserted_id > 0)
                    {  
						$data='';  
					}
					
				}
				else
				{
					$inserted_id = $this->U_Model->users_operations();
				}
					 
               if($inserted_id > 0){
				   //Add user to mailchimp list (START)
					/*if($this->input->post('newsletter') == 'yes')
						{
							$merges = array(
								'FNAME' => ucwords($this->input->post('fname')),
								'LNAME' => ucwords($this->input->post('lname'))
							);
							$mailchimp_lists = $this->NL_Model->getListAssociatedKey();
							if($mailchimp_lists->num_rows()>0)
							{
								foreach($mailchimp_lists->result() as $l)
								{
									$list_id = $l->key;
									//$list_id = '870676594a';
									$listemail = $this->input->post('email');
									$this->mail_chimp->listSubscribe($list_id,$listemail,$merges,'html',true);
									//listUnsubscribe same like listSubscribe
								}
							}
						}*/
						//Add user to mailchimp list (END)
				   
                  $userinfo = $this->U_Model->getUsershortInfo($inserted_id);                                
                  $this->load->model('SystemEmail_Model','SE_Model');
                  $password = $this->input->post('password');
            //-- create account activation code
         $activationcode = md5($inserted_id * 32765);
                   // Create link for verification
         $link = "<a href='".$this->config->item('base_url')."register/registerverify/".$activationcode."'>".$this->config->item('base_url')."register/registerverify/".$activationcode."</a>";
               //--add activation code to table & use it while verification
               $this->U_Model->update_Activation_Code($inserted_id, $activationcode);   
               //--load systememail model for sending email ot user for verification
               $this->load->model('Systememail_Model','SE_Model');   
               //get email template from admin
               $admin_email= $this->SE_Model->getAdminEmails();
               $mail_content= $this->SE_Model->getEmailById(1);   

               //Email Sending Code
               $this->load->library('email');
               $this->email->from($admin_email->value,'Wraters');
               $this->email->to($this->input->post('email'));
               $this->email->subject($mail_content->subject);
               $message = $mail_content->message;

              $emailPath = $this->config->item('base_abs_path')."/templates/".$this->config->item('base_template_dir');
               $email_template =  file_get_contents($emailPath.'/email/email.html');
               $message = str_replace("[[username]]", $this->input->post('username'), $message);
               $message = str_replace("[[email]]", $this->input->post('email'), $message);
               $message = str_replace("[[fullname]]",  $this->input->post('fname'), $message);
               $message = str_replace("[[password]]", $password, $message);
               $message = str_replace("[[link]]", $link, $message);
               $email_template = str_replace("[[EMAIL_HEADING]]", $mail_content->subject, $email_template);

	       //$email_template = str_replace("[[EMAIL_CONTENT]]", nl2br(utf8_encode($message)), $email_template);


               $email_template = str_replace("[[EMAIL_CONTENT]]",(utf8_encode($message)), $email_template);              
               $email_template = str_replace("[[footer]]", $this->config->item('base_site_name'), $email_template);
               $email_template = str_replace("[[SITEROOT]]", $this->config->item('base_url'), $email_template);
               $email_template = str_replace("[[LOGO]]",$this->config->item('base_url')."templates/".$this->config->item('base_template_dir'), $email_template);
              $this->email->message(html_entity_decode($email_template));

   /* echo "<br>msg=".$email_template; exit;*/
               if(!send_ses_mail($this->input->post('email'),$admin_email->value,'no-reply@wraters.com',$mail_content->subject,$email_template))
						$this->email->send();
						     
               //$this->phpsession->save('success_msg','User added successfully');
               $this->message->setMessage("User added!","SUCCESS");
               redirect($this->config->item('base_url').'admin/users');
               }
            }
         }
      }
          $id='';
         //$country_id='';
      $data = array(
                     'edit_id'      => '',
                     'displayname'	=> '',
                     'first_name'   => '',
                     'id'           =>'',
                     'last_name'    => '',
                     'avtar_image' => '',
                     'email'        => '',
                     'username'     => '',
                     'usertypeid'   => '',
                     'state'        => '',
                     'country'     =>'',
                     'city'         => '',
                     'zipcode'      => '',
                     'title'      =>'',
					 'username'     => '',
					 'homepage'     => '',
					 'newsletter'     => '',
					 'bio'      => ''
      );
         $country_id   = '';$state='';
      if($edit_id > 0){
         $user = $this->U_Model->getUserById($edit_id);
         $data = array(
                        'edit_id'      => $edit_id,
                        'displayname'   => $user->displayname,
                        'first_name'   => $user->first_name,
                        'last_name'    => $user->last_name,
                        'avtar_image'    => $user->avtar_image,
                        'email'        => $user->email,
                        'username'     => $user->username,
                        'country'   => $user->country_id,
                        'state'  => $user->state,
                        'city'         => $user->city, 
                        'usertypeid'   => $user->usertypeid,   
                        'zipcode'      => $user->zipcode,
                        'title'      =>$user->title,
						'username'     => $user->username,
						'homepage'     => $user->homepage,
						'newsletter'     => $user->newsletter,
						'bio'      => $user->bio
						
         );
      $country_id   = $user->country_id;
      $state   = $user->state;     
      }    
       $data['states'] = $this->S_Model->getStateComboByCountryId($state);
       $data['combo'] = $this->S_Model->getCountryRecordsByStatus($country_id);
       // set variable to show active menu 
      $data['menutab'] = 'network';
      $data['menuitem'] = 'users';
      $this->load->view($this->config->item('base_template_dir').'/admin/users_new',$data);
   }//end usersnew
   
   
    public function modifyfbuser($edit_id=0){
      if(!$this->phpsession->get('ciAdmId')){   redirect($this->config->item('base_url').'r/k/admin');   }
      
      if(strlen(trim($this->input->post('Submit'))) > 0){
         if($edit_id > 0){

          $this->load->helper("directory");
            $this->load->helper("upload_file");

            $picture_path = $this->input->post('sm_image1');
            if(strlen(trim($_FILES['avtar_image']['name'])) > 0)
            {
                  
                  $picture_path = @upload_file($_FILES['pimage'], "uploads/usersphoto", "usersphoto");
                  $picimage = time();//set image name
               
                  $this->IMGUPLD_Model->set_max_size(1000000);// Set Max Size
                  $this->IMGUPLD_Model->set_directory("./uploads/usersphoto"); // Set Directory

                  // Do not change following
                  $this->IMGUPLD_Model->set_tmp_name($_FILES['avtar_image']['tmp_name']);// Set Temp Name for upload, $_FILES['file']['tmp_name'] is automaticly get the temp name
                  $this->IMGUPLD_Model->set_file_size($_FILES['avtar_image']['size']);// Set file size, $_FILES['file']['size'] is automaticly get the size
                  $this->IMGUPLD_Model->set_file_type($_FILES['avtar_image']['type']);// Set File Type, $_FILES['file']['type'] is automaticly get the type
                  $this->IMGUPLD_Model->set_file_name($_FILES['avtar_image']['name']);// Set File Name, $_FILES['file']['name'] is automaticly get the file name.. you can change
                  $this->IMGUPLD_Model->start_copy();// Start Copy Process
                  $this->IMGUPLD_Model->resize(0,0);// If uploaded file is image, you can resize the image width and height // Support gif, jpg, png

                  $picture_path = $this->IMGUPLD_Model->set_thumbnail_name($picimage);

                   // 141 X 131
                  $this->IMGUPLD_Model->set_thumbnail_name("90/".$picimage);
                  $this->IMGUPLD_Model->create_thumbnail();
                  $this->IMGUPLD_Model->set_thumbnail_size(90,90);

                   $this->IMGUPLD_Model->set_thumbnail_name("100/".$picimage);
                  $this->IMGUPLD_Model->create_thumbnail();
                  $this->IMGUPLD_Model->set_thumbnail_size(100,100);
   
                   // 95 X 65
                        $this->IMGUPLD_Model->set_thumbnail_name("95/".$picimage);
                        $this->IMGUPLD_Model->create_thumbnail();
                         $this->IMGUPLD_Model->set_thumbnail_size(65, 95);
                    @unlink("./uploads/usersphoto/".$_FILES['avtar_image']['name']);
            }


               $dob = explode("-",$this->input->post('dob'));
            // $fdob = $dob[2]."-".$dob[0]."-".$dob[1];
            // update record
            $data = array(
                           'first_name'   => $this->input->post('fname'),
                           'last_name'    => $this->input->post('lname'),
                            'avtar_image' => $picture_path,
                           'email'        => $this->input->post('email'),
                           'username'     => $this->input->post('username'),
                           'base_password' => base64_encode($this->input->post('password')),
                           'usertypeid'   => 3,
                           'university'   => $this->input->post('university'),
                           'state'        => $this->input->post('state'),
                           'city'         => $this->input->post('city'),
                           'zipcode'      => $this->input->post('zipcode')
            );
            
            if(strlen(trim($this->input->post('password'))) > 0){
               $data = array_merge($data,array('password' => md5($this->input->post('password'))));
            }

            $this->U_Model->users_operations($data,'update',$edit_id);
            $this->phpsession->save('success_msg','User updated successfully');
            //$this->message->setMessage("User updated successfully","SUCCESS");
            redirect('admin/facebookusers');
         }else{



            // insert record
            if(strlen(trim($this->input->post('username'))) > 0 && strlen(trim($this->input->post('password'))) > 0){

                 if(strlen(trim($_FILES['avtar_image']['name'])) > 0)
            {
               $this->load->helper("directory");
               $this->load->helper("upload_file");
               $picture_path = upload_file($_FILES['avtar_image'], "uploads/usersphoto", "usersphoto");

                $picimage = time();//set image name
               
               $this->IMGUPLD_Model->set_max_size(1000000);// Set Max Size
               $this->IMGUPLD_Model->set_directory("./uploads/usersphoto"); // Set Directory

                     // Do not change following
               $this->IMGUPLD_Model->set_tmp_name($_FILES['avtar_image']['tmp_name']);// Set Temp Name for upload, $_FILES['file']['tmp_name'] is automaticly get the temp name
               $this->IMGUPLD_Model->set_file_size($_FILES['avtar_image']['size']);// Set file size, $_FILES['file']['size'] is automaticly get the size
               $this->IMGUPLD_Model->set_file_type($_FILES['avtar_image']['type']);// Set File Type, $_FILES['file']['type'] is automaticly get the type
               $this->IMGUPLD_Model->set_file_name($_FILES['avtar_image']['name']);// Set File Name, $_FILES['file']['name'] is automaticly get the file name.. you can change
               $this->IMGUPLD_Model->start_copy();// Start Copy Process
               $this->IMGUPLD_Model->resize(0,0);// If uploaded file is image, you can resize the image width and height // Support gif, jpg, png

               $picture_path = $this->IMGUPLD_Model->set_thumbnail_name($picimage);

                  // 90 X 90
               $this->IMGUPLD_Model->set_thumbnail_name("90/".$picimage);
               $this->IMGUPLD_Model->create_thumbnail();
               $this->IMGUPLD_Model->set_thumbnail_size(90,90);

                $this->IMGUPLD_Model->set_thumbnail_name("100/".$picimage);
                  $this->IMGUPLD_Model->create_thumbnail();
                  $this->IMGUPLD_Model->set_thumbnail_size(100,100);

                   // 95 X 65
                        $this->IMGUPLD_Model->set_thumbnail_name("95/".$picimage);
                        $this->IMGUPLD_Model->create_thumbnail();
                        $this->IMGUPLD_Model->set_thumbnail_size(65, 95);

                @unlink("./uploads/usersphoto/".$_FILES['avtar_image']['name']);
            }

               $dob = explode("-",$this->input->post('dob'));
               $data = array(
                              'first_name'   => $this->input->post('fname'),
                              'last_name'    => $this->input->post('lname'),
                              'avtar_image'  => $picture_path,
                              'email'        => $this->input->post('email'),
                              'username'     => $this->input->post('username'),
                              'password'     => md5($this->input->post('password')),
                              'base_password' => base64_encode($this->input->post('password')),
                              'usertypeid'   => 3,
                              'status'       => 'active',
                              'state'        => $this->input->post('state'),
                              'city'         => $this->input->post('city'),
                              'zipcode'      => $this->input->post('zipcode'),
                              'isverified'   => 'yes',
                              'verified_date'=> date("Y-m-d H:i:s")
               );
        // print_r($data);exit;

                

                        if($this->phpsession->get('inserted_id') == ""){ 
           
               
                  $inserted_id = $this->U_Model->users_operations($data,'addnew');

                 }else{

               $inserted_id = $this->U_Model->users_operations();

            } 

               if($inserted_id > 0){

                  $userinfo = $this->U_Model->getUsershortInfo($inserted_id);
               
                  
                  $this->load->model('SystemEmail_Model','SE_Model');
   
                  $admin_email= $this->SE_Model->getAdminEmails();
                  $mail_content= $this->SE_Model->getEmailById(1); // Welcome email
			// print_r($mail_content); exit;
                  //Email Sending Code
                  $this->load->library('email');
                  $this->email->from($admin_email->value,'Takeawayrestaurant');
                  $this->email->to($this->input->post('email'));
                  $this->email->subject($mail_content->subject);
   
                  // Edit email template   
                     
                  $message = str_replace("[[email]]",  $userinfo->email, $mail_content->message);
                  $message = str_replace("[[username]]", $userinfo->username, $message);
                  $content_message = str_replace("[[password]]", base64_decode($userinfo->base_password), $message);
   
                  $emailPath = $this->config->item('base_abs_path')."templates/".$this->config->item('base_template_dir');
                  $email_template =  file_get_contents($emailPath.'/email/email.html');
                  
                  $email_template = str_replace("[[EMAIL_HEADING]]", $mail_content->subject, $email_template);
                  $email_template = str_replace("[[EMAIL_CONTENT]]", nl2br(utf8_encode($content_message)), $email_template);
                  $email_template = str_replace("[[SITEROOT]]", $this->config->item('base_url'), $email_template);
                  $email_template = str_replace("[[LOGO]]",$this->config->item('base_url')."templates/".$this->config->item('base_template_dir'), $email_template);
   
                  $this->email->message(html_entity_decode(($email_template)));
   
               $this->email->send(); 
              // echo $this->email->print_debugger();exit;

                  $this->phpsession->save('success_msg','User added successfully');
                  redirect('admin/facebookusers');
               }
            }
         }
      }

      $data = array(
                     'edit_id'      => '',
                     'first_name'   => '',
                     'last_name'    => '',
                     'sm_image1' => '',
                     'email'        => '',
                     'username'     => '',
                     'usertypeid'   => '',
                     'university'  => '',
                     'state'        => '',
                     'city'         => '',
                     'zipcode'      => ''
      );
$country_id='';

      if($edit_id > 0){
         $user = $this->U_Model->getUserById($edit_id);
         $data = array(
                        'edit_id'      => $edit_id,
                        'first_name'   => $user->first_name,
                        'last_name'    => $user->last_name,
                        'sm_image1'    => $user->avtar_image,
                        'email'        => $user->email,
                        'username'     => $user->username,
                        'usertypeid'   => $user->usertypeid,
                        'state_selected'  => $user->state,
                        'city'         => $user->city,
                        'zipcode'      => $user->zipcode
         );

      }



      $this->load->model('State_Model','S_Model');

      $data['states'] = $this->S_Model->getStateRecords();

      $this->load->view($this->config->item('base_template_dir').'/admin/users_new',$data);
   }//end modifyfbuser
      
    public function addmodifyadminuser($edit_id=0){
       if(!$this->phpsession->get('ciAdmId')){   redirect($this->config->item('base_url').'r/k/admin');   }
      
      if(strlen(trim($this->input->post('Submit'))) > 0){
         if($edit_id > 0){

          $this->load->helper("directory");
            $this->load->helper("upload_file");

            $picture_path = $this->input->post('sm_image1');
            if(strlen(trim($_FILES['avtar_image']['name'])) > 0)
            {
                  
                  $picture_path = @upload_file($_FILES['pimage'], "uploads/usersphoto", "usersphoto");
                  $picimage = time();//set image name
               
                  $this->IMGUPLD_Model->set_max_size(1000000);// Set Max Size
                  $this->IMGUPLD_Model->set_directory("./uploads/usersphoto"); // Set Directory

                       // Do not change following
                  $this->IMGUPLD_Model->set_tmp_name($_FILES['avtar_image']['tmp_name']);// Set Temp Name for upload, $_FILES['file']['tmp_name'] is automaticly get the temp name
                  $this->IMGUPLD_Model->set_file_size($_FILES['avtar_image']['size']);// Set file size, $_FILES['file']['size'] is automaticly get the size
                  $this->IMGUPLD_Model->set_file_type($_FILES['avtar_image']['type']);// Set File Type, $_FILES['file']['type'] is automaticly get the type
                  $this->IMGUPLD_Model->set_file_name($_FILES['avtar_image']['name']);// Set File Name, $_FILES['file']['name'] is automaticly get the file name.. you can change
                  $this->IMGUPLD_Model->start_copy();// Start Copy Process
                  $this->IMGUPLD_Model->resize(0,0);// If uploaded file is image, you can resize the image width and height // Support gif, jpg, png

                  $picture_path = $this->IMGUPLD_Model->set_thumbnail_name($picimage);

                   // 141 X 131
                  $this->IMGUPLD_Model->set_thumbnail_name("90/".$picimage);
                  $this->IMGUPLD_Model->create_thumbnail();
                  $this->IMGUPLD_Model->set_thumbnail_size(90,90);


                   $this->IMGUPLD_Model->set_thumbnail_name("100/".$picimage);
                  $this->IMGUPLD_Model->create_thumbnail();
                  $this->IMGUPLD_Model->set_thumbnail_size(100,100);

                   // 95 X 65
                        $this->IMGUPLD_Model->set_thumbnail_name("95/".$picimage);
                        $this->IMGUPLD_Model->create_thumbnail();
                         $this->IMGUPLD_Model->set_thumbnail_size(65, 95);

                    @unlink("./uploads/usersphoto/".$_FILES['avtar_image']['name']);
            }


               $dob = explode("-",$this->input->post('dob'));
            // $fdob = $dob[2]."-".$dob[0]."-".$dob[1];
            // update record
            $data = array(
                           'first_name'   => $this->input->post('fname'),
                           'last_name'    => $this->input->post('lname'),
                           'avtar_image'      => $picture_path,
                           'email'        => $this->input->post('email'),
                           'username'     => $this->input->post('username'),
                           'base_password' => base64_encode($this->input->post('password')),
                           'usertypeid'   => 1,
                           'signup_date'  => date("Y-d-d H:i:s"),
                           'state'        => $this->input->post('state'),
                           'city'         => $this->input->post('city'),
                           'zipcode'      => $this->input->post('zipcode')
            );
            
            if(strlen(trim($this->input->post('password'))) > 0){
               $data = array_merge($data,array('password' => md5($this->input->post('password'))));
            }

            $this->U_Model->users_operations($data,'update',$edit_id);
            $this->phpsession->save('success_msg','User updated successfully');
            redirect('admin/adminusers');
         }else{



            // insert record
          
            if(strlen(trim($this->input->post('username'))) > 0 && strlen(trim($this->input->post('password'))) > 0){

                 if(strlen(trim($_FILES['avtar_image']['name'])) > 0)
            {
               $this->load->helper("directory");
               $this->load->helper("upload_file");
               $picture_path = upload_file($_FILES['avtar_image'], "uploads/usersphoto", "usersphoto");

                $picimage = time();//set image name
               
               $this->IMGUPLD_Model->set_max_size(1000000);// Set Max Size
               $this->IMGUPLD_Model->set_directory("./uploads/usersphoto"); // Set Directory

                     // Do not change following
               $this->IMGUPLD_Model->set_tmp_name($_FILES['avtar_image']['tmp_name']);// Set Temp Name for upload, $_FILES['file']['tmp_name'] is automaticly get the temp name
               $this->IMGUPLD_Model->set_file_size($_FILES['avtar_image']['size']);// Set file size, $_FILES['file']['size'] is automaticly get the size
               $this->IMGUPLD_Model->set_file_type($_FILES['avtar_image']['type']);// Set File Type, $_FILES['file']['type'] is automaticly get the type
               $this->IMGUPLD_Model->set_file_name($_FILES['avtar_image']['name']);// Set File Name, $_FILES['file']['name'] is automaticly get the file name.. you can change
               $this->IMGUPLD_Model->start_copy();// Start Copy Process
               $this->IMGUPLD_Model->resize(0,0);// If uploaded file is image, you can resize the image width and height // Support gif, jpg, png

               $picture_path = $this->IMGUPLD_Model->set_thumbnail_name($picimage);

                  // 90 X 90
               $this->IMGUPLD_Model->set_thumbnail_name("90/".$picimage);
               $this->IMGUPLD_Model->create_thumbnail();
               $this->IMGUPLD_Model->set_thumbnail_size(90,90);

                $this->IMGUPLD_Model->set_thumbnail_name("100/".$picimage);
                  $this->IMGUPLD_Model->create_thumbnail();
                  $this->IMGUPLD_Model->set_thumbnail_size(100,100);

                // 95 X 65
                        $this->IMGUPLD_Model->set_thumbnail_name("95/".$picimage);
                        $this->IMGUPLD_Model->create_thumbnail();
                         $this->IMGUPLD_Model->set_thumbnail_size(65, 95);     

                @unlink("./uploads/usersphoto/".$_FILES['avtar_image']['name']);
            }

              


               $dob = explode("-",$this->input->post('dob'));
            // $dob = $dob[2]."-".$dob[0]."-".$dob[1];
               $data = array(
                              'first_name'   => $this->input->post('fname'),
                              'last_name'    => $this->input->post('lname'),
                              'avtar_image'      => $picture_path,
                              'email'        => $this->input->post('email'),
                              'username'     => $this->input->post('username'),
                              'password'     => md5($this->input->post('password')),
                              'base_password' => base64_encode($this->input->post('password')),
                              'usertypeid'   => 1,
                              'status'       => 'active',
                              'state'        => $this->input->post('state'),
                              'city'         => $this->input->post('city'),
                              'zipcode'      => $this->input->post('zipcode'),
                              'isverified'   => 'yes',
                              'verified_date'=> date("Y-m-d H:i:s")
               );
        // print_r($data);exit;               

                        if($this->phpsession->get('inserted_id') == ""){ 
           
               
                  $inserted_id = $this->U_Model->users_operations($data,'addnew');

                 }else{

               $inserted_id = $this->U_Model->users_operations();

            } 

               if($inserted_id > 0){

                  $userinfo = $this->U_Model->getUsershortInfo($inserted_id);
               
                  
                  $this->load->model('SystemEmail_Model','SE_Model');
   
                  $admin_email= $this->SE_Model->getAdminEmails();
                  $mail_content= $this->SE_Model->getEmailById(1); // Welcome email
   
                  //Email Sending Code
                  $this->load->library('email');
                  $this->email->from($admin_email->value,'fashionesia');
                  $this->email->to($this->input->post('email'));
                  $this->email->subject($mail_content->subject);
   
                  // Edit email template   
                     
                  $message = str_replace("[[email]]",  $userinfo->email, $mail_content->message);
                  $message = str_replace("[[username]]", $userinfo->username, $message);
                  $content_message = str_replace("[[password]]", base64_decode($userinfo->base_password), $message);
   
                  $emailPath = $this->config->item('base_abs_path')."templates/".$this->config->item('base_template_dir');
                  $email_template =  file_get_contents($emailPath.'/email/email.html');
                  
                  $email_template = str_replace("[[EMAIL_HEADING]]", $mail_content->subject, $email_template);
                  $email_template = str_replace("[[EMAIL_CONTENT]]", nl2br(utf8_encode($content_message)), $email_template);
                  $email_template = str_replace("[[SITEROOT]]", $this->config->item('base_url'), $email_template);
                  $email_template = str_replace("[[LOGO]]",$this->config->item('base_url')."templates/".$this->config->item('base_template_dir'), $email_template);
   
                  $this->email->message(html_entity_decode(($email_template)));
   
               $this->email->send(); 
              // echo $this->email->print_debugger();exit;

                  $this->phpsession->save('success_msg','User added successfully');
                  redirect('admin/adminusers');
               }
            }
         }
      }

      $data = array(
                     'edit_id'      => '',
                     'first_name'   => '',
                     'last_name'    => '',
                     'sm_image1' => '',
                     'email'        => '',
                     'username'     => '',
                     'usertypeid'   => '',
                     'university'  => '',      
                     'state'        => '',
                     'city'         => '',
                     'zipcode'      => ''
      );

      if($edit_id > 0){
         $user = $this->U_Model->getUserById($edit_id);
         $data = array(
                        'edit_id'      => $edit_id,
                        'first_name'   => $user->first_name,
                        'last_name'    => $user->last_name,
                        'sm_image1'    => $user->avtar_image,
                        'email'        => $user->email,
                        'username'     => $user->username,
                        'usertypeid'   => $user->usertypeid,
                        'state_selected'  => $user->state,
                        'city'         => $user->city,
                        'zipcode'      => $user->zipcode
         );
      }

      $this->load->model('State_Model','S_Model');

      $data['states'] = $this->S_Model->getStateRecords();

      $this->load->view($this->config->item('base_template_dir').'/admin/subadmin_new',$data);
   }//end addmodifyadminuser
   

   #----------------------------------------
   // admin site cms listing
   #----------------------------------------

   public function cms(){
      if(!$this->phpsession->get('ciAdmId')){   redirect($this->config->item('base_url').'r/k/admin');   }
      $this->load->model('Cms_Model');
      $this->load->helper(array('pagination'));
      $array = $this->uri->uri_to_assoc(3);
      $pages = (@$array['pages']?$array['pages']:1);
      $page = (@$array['page']?$array['page']:1);

      if(strlen(trim($this->input->post('submit'))) > 0){
            $data = array();
            $page_ids = @implode(",",$this->input->post('pageid'));
            $action =  $this->input->post('action');
            $message = $this->Cms_Model->cmsOperations(array('page_ids' => $page_ids),$action);
            //$this->phpsession->save('success_msg',$message);
            $this->message->setMessage($message,"SUCCESS");
      }

      $PAGE = $page;
      $PAGE_LIMIT = 20;
      $DISPLAY_PAGES = 25;
      $PAGE_LIMIT_VALUE = ($PAGE - 1) * $PAGE_LIMIT;

      $data = array('cmspages' => '', 'PAGING' => '','helpcategory'=>'',);
      // Get posted search value in variables

      // Count total cms records
      $total = $this->Cms_Model->countCmsRecords();
      $PAGE_TOTAL_ROWS = $total;
      $PAGE_URL = $this->config->item('base_url').'admin/cms';
      $data['PAGING'] = pagination_assoc($PAGE_TOTAL_ROWS,$PAGE_LIMIT,$DISPLAY_PAGES,$PAGE_URL,$page,$pages);
      // Pagination end
     
      $data['cmspages'] = $this->Cms_Model->getCmsRecords($PAGE_LIMIT_VALUE,$PAGE_LIMIT);

      // set variable to show active menu 
      $data['menutab'] = 'content';
      $data['menuitem'] = 'contentpages';
      $this->load->view($this->config->item('base_template_dir').'/admin/cms/cms', $data);
   }//end cms

   #----------------------------------------
   // admin site cms add modify
   #----------------------------------------

   public function cmsaddmodify($edit_id=0){ 
       if(!$this->phpsession->get('ciAdmId')){   redirect($this->config->item('base_url').'r/k/admin');   }
      $this->load->model('Cms_Model');

      if(strlen(trim($this->input->post('Submit'))) > 0){ //echo "hello";exit;
         if($edit_id > 0){
            // update record
            $data = array(
                          // 'help_id' => $this->input->post('help_id'),
                         //  'sub_cat_help_id' => $this->input->post('sub_cat_help_id'),
                           'title'  => $this->input->post('title'),
                           'description'     => $this->input->post('descriptions')
                          
            );
            $this->Cms_Model->cmsOperations($data,'update',$edit_id);
            $this->message->setMessage("Contents updated successfully","SUCCESS");
             redirect('admin/cms');
         }else{
            // insert record
            $data = array(
                           // 'help_id' => $this->input->post('help_id'),
                          //  'sub_cat_help_id' => $this->input->post('sub_cat_help_id'),
                           'title'  => $this->input->post('title'),
                           'description'     => $this->input->post('descriptions')
            );//print_r($data);exit;
            $inserted_id = $this->Cms_Model->cmsOperations($data,'addnew');
            if($inserted_id > 0){
                $this->message->setMessage("Contents added successfully","SUCCESS");
                redirect('admin/cms');
            }
         }
      }

      $data = array(
                     'edit_id'      => '',
                     //'help_id' =>'',
                    // 'sub_cat_help_id'=>'',
                     'title' => '',
                     'description' => '',
                     'helpcategory'=>''
      );
      
    $help_id='';
    $sub_cat_help_id='';

      if($edit_id > 0){ 
         $cms = $this->Cms_Model->getCmsById($edit_id);
         $data = array(
                        'edit_id'      => $edit_id,
                       // 'help_id'        => $cms->help_id,
                       // 'sub_cat_help_id'   => $cms->sub_cat_help_id,
                        'title'        => $cms->title,
                        'description'  => $cms->description,
                        'status'       => $cms->status
         );
     //  $help_id=$cms->help_id;
     // $sub_cat_help_id=$cms->sub_cat_help_id;
      }

     /* $fckeditorConfig = array(
                              'instanceName' => 'descriptions',
                              'BasePath' => $this->config->item('base_url').'system/plugins/fckeditor/',
                              'ToolbarSet' => 'Default',
                              'Width' => '100%',
                              'Height' => '400',
                              'Value' => $data['description']
      );*/

     // $this->load->library('fckeditor', $fckeditorConfig);   
		// set variable to show active menu 
      $data['menutab'] = 'content';
      $data['menuitem'] = 'contentpages';
      $this->load->view($this->config->item('base_template_dir').'/admin/cms/add_modify',$data);
   }//end cmsaddmodify

   #----------------------------------------
   // admin site update paypal settings
   #----------------------------------------

 
  

   public function deletedusers($userid=0){
       if(!$this->phpsession->get('ciAdmId')){   redirect($this->config->item('base_url').'r/k/admin');   }

      if($userid > 0){
         //resend verification mail to user
         $___user = $this->U_Model->getUserById($userid);

         $activationcode = $___user->activationcode;
         $verification_code=$userid * 32765;
         // Create link for verification
         $link = "<a href='".$this->config->item('base_url')."register/registerverify/".$activationcode."'>".$this->config->item('base_url')."register/registerverify/".$activationcode."</a>";

         $this->load->model('SystemEmail_Model','SE_Model');

         $admin_email= $this->SE_Model->getAdminEmails();
         $mail_content= $this->SE_Model->getEmailById(1);
         
         //Email Sending Code
         $this->load->library('email');
         $this->email->from($admin_email->value,'fashionesia');
         $this->email->to($___user->email);  // 
         $this->email->subject($mail_content->subject);

         $message = str_replace("[link]", $link, $mail_content->message);
         $message = str_replace("[[username]]", $___user->first_name, $message);
         $message = str_replace("[[email]]", $___user->email, $message);
         
         $content_message = str_replace("[sitename]", $this->config->item('base_site_name'), $message);

         $emailPath = $this->config->item('base_abs_path')."templates/".$this->config->item('base_template_dir');
         $email_template =  file_get_contents($emailPath.'/email/email.html');
         

         $email_template = str_replace("[[EMAIL_HEADING]]", $mail_content->subject, $email_template);
         $email_template = str_replace("[[EMAIL_CONTENT]]", nl2br(utf8_encode($content_message)), $email_template);
         $email_template = str_replace("[[SITEROOT]]", $this->config->item('base_url'), $email_template);
         $email_template = str_replace("[[LOGO]]",$this->config->item('base_url')."templates/".$this->config->item('base_template_dir'), $email_template);

         $this->email->message(html_entity_decode(($email_template)));

         //echo html_entity_decode(($email_template));

         $this->email->send();

         $this->phpsession->save('success_msg',"Verification email successfully send to '".$___user->first_name." ".$___user->last_name."'.");

         redirect($this->config->item('base_url')."admin/users/");
         
      }

      $data = array('users' => '', 'PAGING' => '', 'search' => '-','delete'=>'-','fn'=>'-','status'=>'-');
      $this->load->helper(array('pagination'));
      $array = $this->uri->uri_to_assoc(3);

      $pages = (@$array['pages']?$array['pages']:1);
      $page = (@$array['page']?$array['page']:1);

      $orderb = (@$array['orderby']?@$array['orderby']:"asc"); $data['orderby']  = $orderb;
      $fn = (@$array['fn']?@$array['fn']:"first_name");        $data['fn']       = $fn;
      $status = (@$array['status']?@$array['status']:"-");     $data['status']       = $status;  
      $orderby = $fn." ".$orderb;

      $data['search'] = (@$array['search']?$array['search']:'-');
      $data['delete'] = (@$array['delete']?$array['delete']:'-');

      
      if(strlen(trim($this->input->post('submit'))) > 0){
            $user_ids = implode(",",$this->input->post('checbox_ids'));
            $action =  $this->input->post('action');
            if($action=='delete'){$action = 'permanentdelete';}
            $message = $this->U_Model->users_operations(array('user_ids' => $user_ids),$action);
            //$this->phpsession->save('success_msg',$message);
            $this->message->setMessage($message,"SUCCESS");
      }

      $PAGE = $page;
      $PAGE_LIMIT = $this->U_Model->countDeletedUsersBySearch($data['search'],$data['delete'],$status); //20;
      $DISPLAY_PAGES = 25;
      $PAGE_LIMIT_VALUE = ($PAGE - 1) * $PAGE_LIMIT;

      if($this->input->post('delete')!='')
      {
//          $delete = explode("-",$this->input->post('datepicker'));
//          $delete_date = $delete[2]."-".$delete[0]."-".$delete[1];
         $data['delete'] =$this->input->post('delete');
      }
      // Get posted search value in variables
      $data['search'] = ($this->input->post('search')?trim($this->input->post('search')):$data['search']);
      $data['delete'] = ($this->input->post('delete')?trim($this->input->post('delete')):$data['delete']);
      
      // Count total users
      $total = $this->U_Model->countDeletedUsersBySearch($data['search'],$data['delete'],$status);
      
      $PAGE_TOTAL_ROWS = $total;
      $PAGE_URL = $this->config->item('base_url').'admin/adminusers/fn/'.$fn.'/orderby/'.$orderb.'/search/'.$data['search'].'/delete/'.$data['delete'].'/status/'.$status;
      $data['PAGING'] = pagination_assoc($PAGE_TOTAL_ROWS,$PAGE_LIMIT,$DISPLAY_PAGES,$PAGE_URL,$page,$pages);
      // Pagination end      
      // Get all users
      $data['users'] = $this->U_Model->deletedusers($PAGE_LIMIT_VALUE,$PAGE_LIMIT,$data['search'],$orderby,$data['delete'],$status);  
      // set variable to show active menu 
      $data['menutab'] = 'network';
      $data['menuitem'] = 'deletedusers'; 
      $this->load->view($this->config->item('base_template_dir').'/admin/users_del', $data);
      
   }//end adminusers

   // Show all facebookusers at admin side
   #----------------------------------------
   public function facebookusers($userid=0){
       if(!$this->phpsession->get('ciAdmId')){   redirect($this->config->item('base_url').'r/k/admin');   }

      if($userid > 0){
         //resend verification mail to user
         $___user = $this->U_Model->getUserById($userid);

         $activationcode = $___user->activationcode;
         $verification_code=$userid * 32765;
         // Create link for verification
         $link = "<a href='".$this->config->item('base_url')."register/registerverify/".$activationcode."'>".$this->config->item('base_url')."register/registerverify/".$activationcode."</a>";

         $this->load->model('SystemEmail_Model','SE_Model');

         $admin_email= $this->SE_Model->getAdminEmails();
         $mail_content= $this->SE_Model->getEmailById(1);
         
         //Email Sending Code
         $this->load->library('email');
         $this->email->from($admin_email->value,'Takeawayrestaurant');
         $this->email->to($___user->email);  // 
         $this->email->subject($mail_content->subject);

         $message = str_replace("[link]", $link, $mail_content->message);
         $message = str_replace("[[username]]", $___user->first_name, $message);
         $message = str_replace("[[email]]", $___user->email, $message);
         
         $content_message = str_replace("[sitename]", $this->config->item('base_site_name'), $message);

         $emailPath = $this->config->item('base_abs_path')."templates/".$this->config->item('base_template_dir');
         $email_template =  file_get_contents($emailPath.'/email/email.html');
         

         $email_template = str_replace("[[EMAIL_HEADING]]", $mail_content->subject, $email_template);
         $email_template = str_replace("[[EMAIL_CONTENT]]", nl2br(utf8_encode($content_message)), $email_template);
         $email_template = str_replace("[[SITEROOT]]", $this->config->item('base_url'), $email_template);
         $email_template = str_replace("[[LOGO]]",$this->config->item('base_url')."templates/".$this->config->item('base_template_dir'), $email_template);

         $this->email->message(html_entity_decode(($email_template)));

         //echo html_entity_decode(($email_template));

         $this->email->send();

         $this->phpsession->save('success_msg',"Verification email successfully send to '".$___user->first_name." ".$___user->last_name."'.");

         redirect($this->config->item('base_url')."admin/users/");
         
      }

      $data = array('users' => '', 'PAGING' => '', 'search' => '-','signup'=>'-');
      $this->load->helper(array('pagination'));
      $array = $this->uri->uri_to_assoc(3);

      $pages = (@$array['pages']?$array['pages']:1);
      $page = (@$array['page']?$array['page']:1);

//       $orderb = (@$array['orderby']?@$array['orderby']:"asc");
//       $fn = (@$array['fn']?@$array['fn']:"first_name");
//       $orderby = $fn." ".$orderb;
        
      $orderb = (@$array['orderby']?@$array['orderby']:"asc");             $data['orderby']      = $orderb;
      $fn = (@$array['fn']?@$array['fn']:"first_name");                    $data['fn']           = $fn;
      $status = (@$array['status']?@$array['status']:"-");                 $data['status']       = $status;  
      $isverified = (@$array['isverified']?@$array['isverified']:"-");     $data['isverified']   = $isverified;    
      $orderby = $fn." ".$orderb;

      $data['search'] = (@$array['search']?$array['search']:'-');
      $data['signup'] = (@$array['signup']?$array['signup']:'-');

//       $data['search'] = (@$array['search']?$array['search']:'-');
//       $data['signup'] = (@$array['signup']?$array['signup']:'-');
      $data['search'] = ($this->input->post('search')?trim($this->input->post('search')):$data['search']);
      $data['signup']= ($this->input->post('signup')?trim($this->input->post('signup')):$data['signup']);

      
      if(strlen(trim($this->input->post('submit'))) > 0){
            $user_ids = implode(",",$this->input->post('checbox_ids'));
            $action =  $this->input->post('action');
            $message = $this->U_Model->users_operations(array('user_ids' => $user_ids),$action);
            $this->phpsession->save('success_msg',$message);
      }

      $PAGE = $page;
      $PAGE_LIMIT = 20;
      $DISPLAY_PAGES = 25;
      $PAGE_LIMIT_VALUE = ($PAGE - 1) * $PAGE_LIMIT;

      if($this->input->post('signup')!='')
      {
//          $signup = explode("-",$this->input->post('datepicker'));
//          $signup_date = $signup[2]."-".$signup[0]."-".$signup[1];
         $data['signup'] = $this->input->post('signup');
      }
      // Get posted search value in variables
      $data['search'] = ($this->input->post('search')?trim($this->input->post('search')):$data['search']);
      $data['signup'] = ($this->input->post('signup')?trim($this->input->post('signup')):$data['signup']);
      
      // Count total users
      $total = $this->U_Model->countFbUsersBySearch($data['search'],$data['signup'],$status,$isverified);
      
      $PAGE_TOTAL_ROWS = $total;
      //$PAGE_URL = $this->config->item('base_url').'admin/facebookusers/search/'.$data['search'].'/signup/'.$data['signup'];
      $PAGE_URL = $this->config->item('base_url').'admin/facebookusers/fn/'.$fn.'/orderby/'.$orderb.'/search/'.$data['search'].'/signup/'.$data['signup'].'/status/'.$status.'/isverified/'.$isverified;
      $data['PAGING'] = pagination_assoc($PAGE_TOTAL_ROWS,$PAGE_LIMIT,$DISPLAY_PAGES,$PAGE_URL,$page,$pages);
      // Pagination end
      
      // Get all users
//       $data['users'] = $this->U_Model->fbusers($PAGE_LIMIT_VALUE,$PAGE_LIMIT,$data['search'],$orderby,$data['signup']);
              $data['users'] = $this->U_Model->fbusers($PAGE_LIMIT_VALUE,$PAGE_LIMIT,$data['search'],$orderby,$data['signup'],$status,$isverified);
   
      $this->load->view($this->config->item('base_template_dir').'/admin/fbusers', $data);

   }//end facebookusers



    

#--------------metatags----------------------------------


 public function metatags()
   {
      if($this->phpsession->get('ciAdmId') < 1) redirect('r/k/admin');
      $data['seolist'] = $this->MT_Model->getlist();
      
      // set variable to show active menu 
      $data['menutab'] = 'setting';
      $data['menuitem'] = 'metatags';
       $this->load->view($this->config->item('base_template_dir').'/admin/seolist', $data);
   }

   public function metaaddmodify($edit_id=0)
   {
       if(!$this->phpsession->get('ciAdmId')){   redirect($this->config->item('base_url').'r/k/admin');   }
      $this->load->model('metatags_Model', 'MT_Model');

      if(strlen(trim($this->input->post('Submit'))) > 0)
      {
         if($edit_id>0)
		 {
			 $data = array(
			 'pagename'=>trim($this->input->post('pagename')),
			 'title'  => $this->input->post('title'),
			 'keywords'=> $this->input->post('keywords'),
			 'description'=>$this->input->post('description')
			 );
	
			 $this->MT_Model->metaOperations($data,'update',$edit_id);
			 
			 $this->message->setMessage("Meta updated successfully","SUCCESS");
			 redirect('./admin/metatags/');
		}
		else
		{
			 $data = array(
			 'pagename'=>trim($this->input->post('pagename')),
			 'title'  => $this->input->post('title'),
			 'keywords'=> $this->input->post('keywords'),
			 'description'=>$this->input->post('description')
			 );
	
			 $this->MT_Model->metaOperations($data,'addnew');
			 
		}         
      }
      $data = array(
      'edit_id'      => '',
      'pagename'=>'',
      'title' => '',
      'keywords'=>'',
      'description' => ''                    
      );

		if($edit_id>0)
		{
			  $meta = $this->MT_Model->getMetaById($edit_id);
			  $data = array(
			  'edit_id'      => $edit_id,
			  'pagename'=>$meta->pagename,
			  'title'        => $meta->title,
			  'keywords' => $meta->keywords,
			  'description'  => $meta->description
		
			  );
		}
// set variable to show active menu 
      $data['menutab'] = 'setting';
      $data['menuitem'] = 'metatags';
      $this->load->view($this->config->item('base_template_dir').'/admin/metaaddmodify',$data);
   }//end metaaddmodify
    public function paymentgateway()
   {
    if(!$this->phpsession->get('ciAdmId')){   redirect($this->config->item('base_url').'r/k/admin');   }
      $this->load->model('Paymentgateway_Model', 'PG_Model');   
      if($this->phpsession->get('ciAdmId') < 1) redirect('r/k/admin');
      $data['paydetails'] = $this->PG_Model->getGateWayDetails();       
       if(strlen(trim($this->input->post('submit'))) > 0)
      {
         if($this->input->post('user_type')=='live'){
         $instdata = array(
         'live_username'  => $this->input->post('live_user'),
         'live_password'=> $this->input->post('live_password'),
         'live_signature'=>$this->input->post('live_signature'),
         'test_live'=>'Live'           
         );
         }
         if($this->input->post('user_type')=='test'){
         $instdata = array(
         'test_username'  => $this->input->post('autho_login'),
         'test_password'=> $this->input->post('password'),
         'test_signature'=>$this->input->post('email'),
         'test_live'=>'Test'           
         );
         }
         $this->PG_Model->gatewayOperations($instdata,'update',1);
         $this->phpsession->save('success_msg','Payment gateway updated.');
         redirect('admin/paymentgateway');         
      }         
       $this->load->view($this->config->item('base_template_dir').'/admin/paymentgateway', $data);
   }   


       // check user more informatin and billing infromation

       
   function userinfo($edit_id=0)
         {
           if(!$this->phpsession->get('ciAdmId')){   redirect($this->config->item('base_url').'r/k/admin');   }

                if($edit_id > 0){
					$user = $this->U_Model->getUserById($edit_id);
					$data = array(
										'edit_id'      => $edit_id,
										'first_name'   => $user->first_name,
										'last_name'    => $user->last_name,
										'sm_image1'    => $user->avtar_image,
										'email'        => $user->email,
										'username'     => $user->username,
										'gender'  => $user->gender,
										'usertypeid'   => $user->usertypeid,
										'country'   => $user->country_id,
										'university'  => $user->university,
										'address_two'  => $user->address_two,
										'state'  => $user->state,
										'city'         => $user->city,
										'bio'         => $user->bio,
										'zipcode'      => $user->zipcode
					);
				$country_id   = $user->country_id;
				$state   = $user->state;      
            } 

           $this->load->view($this->config->item('base_template_dir').'/admin/user_billing');


         }


         public function sitesettings(){

           if(!$this->phpsession->get('ciAdmId')){   redirect($this->config->item('base_url').'r/k/admin');   }
           
            $sitesettings = $this->SS_Model->getSiteSettingsDetails();
  
			$data = array(
               'SITETITLE'=> $sitesettings[2],
               'FROM_EMAIL'=>$sitesettings[3],
               'EMAIL_FROM'=>$sitesettings[4],
               'SITE_EMAIL'=>$sitesettings[5],//,
               'SIGN_UP_TEXT'=>$sitesettings[8],
               'WRATED_FEES'=>$sitesettings[9],
               'WRATED_BENEFITS'=>$sitesettings[10],
               'PAYPAL_EMAIL'=>$sitesettings[6],
               'VISIT_INTERVAL'=>$sitesettings[12],
               'WRITERS_TEXT'=>$sitesettings[14],
			   'RVDISCOUNT'=>$sitesettings[15],
			   
			   'FACEBOOK_APPID'=>$sitesettings[16],
			   'FACEBOOK_SECRET'=>$sitesettings[17],
			   'TWITTER_KEY'=>$sitesettings[18],
			   'TWITTER_SECRET'=>$sitesettings[19]
               //'SITEMODE'=>$sitesettings[6],
               //'offlinecontent'=>$sitesettings[7]
              );
            
        if(strlen(trim($this->input->post('Submit'))) > 0){
               $this->SS_Model->siteSettingsUpdate(array('value'=> $this->input->post('SITETITLE')),3);
               $this->SS_Model->siteSettingsUpdate(array('value'=> $this->input->post('FROM_EMAIL')),7);
               $this->SS_Model->siteSettingsUpdate(array('value'=> $this->input->post('EMAIL_FROM')),5);
               $this->SS_Model->siteSettingsUpdate(array('value'=> $this->input->post('SITE_EMAIL')),6);
               $this->SS_Model->siteSettingsUpdate(array('value'=> $this->input->post('SIGN_UP_TEXT')),10);
               $this->SS_Model->siteSettingsUpdate(array('value'=> $this->input->post('WRATED_FEES')),11);
               $this->SS_Model->siteSettingsUpdate(array('value'=> $this->input->post('WRATED_BENEFITS')),12);
               $this->SS_Model->siteSettingsUpdate(array('value'=> $this->input->post('PAYPAL_EMAIL')),8);
               $this->SS_Model->siteSettingsUpdate(array('value'=> $this->input->post('VISIT_INTERVAL')),14);
               $this->SS_Model->siteSettingsUpdate(array('value'=> $this->input->post('WRITERS_TEXT')),16);
			    $this->SS_Model->siteSettingsUpdate(array('value'=> $this->input->post('RVDISCOUNT')),17);
			    
			    $this->SS_Model->siteSettingsUpdate(array('value'=> $this->input->post('FACEBOOK_APPID')),18);
			    $this->SS_Model->siteSettingsUpdate(array('value'=> $this->input->post('FACEBOOK_SECRET')),19);
			    $this->SS_Model->siteSettingsUpdate(array('value'=> $this->input->post('TWITTER_KEY')),20);
			    $this->SS_Model->siteSettingsUpdate(array('value'=> $this->input->post('TWITTER_SECRET')),21);
                                             
               $this->message->setMessage("Site settings updated.","SUCCESS");
               redirect($this->config->item('base_url').'admin/sitesettings');
        }       
              
              // set variable to show active menu 
      $data['menutab'] = 'setting';
      $data['menuitem'] = 'sitesetting';
     $this->load->view($this->config->item('base_template_dir').'/admin/sitesettings',$data);         
     }  
	 public function adminupdate($edit_id = 0)
        {
                if (!$this->phpsession->get('ciAdmId')) {
                        redirect($this->config->item('base_url') . 'r/k/admin');
                } //!$this->phpsession->get( 'ciAdmId' )
                if (strlen(trim($this->input->post('Submit'))) > 0) {
                        $insert_cat = array();
                        if ($edit_id > 0) {
                                //update
                                $data = array(
                                        'email' => $this->input->post('email'),
                                        'username' => $this->input->post('username')
                                        );
                                if (strlen(trim($this->input->post('password'))) > 0) {
                                        $data = array_merge($data, array(
                                                'password' => md5($this->input->post('password')),
                                                'base_password' => base64_encode($this->input->post('password'))
                                        ));
                                } //strlen
                                $this->U_Model->users_operations($data, 'update', $edit_id);
                                //$this->phpsession->save('success_msg', 'Admin Details updated successfully');
                                $this->message->setMessage("Information updated successfully","SUCCESS");
                                redirect('admin/adminupdate/' . $edit_id);
                        } //$edit_id > 0
                } //if
                $id         = '';
                $data       = array(
                        'edit_id' => '',
                        'email' => '',
                        'username' => ''
                        );
                $country_id = '';
                $state      = '';
                if ($edit_id > 0) {
                        $user = $this->U_Model->getUserById($edit_id);
                        $data = array(
                                'edit_id' => $edit_id,
                                'email' => $user->email,
                                'username' => $user->username
                                );
                } //if
                $data['menutab']  = 'network';
                $data['menuitem'] = 'admin';
                $this->load->view($this->config->item('base_template_dir') . '/admin/adminupdate', $data);
        }


// Industrial Professionals 
public function industrialprofessional($userid=0){
	  if(!$this->phpsession->get('ciAdmId')){   redirect($this->config->item('base_url').'r/k/admin');   }
      $data = array('users' => '', 'PAGING' => '', 'search' => '-','signup'=>'-','orderby'=>'-','fn'=>'-','status'=>'-');
      $this->load->helper(array('pagination'));
      $array = $this->uri->uri_to_assoc(3);

      $pages = (@$array['pages']?$array['pages']:1);
      $page = (@$array['page']?$array['page']:1);

      $orderb = (@$array['orderby']?@$array['orderby']:"asc");             $data['orderby']      = $orderb;
      $fn = (@$array['fn']?@$array['fn']:"first_name");                    $data['fn']           = $fn;
      $status = (@$array['status']?@$array['status']:"-");                 $data['status']       = $status;  
      $isverified = (@$array['isverified']?@$array['isverified']:"-");     $data['isverified']   = $isverified;    
      $orderby = $fn." ".$orderb;

      $data['search'] = (@$array['search']?$array['search']:'-');
      $data['signup'] = (@$array['signup']?$array['signup']:'-');

      
      if(strlen(trim($this->input->post('submit'))) > 0){ 
            $user_ids = implode(",",$this->input->post('checbox_ids'));
           $action =  $this->input->post('action');  
           
            $message = $this->U_Model->users_operations(array('user_ids' => $user_ids),$action);
             //echo  $this->db->last_query();exit;
           $this->message->setMessage($message,"SUCCESS");
           // $this->phpsession->save('success_msg',$message);
      }

      $PAGE = $page;
      $PAGE_LIMIT =  $this->U_Model->countUsersBySearch($data['search'],$data['signup'],$status,$isverified); //20;
      $DISPLAY_PAGES = 25;
      $PAGE_LIMIT_VALUE = ($PAGE - 1) * $PAGE_LIMIT;

      if($this->input->post('signup')!='')
      {
       $data['signup'] = $this->input->post('signup');     
      }
     
      // Get posted search value in variables
     
      $data['search'] = ($this->input->post('search')?trim($this->input->post('search')):$data['search']);
      $data['signup']= ($this->input->post('signup')?trim($this->input->post('signup')):$data['signup']);
      
      // Count total users
      $total = $this->U_Model->countUsersBySearch($data['search'],$data['signup'],$status,$isverified,3);
//print_r($this->db->last_query());
      $PAGE_TOTAL_ROWS = $total;
      $PAGE_URL = $this->config->item('base_url').'admin/users/fn/'.$fn.'/orderby/'.$orderb.'/search/'.$data['search'].'/signup/'.$data['signup'].'/status/'.$status.'/isverified/'.$isverified;
      $data['PAGING'] = pagination_assoc($PAGE_TOTAL_ROWS,$PAGE_LIMIT,$DISPLAY_PAGES,$PAGE_URL,$page,$pages);
      // Pagination end
      
      // Get all users
      $data['users'] = $this->U_Model->users($PAGE_LIMIT_VALUE,$PAGE_LIMIT,$data['search'],$orderby,$data['signup'],$status,$isverified,3);
      // set variable to show active menu 
      $data['menutab'] = 'network';
      $data['menuitem'] = 'industrialprofessional';
      $this->load->view($this->config->item('base_template_dir').'/admin/industrialprof/industrialprofessional', $data);

   }//end users
   // industrial professional new
public function industrialprofessionalnew($edit_id=0){
       if(!$this->phpsession->get('ciAdmId')){   redirect($this->config->item('base_url').'r/k/admin');   }
      
      if(strlen(trim($this->input->post('Submit'))) > 0){
         if($edit_id > 0){
                     
            // update record
            $data = array(
							'displayname'   => $this->input->post('displayname'),
						   'first_name'   => $this->input->post('fname'),
						   'last_name'    => $this->input->post('lname'),
						   'email'        => $this->input->post('email'),
						   'username'     => $this->input->post('username'),   
						   'country_id'      => $this->input->post('country'),
						   'state'        => $this->input->post('state'),
						   'city'         => $this->input->post('city'),
						   'zipcode'      => $this->input->post('zipcode'),
						   'website'        => $this->input->post('website'),
						   'imdb_profile'     =>$this->input->post('imdb_profile'),
						   'company_name'         => $this->input->post('company_name'),
						   'iam_representing'      => $this->input->post('iam_representing'),
						   'iam_representing_other'=> $this->input->post('txt_other'),
						   'title'      => $this->input->post('title'),
						   'username'      => $this->input->post('username'),
						   'homepage'      => $this->input->post('homepage'),
						   'bio'      => $this->input->post('bio'),
						   'facebook_lnk'   => $this->input->post('facebooklnk'),
							'twitter_lnk'    => $this->input->post('twitterlnk'),
							'linkedin_lnk'   => $this->input->post('linkedinlnk')
            );
            
            if(strlen(trim($this->input->post('password'))) > 0){
               $data = array_merge($data,array('password' => md5($this->input->post('password')),'base_password' => base64_encode($this->input->post('password'))));
            }
			//this is for upload profile image
				$this->load->helper("directory");
				$this->load->helper("upload_file");
				if(strlen(trim($_FILES['profileimage']['name'])) > 0)
				{
					if($_FILES['profileimage']['size'] < 2097152)
					  {
					  $picture_path = @upload_file($_FILES['profileimage'], "uploads/usersphoto", "usersphoto");
					  $data['avtar_image'] = $picture_path;
					  $this->phpsession->save('uprofileimage', $picture_path);		
					  $this->U_Model->users_operations($data,'update',$edit_id);
					  $this->message->setMessage('Industry Professional updated successfully',"SUCCESS");
						redirect('admin/industrialprofessional');
					  }
					  else
					  {
						  $this->message->setMessage("Image size is larger than 2MB is not allowed.","ERROR");
						  redirect('admin/industrialprofessional');
						  
					  }
				}
				else
				{
					 $this->U_Model->users_operations($data,'update',$edit_id);
					  $this->message->setMessage('Industry Professional updated successfully',"SUCCESS");
						redirect('admin/industrialprofessional');
				}
				//upload end
            
            
            
         }else{

            // insert record
            $inserted_id="";
            if(strlen(trim($this->input->post('email'))) > 0 && strlen(trim($this->input->post('password'))) > 0){          
            
               $dob = explode("-",$this->input->post('dob'));
               $data = array(
								'displayname'   => $this->input->post('displayname'),
                              'first_name'   => $this->input->post('fname'),
                              'last_name'    => $this->input->post('lname'),
                              'email'        => $this->input->post('email'),
                              'username'     => $this->input->post('username'),
                              'password'     => md5($this->input->post('password')),
                              'base_password' => base64_encode($this->input->post('password')),
                              'usertypeid'   => 3,
                              'status'       => 'active',
                              'country_id'      => $this->input->post('country'),
                              'state'        => $this->input->post('state'),
                              'city'         => $this->input->post('city'),
                              'zipcode'      => $this->input->post('zipcode'),
							  'website'        => $this->input->post('website'),
							  'imdb_profile'     =>$this->input->post('imdb_profile'),
							  'company_name'         => $this->input->post('company_name'),
							  'iam_representing'      => $this->input->post('iam_representing'),
							  'iam_representing_other'=> $this->input->post('txt_other'),
                              'isverified'   => 'yes',
                              'verified_date'=> date("Y-m-d H:i:s"),
                              'signup_date'  => date("Y-m-d H:i:s"),
                              'title'      => $this->input->post('title'),
							  'username'      => $this->input->post('username'),
							  'homepage'      => $this->input->post('homepage'),
							  'bio'      => $this->input->post('bio'),
							  'facebook_lnk'   => $this->input->post('facebooklnk'),
							'twitter_lnk'    => $this->input->post('twitterlnk'),
							'linkedin_lnk'   => $this->input->post('linkedinlnk')
               );              

               if($this->phpsession->get('inserted_id') == ""){ 
           
               //this is for upload profile image
				$this->load->helper("directory");
				$this->load->helper("upload_file");
				if(strlen(trim($_FILES['profileimage']['name'])) > 0)
				{
					if($_FILES['profileimage']['size'] < 2097152)
					  {
					  $picture_path = @upload_file($_FILES['profileimage'], "uploads/usersphoto", "usersphoto");
					  $data['avtar_image'] = $picture_path;
					  $this->phpsession->save('uprofileimage', $picture_path);		
					  $inserted_id = $this->U_Model->users_operations($data,'addnew');
					  }
					  else
					  {
						  $this->message->setMessage("Image size is larger than 2MB is not allowed.","ERROR");
					  }
				}
				else
				{
					$inserted_id = $this->U_Model->users_operations($data,'addnew');
				}
				//upload end
                  

                    if($inserted_id > 0)

                    {  $data='';  }

                 }else{

               $inserted_id = $this->U_Model->users_operations();
            } 
               if($inserted_id > 0){
                  $userinfo = $this->U_Model->getUsershortInfo($inserted_id);                                
                  $this->load->model('SystemEmail_Model','SE_Model');
                    $password = $this->input->post('password');
            //-- create account activation code
         $activationcode = md5($inserted_id * 32765);
                   // Create link for verification
         $link = "<a href='".$this->config->item('base_url')."register/registerverify/".$activationcode."'>".$this->config->item('base_url')."register/registerverify/".$activationcode."</a>";
               //--add activation code to table & use it while verification
               $this->U_Model->update_Activation_Code($inserted_id, $activationcode);   
               //--load systememail model for sending email ot user for verification
               $this->load->model('Systememail_Model','SE_Model');   
               //get email template from admin
               $admin_email= $this->SE_Model->getAdminEmails();
               $mail_content= $this->SE_Model->getEmailById(1);   

               //Email Sending Code
               $this->load->library('email');
               $this->email->from($admin_email->value,'Wraters');
               $this->email->to($this->input->post('email'));
               $this->email->subject($mail_content->subject);
               $message = $mail_content->message;

               $emailPath = $this->config->item('base_abs_path')."templates/".$this->config->item('base_template_dir');
               $email_template =  file_get_contents($emailPath.'/email/email.html');
               $message = str_replace("[[username]]", $this->input->post('username'), $message);
               $message = str_replace("[[email]]", $this->input->post('email'), $message);
               $message = str_replace("[[fullname]]",  $this->input->post('fname'), $message);
               $message = str_replace("[[password]]", $password, $message);
               $message = str_replace("[[link]]", $link, $message);
               $email_template = str_replace("[[EMAIL_HEADING]]", $mail_content->subject, $email_template);

               $email_template = str_replace("[[EMAIL_CONTENT]]",(utf8_encode($message)), $email_template);              
               $email_template = str_replace("[[SITEROOT]]", $this->config->item('base_url'), $email_template);
               $email_template = str_replace("[[footer]]", $this->config->item('base_site_name'), $email_template);
               $email_template = str_replace("[[LOGO]]",$this->config->item('base_url')."templates/".$this->config->item('base_template_dir'), $email_template);
               $this->email->message(html_entity_decode($email_template));

               $this->email->send();              
               //$this->phpsession->save('success_msg','Industrial Professional added successfully');
               $this->message->setMessage('Industry Professional added successfully',"SUCCESS");
               redirect($this->config->item('base_url').'admin/industrialprofessional');
               }
            }
         }
      }
          $id='';
         //$country_id='';
      $data = array(
                     'edit_id'      => '',
                     'displayname' => '',
                     'first_name'   => '',
                     'id'           =>'',
                     'last_name'    => '',
                     'avtar_image' => '',
                     'email'        => '',
                     'username'     => '',
                     'usertypeid'   => 3,
                     'state'        => '',
                     'country'     =>'',
                     'city'         => '',
                     'zipcode'      => '',
                     'website'        => '',
                     'imdb_profile'     =>'',
                     'company_name'         => '',
                     'iam_representing'      => '',
                     'iam_representing_other'=> '',
                      'title'      =>'',
					 'username'     => '',
					 'homepage'     => '',
					 'bio'      => '',
					 'facebook_lnk'   => '',
					'twitter_lnk'   => '',
					'linkedin_lnk'   =>''
					 
                     
      );
         $country_id   = '';$state='';
      if($edit_id > 0){
         $user = $this->U_Model->getUserById($edit_id);
         $data = array(
                        'edit_id'      => $edit_id,
                        'displayname'   => $user->displayname,
                        'first_name'   => $user->first_name,
                        'last_name'    => $user->last_name,
                         'avtar_image'    => $user->avtar_image,
                        'email'        => $user->email,
                        'username'     => $user->username,
                        'country'   => $user->country_id,
                        'state'  => $user->state,
                        'city'         => $user->city, 
                        'usertypeid'   => $user->usertypeid,   
                        'zipcode'      => $user->zipcode,
                        'website'        => $user->website,
						 'imdb_profile'     =>$user->imdb_profile,
						 'company_name'         => $user->company_name,
						 'iam_representing'      => $user->iam_representing,
						 'iam_representing_other'=> $user->iam_representing_other,
						 'title'      =>$user->title,
						'username'     => $user->username,
						'homepage'     => $user->homepage,
						'bio'      => $user->bio,
						'facebook_lnk'   => $user->facebook_lnk,
						'twitter_lnk'   => $user->twitter_lnk,
						'linkedin_lnk'   => $user->linkedin_lnk
         );
      $country_id   = $user->country_id;
      $state   = $user->state;     
      }    
       $data['states'] = $this->S_Model->getStateComboByCountryId($state);
       $data['combo'] = $this->S_Model->getCountryRecordsByStatus($country_id);
       // set variable to show active menu 
      $data['menutab'] = 'network';
      $data['menuitem'] = 'industrialprofessional';
      $this->load->view($this->config->item('base_template_dir').'/admin/industrialprof/industrialprofessionalnew',$data);
   }//end IPnew

function viewUserDetails($user_id)
{
	if($user_id > 0){
         $user = $this->U_Model->getUserById($user_id);
         $data = array(
                        'edit_id'      => $user_id,
                        'usertypeid'   => $user->usertypeid,
                        'first_name'   => $user->first_name,
                        'last_name'    => $user->last_name,
                         'sm_image1'    => $user->avtar_image,
                        'email'        => $user->email,
                        'username'     => $user->username,
                        'country'   => $user->country_name,
                        'state'  => $user->state_name,
                        'city'         => $user->city, 
                        'usertypeid'   => $user->usertypeid,   
                        'zipcode'      => $user->zipcode,
                        'website'        => $user->website,
						 'imdb_profile'     =>$user->imdb_profile,
						 'company_name'         => $user->company_name,
						 'iam_representing'      => $user->iam_representing,
						 'iam_representing_other'=> $user->iam_representing_other
						 
         );
      $country_id   = $user->country_id;
      $state   = $user->state;     
      $this->load->view($this->config->item('base_template_dir').'/admin/viewuserdetails',$data);
      }
       
}


//end of IP






}
/* End of file admin.php */
/* Location: ./system/application/controllers/admin.php */
?>
