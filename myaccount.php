<?php
class Myaccount extends CI_Controller {

	function __construct(){
		parent::__construct();
		$this->load->helper(array('form', 'url'));
		$this->load->model('users_model','U_Model');
		$this->load->model('State_Model','S_Model');
		$this->load->model('country_model','C_Model');
		$this->load->model('Friends_Model','F_Model');
		$this->load->model('ImageUpload_Model','IMGUPLD_Model');
		$this->load->model('Emailsetting_Model','E_Model');
		$this->load->model('Screenplay_Model','SP_Model');
		$this->load->model('sitesettings_model','SS_Model');
		$this->load->library('phpsession');
        $this->load->library('message');
        //Mailchimp Newsletter lib.
		$sitesettings = $this->SS_Model->getSiteSettingsDetails();
		$MAILCHIMP = array(
					'apikey' => $sitesettings[13],
					'secure' => FALSE   // Optional (defaults to FALSE)
				);
        		$this->load->library('MCAPI', $MAILCHIMP, 'mail_chimp');
       
	}
	//render dashboard here...
	function index()
	{
	
		if(!$this->phpsession->get('ulogid')){ redirect('/register'); }
		$data ="";
		//get all screenplay to display on dashboard slider...
		$data["srcp_finished"] =  $this->SP_Model->getScreenplayByStatus("published",$this->phpsession->get('ulogid'));
		$data["srcp_unfinished"] =  $this->SP_Model->getScreenplayByStatus("sid",$this->phpsession->get('ulogid'));
		$data["srcp_wrated"] =  $this->SP_Model->getScreenplayByStatus("final-wrated",$this->phpsession->get('ulogid'));
		$data["myfriends"] =  $this->F_Model->getFriendlist($this->phpsession->get('ulogid'));
		$email = $this->U_Model->getUserimpdetails($this->phpsession->get('ulogid'))->email;
		$data['reviewinvitation'] = $this->SP_Model->getreviewinvitation($this->phpsession->get('ulogid'),'Pending acceptance',3);
		$data['totalacceptinvite'] = $this->SP_Model->getnumreviewinvitation($this->phpsession->get('ulogid'),'reviewing');
		$data['totalinvite'] = $this->SP_Model->getnumreviewinvitation($this->phpsession->get('ulogid'),'Pending acceptance');
		$this->load->view($this->config->item('base_template_dir').'/user/myaccount/dashboard', $data);
	}
	
	function uploadnewscreenplay()
	{
	
		if(!$this->phpsession->get('ulogid')){ redirect('/register'); }
		$data ="";
		//get all screenplay to display on dashboard slider...
		$data["srcp_finished"] =  $this->SP_Model->getScreenplayByStatus("published",$this->phpsession->get('ulogid'));
		$data["srcp_unfinished"] =  $this->SP_Model->getScreenplayByStatus("sid",$this->phpsession->get('ulogid'));
		$data["srcp_wrated"] =  $this->SP_Model->getScreenplayByStatus("final-wrated",$this->phpsession->get('ulogid'));
		$data["myfriends"] =  $this->F_Model->getFriendlist($this->phpsession->get('ulogid'));
		$data["page"] =  "newscreenplaypopup";
		$email = $this->U_Model->getUserimpdetails($this->phpsession->get('ulogid'))->email;
		$data['reviewinvitation'] = $this->SP_Model->getreviewinvitation($this->phpsession->get('ulogid'),'Pending acceptance',3);
		$data['totalacceptinvite'] = $this->SP_Model->getnumreviewinvitation($this->phpsession->get('ulogid'),'reviewing');
		$data['totalinvite'] = $this->SP_Model->getnumreviewinvitation($this->phpsession->get('ulogid'),'Pending acceptance');
		$this->load->view($this->config->item('base_template_dir').'/user/myaccount/dashboard', $data);
	}
	
	function deleteaccount()
	{
		if(!$this->phpsession->get('ulogid')){ redirect('/register'); }
		$userid = $this->phpsession->get('ulogid');
		if($this->U_Model->deletemyaccount($userid))
			echo "done";
		else
			echo "fail";
	}
	
