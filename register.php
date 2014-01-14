<?php ob_start();
class Register extends CI_Controller{
   private $msg = '';
   private $user = '';
   #----------------------------------------
   // Intialise constructor
   #----------------------------------------
   public function __construct(){
      parent::__construct();
      $this->load->helper(array('form', 'url'));
      $this->load->model('Users_Model',"U_Model");
	  $this->load->model('Notification_Model',"Not_Model");
	  $this->load->model("Friends_Model","F_Model");
      $this->load->model('Cms_Model','C_Model');
	  $this->load->library('phpsession');
	  $this->load->library('message');
	  $this->load->helper('recaptchalib');
	  $this->load->model('Emailsetting_Model','E_Model');
	  $this->load->model('sitesettings_model','SS_Model');
	   //Mailchimp Newsletter lib. DO NOT DELETE COMMENT.
	/*	$sitesettings = $this->SS_Model->getSiteSettingsDetails();
		$MAILCHIMP = array(
					'apikey' => $sitesettings[13],
					'secure' => FALSE   // Optional (defaults to FALSE)
				);
        		$this->load->library('MCAPI', $MAILCHIMP, 'mail_chimp');*/
}

function index($code='')
{
	if($this->phpsession->get('ulogid')){ redirect('/myaccount'); }
	$data = array(
			   'displayname'   =>'',
			   'first_name'   => '',
			   'last_name'    => '',
			   'email'        => '',
			   'password'        => ''
				);
        $sitesettings = $this->SS_Model->getSiteSettingsDetails();		
        
        if(count($sitesettings) > 14)
        {
			$data["wratersregister"] = $sitesettings[14];
			$data["ipregister"] = $sitesettings[20];
			$data["signuptext"] = $sitesettings[8];
		}
		else
		{
			$data["wratersregister"] = "";
			$data["ipregister"] = "";
			$data["signuptext"] = "";
		}
		$data["whysignup"] = $this->C_Model->getCmsByTitle('why-sign-up');
		$data["whichprofilesuit"] = $this->C_Model->getCmsByTitle('wh-profile');
		$data['menutab'] = "register";
		$this->load->view($this->config->item('base_template_dir').'/user/register/signupprofile',$data);
		if($code!='')
		{
			$chk = $this->U_Model->getUserById(base64_decode($code));
			if($chk)
				$this->phpsession->save('invitecode',base64_decode($code));
		}//if
		
}

function createUsername($username="")
{
		if($this->U_Model->checkDuplicateUsername($username,'addnew',0) == "false")
		{
			$digits = 3;
			$rand_num = str_pad(rand(0, pow(10, $digits)-1), $digits, '0', STR_PAD_LEFT);
			$username = $username.$rand_num;
			$username = $this->createUsername($username);
		}
		return $username;
}
function writer()
{
	if($this->phpsession->get('ulogid')){ redirect('/myaccount'); }
	if(strlen(trim($this->input->post('Submit'))) > 0){
		//	register record
		if($this->_verifycapcha())
		{
			
			 $user_name = clean_url($this->input->post('fname').$this->input->post('lname'));
			 $user_name = $this->createUsername($user_name);
			 $data = array(
							
                           'first_name'   => $this->input->post('fname'),
                           'last_name'    => $this->input->post('lname'),
                           'username'        => $user_name,
                           'email'        => $this->input->post('email'),
                           'usertypeid'   => 2,
                           'signup_date'  => date("Y-m-d H:i:s")	
                        
             );
            
            if(strlen(trim($this->input->post('password'))) > 0){
               $data = array_merge($data,array('password' => md5($this->input->post('password')),'base_password' => base64_encode($this->input->post('password'))));
            }

            $inserted_id = $this->U_Model->users_operations($data,'addnew');
			if($inserted_id > 0){
			
				if($this->phpsession->get('invitecode')!='')
				{
					$req_status = $this->F_Model->checkfriends($this->phpsession->get('invitecode'),$inserted_id);
					if(!$req_status)
					{
						$insert_data = array(
							'requested_from_id' => $this->phpsession->get('invitecode'),
							'requested_id' => $inserted_id,
							'request_status' => 'confirm',
							'post_date' => date("Y-m-d H:i:s")
						);
						$friends_id = $this->F_Model->friendOperations($insert_data,'addnew',0);
					
						$notification_data = array(
							'user_id' => $this->phpsession->get('invitecode'),
							'notification' => "Your friend request accepted by ".$this->input->post('fname')." ".$this->input->post('lname'),
							'post_date' => date("Y-m-d H:i:s"),
							'status' => 'active',
							'type' => 'other',
							'anchor_id' =>0
						);
						$not_id = $this->Not_Model->notificationOperations($notification_data,'addnew',0);
					}//if
					$this->phpsession->save('invitecode','');
				}//if
						
				$this->_sendverificationlink($inserted_id);
				$this->phpsession->save("signup","ready");
				
				$update_db = array(
					'userid' => $inserted_id
				);

				$this->db->where('userid =0');
				$this->db->where('email',$this->input->post('email'));
				$this->db->update('tbl_sent_invitation',$update_db); 
				
				$this->db->flush_cache();
				
				$update_db = array(
					'requested_id' => $inserted_id
				);
				$this->db->where('requested_id =0');
				$this->db->where('requeste_to_email',trim($this->input->post('email')));
				$this->db->update('tbl_friends',$update_db); 
				
				$this->db->flush_cache();
				
				$update_db = array(
					'user_id' => $inserted_id
				);
				$this->db->where('user_id =0');
				$this->db->where('to_email',trim($this->input->post('email')));
				$this->db->update('tbl_notification',$update_db); 
				
				//EMAIL SUBSCRIPTION 
				//DO NOT DELETE COMMENT.
				/*$mailChimpList = $this->E_Model->getMailChimpEmailSettings();
				foreach($mailChimpList as $list)
				{
						$insert_data = array(
						'status' => 'yes',
						'userid' => $inserted_id,
						'email_setting_id' => $list->pk_email_setting
						);
						$this->E_Model->emailSubscriptionOperations($insert_data,'addnew',0);
						//UPDATED TO MAILCHIMP SUBSCRIPTION
						$merges = array(
							'FNAME' => ucwords($this->input->post('fname')),
							'LNAME' => ucwords($this->input->post('lname'))
						);
						$list_id = $list->key;
						//$list_id = '870676594a';
						$listemail = $this->input->post('email');
						$this->mail_chimp->listSubscribe($list_id,$listemail,$merges,'html',false);
						//END OF MAILCHIMP
						//echo "mailchimp"."|".$list_id."|".$listemail."|".$merges;
				}
				//END OF SUB.*/
				redirect("./");
			}
			else{
				//$this->message->setMessage("Unable to signup.","ERROR");     
			}
		}
		else
		{
			$this->message->setMessage("The reCAPTCHA wasn't entered correctly.","ERROR");
		}
			
		}
		
			
			$dataempty = array(
							   'displayname'   =>'',
							   'first_name'   => '',
							   'last_name'    => '',
							   'email'        => '',
							   'password'        => ''
			);
		$dataempty["whysignup"] = $this->C_Model->getCmsByTitle('why-sign-up');
		$dataempty["whichprofilesuit"] = $this->C_Model->getCmsByTitle('wh-profile');
		$dataempty["signuptext"] = $this->C_Model->getSignupText();
		$dataempty['menutab'] = "signup";
		$this->load->view($this->config->item('base_template_dir').'/user/register/writer',$dataempty);
		
}

function iprofessional()
{
	if($this->phpsession->get('ulogid')){ redirect('/myaccount'); }
	if(strlen(trim($this->input->post('Submit'))) > 0){
		//	register record
		if($this->_verifycapcha())
		{
			 $data = array(
							'first_name'   => $this->input->post('fname'),
                            'last_name'    => $this->input->post('lname'),
                            'email'        => $this->input->post('email'),
                            'website'        => $this->input->post('website'),
							'imdb_profile'     =>$this->input->post('imdb_profile'),
							'phone'         => $this->input->post('phone'),
							'usertypeid'   => 3,
							'signup_date'  => date("Y-m-d H:i:s")	
                        
             );
            
            if(strlen(trim($this->input->post('password'))) > 0){
               $data = array_merge($data,array('password' => md5($this->input->post('password')),'base_password' => base64_encode($this->input->post('password'))));
            }
			
            $inserted_id = $this->U_Model->users_operations($data,'addnew');
			if($inserted_id > 0){
				//$this->_sendverificationlink($inserted_id);
				$this->message->setMessage("You are signup successfully, admin will apporove and send you email with verification link","SUCCESS");
				redirect("/register/iprofessional");
			}
			else{
				//$this->message->setMessage("Unable to signup.","ERROR");     
			}
			}
		}
			
			$dataempty = array(
							   'first_name'   => '',
							   'last_name'    => '',
							   'email'        => '',
							   'password'        => '',
							   'website'        => '',
							   'imdb_profile'     =>'',
							   'phone'         => ''
			);
		$dataempty["whysignup"] = $this->C_Model->getCmsByTitle('why-sign-up');
		$dataempty["whichprofilesuit"] = $this->C_Model->getCmsByTitle('wh-profile');
		$dataempty["signuptext"] = $this->C_Model->getSignupText();
		$dataempty['menutab'] = "signup";
		$this->load->view($this->config->item('base_template_dir').'/user/register/iprofessional',$dataempty);
		
}

function _verifycapcha()
{
	 /* $privatekey = "6LdTQeASAAAAADNTqrXc7hnsW8MnQfP5bfULBZ9j";
	  $resp = recaptcha_check_answer ($privatekey,
									$_SERVER["REMOTE_ADDR"],
									$_POST["recaptcha_challenge_field"],
									$_POST["recaptcha_response_field"]);
	   if (!$resp->is_valid) {
		   return false;
	   }
	   else
	   {
			return true;
	   }*/
	   return true;
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
               $this->email->message(html_entity_decode($email_template));
				//echo $email_template;die;
               //$this->email->send();   
               
               if(!send_ses_mail($this->input->post('email'),'no-reply@wraters.com',$admin_email->value,$mail_content->subject,$email_template))
               		$this->email->send();   
               //echo $this->email->print_debugger(); 
               //die;
		   }
	   }  
	   
	   
		function registerverify($activationcode="")
		{
			$userid = $this->U_Model->userverificationbycode($activationcode);
			if($userid)
			{
			//SEND WELCOME MAIL TO USER 
			$userinfo = $this->U_Model->getUsershortInfo($userid);                                
			$this->load->model('SystemEmail_Model','SE_Model');
                
               $this->load->model('Systememail_Model','SE_Model');   
               //get email template from admin
               $admin_email= $this->SE_Model->getAdminEmails();
               $mail_content= $this->SE_Model->getEmailById(4);   

               //Email Sending Code
               $this->load->library('email');
               $this->email->from($admin_email->value,'Wraters');
               $this->email->to($userinfo->email);
               $this->email->subject($mail_content->subject);
               $message = $mail_content->message;

               $emailPath = $this->config->item('base_abs_path')."templates/".$this->config->item('base_template_dir');
               $email_template =  file_get_contents($emailPath.'/email/email.html');
               
               $message = str_replace("[[Username]]", $userinfo->first_name, $message);
                           
               $email_template = str_replace("[[EMAIL_HEADING]]", $mail_content->subject, $email_template);
               $email_template = str_replace("[[SITE_NAME]]", $this->config->item('base_site_name'), $email_template);
               $email_template = str_replace("[[footer]]", $this->config->item('base_site_name'), $email_template);

               $email_template = str_replace("[[EMAIL_CONTENT]]",(utf8_encode($message)), $email_template);              
               $email_template = str_replace("[[footer]]", $this->config->item('base_site_name'), $email_template);
               $email_template = str_replace("[[SITEROOT]]", $this->config->item('base_url'), $email_template);
               $email_template = str_replace("[[LOGO]]",$this->config->item('base_url')."templates/".$this->config->item('base_template_dir'), $email_template);
               $this->email->message(html_entity_decode($email_template));
               
			//	echo $email_template;die;
               if(!send_ses_mail($userinfo->email,'no-reply@wraters.com',$admin_email->value,$mail_content->subject,$email_template))
               		$this->email->send(); 
               
               $message_content= $this->SE_Model->getEmailById(24);  
               $welcomeMessage = $message_content->message; 
               $welcomeMessage = str_replace("[[Username]]", $userinfo->first_name, $welcomeMessage);
               $welcomeMessage = html_entity_decode($welcomeMessage);
					//END MAIL
					//SEND APPLICATION MESSAGE HERE....
					$this->load->model('Message_Model','Msg_Model');
						$sender_id = 1;
						$receiver_id = $userid;
						$subject = "Welcome to wraters.com";
						$insert_data = array(
						'subject' => $subject,
						'message' => $welcomeMessage,
						'parent_id' => 0,
						'sender_id' => $sender_id,
						'receiver_id' => $receiver_id,
						'msg_status' => 'unread',
						'post_date' => 	date("Y-m-d H:i:s")
						);
						
						$message_id = $this->Msg_Model->messageOperations($insert_data,'addnew',0);
					//END
					
					$this->message->setMessage("Account verified","SUCCESS","loginmsg");
					redirect('/register/verified');
				}
				else
				{
					$this->message->setMessage("Unable to verify account.<br />Verification code is either used or link is expired","ERROR","loginmsg");
					redirect('/register');
				}
		}
		
		function verified()
		{
			if($this->phpsession->get('ulogid')){ redirect('/myaccount'); }
			$this->load->model('Home_Feature_Model','H_Model');
			$data['home_feature'] = $this->H_Model->gethomeRecordsByStatus();//Active
			$data['verified'] = true;
			$data['menutab'] = "home";
			$this->load->view( $this->config->item( 'base_template_dir' ) . '/home_page',$data);
		}
		function wraterslogin()
		{
			if($this->phpsession->get('ulogid')){ redirect('/myaccount'); }
			$this->load->model('Home_Feature_Model','H_Model');
			$data['home_feature'] = $this->H_Model->gethomeRecordsByStatus();//Active
			$data['verified'] = true;
			$data['menutab'] = "home";
			$this->load->view( $this->config->item( 'base_template_dir' ) . '/home_page',$data);
		}

	   
}
