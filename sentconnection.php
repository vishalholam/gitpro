<?php
			$userinfo = $this->U_Model->getUserById($this->phpsession->get('ulogid'));
			if($sentconnection->num_rows()>0)
			{
				$i=0;
				foreach($sentconnection->result() as $sent)
				{
					$i++;
					$requestto = $this->U_Model->getUserById($sent->requested_id);
					$mytimezone = $userinfo->timezoneset;
					if($mytimezone!='')
						$orgdate =  time_translate(date_default_timezone_get(),$mytimezone,$sent->post_date);
					else
						$orgdate = $sent->post_date;
						
				if($sent->avtar_image != "")
						$img = $sent->avtar_image;
					else
						$img = "defaultuser.jpg";		
						
		?>
					<div <?php  if($highlight == $sent->friends_id){?>style="min-height: 50px;border:solid 1px green;box-shadow: 5px 5px 3px #888888;"<?php }?> onmouseover="$('#btnunfriendsent<?php echo $i; ?>').show();" onmouseout="$('#btnunfriendsent<?php echo $i; ?>').hide();" class="notif-main" style="min-height:50px" id="<?php echo "notif".$sent->friends_id ?>">
					<p class="review-text1 southspace2">
						<img src='<?php echo $this->config->item('base_url')."img/size/o/usersphoto-".$img ."/w/60/h/60/m/auto';" ?>' style="float: left;">
						&nbsp; <a href="<?php echo $this->config->item('base_url').'myaccount/profile/'.clean_url($requestto->username) ?>"><strong> <?php echo $requestto->first_name.' '.$requestto->last_name; ?></strong></a> - 
						<?php
						if($sent->request_status=='confirm')
							$connection_status = 'Accepted';
						else
							$connection_status =$sent->request_status;
					?>
					
						<?php 
						if($sent->request_status=='pending')
							{
								echo "<span style='font-weight:bold;color:red;'>". ucfirst($connection_status)."</span>";
							}
							else
								echo  ucfirst($connection_status); ?>
								- sent <?php echo date('F jS H:iA',strtotime($orgdate)); ?> 
					
						<p class="review-text1 southspace2" >
						<?php
							if($sent->request_status=='confirm')
							{
						?>
								<input style="display:none;"  id="btnunfriendsent<?php echo $i; ?>"  type='button' class='small-grn-button-bg01 btnRequest' requested_id='<?php echo $sent->requested_id; ?>' req_id='<?php echo $sent->friends_id;?>' req_status='cancel'  value='Remove Connection' /> 
						<?php
							}//if
							else
							{
						?>
								<input  type='button' class='small-grn-button-bg01 btnRequest' requested_id='<?php echo $sent->requested_id; ?>' req_id='<?php echo $sent->friends_id;?>' req_status='cancel'   value='Cancel' /> 
						<?php
							}
						?>
						<input user_id="<?php echo $sent->userid;?>" user="<?php echo ucfirst($sent->first_name).' '.ucfirst($sent->last_name);?>" type="button" class="small-button-bg-gry fr composemsgpop" value="Message" name="">
						</p>
						</p>
					   </div>
		<?php
				}//foreach
			}//if
			else
			{
		?>
				<div class="notif-main" style="min-height:50px">
											
						<p class="review-text1">No Connections </p>
						
					
					   
					   
					  </div>
		<?php
			}//else
		?>
		
		<div class="review-pagination fr">
		<ul class="pagination">
		<?php
		if($PAGING)
			echo $PAGING;
		?>
		</ul>
		</div>