	function test()
	{
		$this->load->view($this->config->item('base_template_dir').'/user/myaccount/uploadify');
	}
	function changeprofileimage()
	{
		//$targetFolder = '/projects/wraters/uploads/screenplay1'; // Relative to the root
		$targetFolder = 'uploads/usersphoto'; // Relative to the root
		$this->load->helper("directory");
		$verifyToken = md5('unique_salt' . $_POST['timestamp']);

		if (!empty($_FILES) && $_POST['token'] == $verifyToken) {
			$tempFile = $_FILES['Filedata']['tmp_name'];
			// Validate the file type
			$fileTypes = array('png','jpeg','jpg','gif'); // File extensions
			$fileParts = pathinfo($_FILES['Filedata']['name']);
			
			$targetPath =  $this->config->item('base_abs_path') .$targetFolder;//$_SERVER['DOCUMENT_ROOT']
			$new_filename = directory_cleanname(time().".".$fileParts['extension']);
			$targetFile = rtrim($targetPath,'/') . '/'.$new_filename;//$_FILES['Filedata']['name'];// .$verifyToken
					
			if (in_array(strtolower($fileParts['extension']),$fileTypes)) {
				move_uploaded_file($tempFile,$targetFile);
				$update_data['avtar_image'] = $new_filename;
				//	  $this->phpsession->save('uprofileimage', $new_filename);		
					  $this->U_Model->users_operations($update_data,'update',$_POST['userid']);
			echo $new_filename;
			} else {
				echo 'Invalid file type.';
			}
		}
	}
	
	
	
	function changecoverimage()
	{
		//$targetFolder = '/projects/wraters/uploads/screenplay1'; // Relative to the root
		$targetFolder = 'uploads/usersphoto/coverimage'; // Relative to the root
		$this->load->helper("directory");
		$verifyToken = md5('unique_salt' . $_POST['timestamp']);

		if (!empty($_FILES) && $_POST['token'] == $verifyToken) {
			$tempFile = $_FILES['Filedata']['tmp_name'];
			// Validate the file type
			$fileTypes = array('png','jpeg','jpg','gif'); // File extensions
			$fileParts = pathinfo($_FILES['Filedata']['name']);
			
			$targetPath =  $this->config->item('base_abs_path') .$targetFolder;//$_SERVER['DOCUMENT_ROOT']
			$new_filename = directory_cleanname(time().".".$fileParts['extension']);
			$targetFile = rtrim($targetPath,'/') . '/'.$new_filename;//$_FILES['Filedata']['name'];// .$verifyToken
					
			if (in_array(strtolower($fileParts['extension']),$fileTypes)) {
				move_uploaded_file($tempFile,$targetFile);
				$update_data['cover_image'] = $new_filename;
				//	  $this->phpsession->save('uprofileimage', $new_filename);		
					  $this->U_Model->users_operations($update_data,'update',$_POST['userid']);
			echo $new_filename;
			} else {
				echo 'Invalid file type.';
			}
		}
	}
	public function changesettings()
	{
		$status = $this->input->post('status');
		$fieldname = $this->input->post('fieldname');
		$update_data = array($fieldname => $status);
		$userid = $this->phpsession->get('ulogid');
		$this->U_Model->users_operations($update_data, 'update', $userid);
		//echo $this->db->last_query();
		echo "done";
	}
	
	public function emailsubscription()
	{
		$status = $this->input->post('status');
		$email_setting_id = $this->input->post('email_setting_id');
		$key = $this->input->post('key');
		$mailtype = $this->input->post('mailtype');
		$userid = $this->phpsession->get('ulogid');
		//CHECK MAIL SUBSCRIPTION
		$subscription_id = $this->E_Model->checkMailSubscription($userid,$email_setting_id);
		if($subscription_id)
		{
			$update_data = array(
				'status' => $status
			);
			$this->E_Model->emailSubscriptionOperations($update_data,'update',$subscription_id);
		}
		else
		{
			$insert_data = array(
				'status' => $status,
				'userid' => $userid,
				'email_setting_id' => $email_setting_id
			);
			$this->E_Model->emailSubscriptionOperations($insert_data,'addnew',0);
		}
		
		//UPDATED TO MAILCHIMP SUBSCRIPTION
			if($mailtype == 'mailchimp')
			{
				$userinfo = $this->U_Model->getUserimpdetails($userid);
				$merges = array(
									'FNAME' => ucwords($userinfo->first_name),
									'LNAME' => ucwords($userinfo->last_name)
								);
				$list_id = $key;
				//$list_id = '870676594a';
				$listemail = $userinfo->email;
				if($status == 'yes')
					$this->mail_chimp->listSubscribe($list_id,$listemail,$merges,'html',true);
				else
					$this->mail_chimp->listUnsubscribe($list_id,$listemail,$merges,'html',true);
				//END OF MAILCHIMP
				//echo "mailchimp"."|".$list_id."|".$listemail."|".$merges;
			}
		//echo $this->db->last_query();
		echo "done"."|".$status;
	}
	
	function profilesetting($pagetype="email")
	{
		$data="";
		if(!$this->phpsession->get('ulogid')){ redirect('/register'); }
		if($pagetype == "email")
		{
			$data['emailSetting'] = $this->E_Model->getAllEmailSettings();
		}
		elseif($pagetype == "password")
		{
		}
		elseif($pagetype == "purchasehistory")
		{
			
			// Get all cms records
			$data['transactions'] = $this->SP_Model->getPurchaseHistory($this->phpsession->get('ulogid'));
			
			
		}
		elseif($pagetype == "profile")
		{
				if(!$this->phpsession->get('ulogid')){ redirect('/register'); }
				$user_id = $this->phpsession->get('ulogid');
					if(strlen(trim($this->input->post('fname'))) > 0){
					
					$update_data = array(
						'displayname'   => $this->input->post('displayname'),
						'username'   => $this->input->post('username'),
						'title'   => $this->input->post('title'),
						'first_name'   => $this->input->post('fname'),
						'last_name'    => $this->input->post('lname'),
						'email'   => $this->input->post('email'),
						'country_id'      => $this->input->post('country'),
						'state'        => $this->input->post('state'),
						'city'         => $this->input->post('city'),
						'zipcode'      => $this->input->post('zipcode'),
						'bio'   => $this->input->post('bio'),
						'education'   => $this->input->post('education'),
						'homepage'   => $this->input->post('homepage'),
						/*'iam_representing'      => $this->input->post('iam_representing'),
						'iam_representing_other'=> $this->input->post('txt_other'),*/
						'facebook_lnk'   => $this->input->post('facebooklnk'),
						'twitter_lnk'    => $this->input->post('twitterlnk'),
						'linkedin_lnk'   => $this->input->post('linkedinlnk'),
						'timezoneset'   => $this->input->post('timezoneset')
						
						);
						
						if($this->input->post('usertype')==3)
						{
							$update_data['website'] = $this->input->post('website');
							$update_data['imdb_profile'] = $this->input->post('imdb_profile');
							$update_data['company_name'] = $this->input->post('company_name');
						}
						
						
						//this is for upload profile image
						/*$this->load->helper("directory");
						$this->load->helper("upload_file");
						if(strlen(trim($_FILES['profileimage']['name'])) > 0)
						{
							  //echo $_FILES['profileimage']['size'];
							  if($_FILES['profileimage']['size'] < 2097152)
							  {
							  $picture_path = @upload_file($_FILES['profileimage'], "uploads/usersphoto", "uploads/usersphoto");
							  $update_data['avtar_image'] = $picture_path;
							  $this->phpsession->save('uprofileimage', $picture_path);		
							  
							  //upload end
								$this->phpsession->save('udisplayname',$this->input->post('displayname'));
								$this->U_Model->users_operations($update_data,'update',$user_id);
								$this->message->setMessage("Your profile is updated.","SUCCESS");
							  }
							  else
							  {
								  $this->message->setMessage("Image size is larger than 2MB is not allowed.","ERROR");
							  }
						}
						else
						{*/
						//upload end
						$this->phpsession->save('udisplayname',$this->input->post('displayname'));
						$this->U_Model->users_operations($update_data,'update',$user_id);
						$this->message->setMessage("Your profile was updated!","SUCCESS");
						//}
						redirect('myaccount/profilesetting/profile');
					}
				//Get user information for edit
				
				//$user = $this->U_Model->getUserForEditProfileById($user_id);
				$user = $this->U_Model->getUserById($user_id);
				
				$data = array(
				'edit_id'      => $user_id,
				'displayname'   => $user->displayname,
				'first_name'   => $user->first_name,
				'last_name'    => $user->last_name,
				'avtar_image'    => $user->avtar_image,
				'title'   => $user->title,
				'bio'   => $user->bio,
				'education'   => $user->education,
				'homepage'   => $user->homepage,
				'facebook_lnk'   => $user->facebook_lnk,
				'twitter_lnk'   => $user->twitter_lnk,
				'linkedin_lnk'   => $user->linkedin_lnk,
								
				'email'        => $user->email,
				'username'     => $user->username,
				'country'   => $user->country_id,
				'state'  => $user->state,
				'city'         => $user->city, 
				'usertypeid'   => $user->usertypeid,   
				'zipcode'      => $user->zipcode,
				'timezoneset'      => $user->timezoneset
				/*'website'        => $user->website,
				'imdb_profile'     =>$user->imdb_profile,
				'company_name'         => $user->company_name,
				'iam_representing'      => $user->iam_representing,
				'iam_representing_other'=> $user->iam_representing_other*/
				
				
				);
				//end.
				$country_id   = $user->country_id;
				$state   = $user->state;     
				if($country_id == 0 || $country_id =="")
					$country_id = 223;
				$data['countryOptions'] = $this->S_Model->getCountryRecordsByStatus($country_id);
				$data['statesOptions'] = $this->S_Model->getStateComboByCountryId($state,$country_id);
		
		}
		else
		{
			$user_id = $this->phpsession->get('ulogid');
			$user = $this->U_Model->getUserProfileSettings($user_id);
			//profilestatus,profilestats,screenplaystats,reviewsstats'
				$data = array(
				'profilestatus'      => $user->profilestatus,
				'profilestats'   => $user->profilestats,
				'screenplaystats'   => $user->screenplaystats,
				'reviewsstats'    => $user->reviewsstats
				
				);
				$data['emailSetting'] = $this->E_Model->getAllEmailSettings();
		}
		$user_id = $this->phpsession->get('ulogid');
			$user = $this->U_Model->getUserProfileSettings($user_id);
			$data['login_type'] = $user->login_type;
			
		
		$data['pagetype'] = $pagetype;
		$this->load->view($this->config->item('base_template_dir').'/user/myaccount/profilesetting', $data);
	}
	
	function changepassword()
	{
		if(!$this->phpsession->get('ulogid')){ redirect('/register'); }
		$user_id = $this->phpsession->get('ulogid');
		$varoldpwd = md5($this->input->post('oldpassword'));
		if( $this->U_Model->checkfrontuserpassword($varoldpwd) == "true")
		{
		$varnewpassword = md5($this->input->post('cpassword'));
		$passdata       = array(
                       'password' => $varnewpassword,
                       );
            $this->U_Model->users_operations($passdata, 'update', $user_id);
		    $this->message->setMessage("Your password has been changed!","SUCCESS");
			redirect('/myaccount/profilesetting/password');
		}
		else
		{
			$this->message->setMessage("Old password does not match","ERROR");
			redirect('/myaccount/profilesetting/password');
		}
	}
	
	function checkvalidpwd()
	{
		if(!$this->phpsession->get('ulogid')){ redirect('/register'); }
		$user_id = $this->phpsession->get('ulogid');
		$varoldpwd = md5($this->input->post('oldpassword'));
		echo $this->U_Model->checkfrontuserpassword($varoldpwd);
		
		
	}
	
	public function profile($user_name="-")
	{
		if(!$this->phpsession->get('ulogid')){ redirect('/register'); }
			$data="";
			if($user_name == "-")
			{
				$user_name = $this->phpsession->get('uusername');
			}
			$data['userInfo'] = $this->U_Model->getUserbyUsername($user_name);
			if($data['userInfo'])
			{
				if($data['userInfo']->profilestatus=='private' && $data['userInfo']->userid!=$this->phpsession->get('ulogid') ) redirect('/');
				
				$data["srcp_published"] =  $this->SP_Model->getScreenplayByStatus("published-wrated",$data['userInfo']->userid);
			
				//CHECK FOR FRIEND REQUEST IS PENDING...?
				$this->load->model('Friends_Model','F_Model');
				$pending_req = 0;
				$request_data = $this->F_Model->checkpendingrequest($data['userInfo']->userid);
				if($request_data)
				{
					
				$data['request_id']=$request_data->friends_id;
				$data['sender_id']=$request_data->sender_id;
				$data['request_status']=$request_data->request_status;
				}
				else
				{
					$data['request_id'] = 0;
				}
				//END
				$data["myfriends"] =  $this->F_Model->getFriendlist($data['userInfo']->userid);
				//COUNT USER VISIT HERE...
				$visiterid = $this->phpsession->get('ulogid');
				$varuser_id = $data['userInfo']->userid;
				if($visiterid  != $varuser_id)
					$this->Statistics_Model->countUserVisited($varuser_id,$visiterid,"profile");
				//END OF COUNT
				//user profile visit count
				$profileVisit = $this->db->where('tp_id',$varuser_id)->where("page = 'profile'")->count_all_results('tbl_user_visited');
				$this->db->flush_cache();
				$data['profileVisit']=$profileVisit;
				//end
				//screenplay USER REVIEW RATING SCORE
				//get all sid from titlepage userid ....
				$sidResult = $this->db->select("GROUP_CONCAT(sid SEPARATOR ',') as sids",false)->where('user_id',$varuser_id)->get('tbl_screenplay');
				$sidsArr = array();
				if($sidResult->num_rows() > 0)
				{
					$sidsArr = explode(',',$sidResult->row()->sids);
				}
				$this->db->flush_cache();
				//print_r($sidsArr);die;
				$wratingscore = $this->db->where_in('reviewed_sid',$sidsArr)->where('status','accept')->select_avg('ratingscore')->select("COUNT(*) AS RCount")->get('tbl_wraters_review');
				if($wratingscore->num_rows() > 0)
				{
					$data['wratingscore'] = round($wratingscore->row()->ratingscore,1);
				}
				//REVIES FOR USER SCREENPLAY 
				$this->db->flush_cache();
				//print_r($sidsArr);die;
				$UsersReview = $this->db->where_in('sid',$sidsArr)->where('status','Accepted')->select_avg('ratingscore')->select("COUNT(*) AS RCount")->get('tbl_user_reviews');
						if($UsersReview->num_rows() > 0)
						{
							$data['UsersReview'] = $UsersReview->row()->RCount;
							$data['UsersReviewScore'] = $UsersReview->row()->ratingscore;
						}
				//END OF USER SCREENPLAY REVIEW
				$this->db->flush_cache();
				/*$RGivenCount = $this->db->where('userid',$varuser_id)->where('status','Accepted')->select("COUNT(*) AS RCount")->get('tbl_user_reviews');
						if($RGivenCount->num_rows() > 0)
						{
							$data['RGivenCount'] = $RGivenCount->row()->RCount;
						}*/
						
				$UsersReviewWritten = $this->db->where_in('userid',$varuser_id)->where('status','Accepted')->select_avg('ratingscore')->select("COUNT(*) AS RCount")->get('tbl_user_reviews');
						if($UsersReviewWritten->num_rows() > 0)
						{
							$data['RGivenCount'] = $UsersReviewWritten->row()->RCount;
							$data['UsersReviewWrittenScore'] = $UsersReviewWritten->row()->ratingscore;
						}		
				//REPUTATION LEVEL FROM WRITTEN SCREENPLAY REVIEWS...
				//SELECT * FROM `tbl_reputation_levels` WHERE `from` < 15 and `to` > 15
				$RLevel = $this->db->where("`from` < ".$data['RGivenCount']." and `to` > ".$data['RGivenCount'])->select('level')->get('tbl_reputation_levels');
				
				if($RLevel->num_rows() > 0)
					$data['ReputationLevel']=$RLevel->row()->level;
				
				//END OF  LEVEL CAL		
						
				//user PUBLISHED SCREENPLAY COUNT
				$this->db->flush_cache();
				$publishedScreenplay = $this->db->where('user_id',$varuser_id)->where("publish_status = 'yes'")->count_all_results('tbl_screenplay');
				$data['publishedScreenplay']=$publishedScreenplay;
				//end
				//user WORKING ON SCREENPLAY COUNT
				$this->db->flush_cache();
				$screenplayindev = $this->db->where('user_id',$varuser_id)->where("status = 'sid'")->count_all_results('tbl_screenplay');
				$data['screenplayindev']=$screenplayindev;
				//end
				//user TOTAL SCREENPLAY COUNT
				$this->db->flush_cache();
				$totalscreenplay = $this->db->where('user_id',$varuser_id)->count_all_results('tbl_screenplay');
				$data['totalscreenplay']=$totalscreenplay;
				//end
				//user TOTAL FRINEDS COUNT
				$this->db->flush_cache();
				$data["fulllistfrnds"] =  $this->F_Model->getFriendlist($data['userInfo']->userid,1);
				$totalfriends = $data["fulllistfrnds"]->num_rows();
				$data['totalfriends']=$totalfriends;
				//end
				$this->load->view($this->config->item('base_template_dir').'/user/myaccount/publicprofile', $data);
			}
			else
			redirect('myaccount/');
		
	}
	public function changeprofilestatus()
	{
		$user_id = $this->input->post('user_id');
		$status = $this->input->post('status');
		$update_data = array(
		'profilestatus' => $status
		);
		$result = $this->U_Model->users_operations($update_data, 'update', $user_id);
		if($result)
		echo "true";
		else
		echo "fail";
	}
	
	function screenplay($invitation)
	{
		if(!$this->phpsession->get('ulogid')){ redirect('/register'); }
		$sortBy = "all";
		if(isset($_POST['selSort']))
			$sortBy = $this->input->post('selSort');
		if($invitation=='invitation')
		{
			$email = $this->U_Model->getUserimpdetails($this->phpsession->get('ulogid'))->email;
			$data['reviewinvitation'] = $this->SP_Model->getreviewinviteall($this->phpsession->get('ulogid'),$sortBy);
			$data['selSort'] = $sortBy;
			$this->load->view($this->config->item('base_template_dir').'/user/screenplay/review_invitations',$data);
		}//if
	}//screenplay
	
	function sendaccesspermissionmsg()
	{
		if(!$this->phpsession->get('ulogid')){ redirect('/register'); }
		$this->load->model('message_model','Msg_Model');
		$sid = $this->input->post('sid');
		$details = $this->SP_Model->getScreenplayById($sid);
		if($details)
		{
			$userid = $details->user_id;
			
			$sender_id = $this->phpsession->get('ulogid');
			$receiver_id = $userid;
			$message = $this->input->post('txtmessage');
			$MessageStr = "Title Page Privacy Setting Changed<br />";
			$MessageStr .= $details->title."<br />";
			$MessageStr .= "Request Access Below<br />";
			$MessageStr .= $message;
			
			$subject = 'Screenplay Access Required: '.$details->title;
			$insert_data = array(
				'subject' => $subject,
				'message' => html_entity_decode($MessageStr),
				'parent_id' => 0,
				'sender_id' => $sender_id,
				'receiver_id' => $receiver_id,
				'msg_status' => 'unread',
				'post_date' =>  date("Y-m-d H:i:s"),
				'replydate' =>  date("Y-m-d H:i:s")
			);
			
			 $message_id = $this->Msg_Model->messageOperations($insert_data,'addnew',0);
			 
			 $this->message->setMessage("Message Sent!","SUCCESS");
			echo $this->message->getMessage();
		}//if
		else
		{
			$this->message->setMessage("Some thing went wrong","ERROR");
			echo $this->message->getMessage();
		}//else
	}//sendaccesspermissionmsg
	
}
	
