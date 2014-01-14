<?php
			$userinfo = $this->U_Model->getUserById($this->phpsession->get('ulogid'));
			if($receivedconnection->num_rows()>0)
			{
				$i=0;
				foreach($receivedconnection->result() as $received)
				{
					$i++;
					$requestfrom = $this->U_Model->getUserById($received->requested_from_id);
					$usertimezone = $requestfrom->timezoneset;
					
					$mytimezone = $userinfo->timezoneset;
					if($usertimezone!='' && $mytimezone!='')
					{
						if($usertimezone==$mytimezone)
							$orgdate =  time_translate(date_default_timezone_get(),$mytimezone,$received->post_date);
						else
							$orgdate =  time_translate($usertimezone,$mytimezone,$received->post_date);
						
					}
					else
						$orgdate = $received->post_date;
						
						
					if($received->avtar_image != "")
						$img = $received->avtar_image;
					else
						$img = "defaultuser.jpg";
		?>
					<div <?php  if($highlight == $received->friends_id){?>style="min-height: 50px;border:solid 1px green;box-shadow: 5px 5px 3px #888888;"<?php }?> class="notif-main" onmouseover="$('#btnunfriend<?php echo $i; ?>').show();" onmouseout="$('#btnunfriend<?php echo $i; ?>').hide();" style="min-height:50px" id="<?php echo "notif".$received->friends_id ?>">
					<p class="review-text1 southspace2">
						<img src='<?php echo $this->config->item('base_url')."img/size/o/usersphoto-".$img ."/w/60/h/60/m/auto';" ?>' style="float: left;">
						&nbsp; <a href="<?php echo $this->config->item('base_url').'myaccount/profile/'.clean_url($requestfrom->username) ?>"><strong> <?php echo $requestfrom->first_name.' '.$requestfrom->last_name; ?></strong></a> - 
						<?php
						if($received->request_status=='confirm')
							$connection_status = 'Accepted';
						else
							$connection_status =$received->request_status;
					?>
					
						<?php 
						if($received->request_status=='pending')
							{
								echo "<span style='font-weight:bold;color:red;'>". ucfirst($connection_status)."</span>";
							}
							else
								echo  ucfirst($connection_status); ?>
								- received <?php echo date('F jS H:iA',strtotime($orgdate)); ?> 
					</p>
					
						<p class="review-text1 southspace2" >
							
						<?php
							if($received->request_status=='confirm')
							{
						?> 
								<input style="display:none;" id="btnunfriend<?php echo $i; ?>" type='button' class='small-grn-button-bg01 btnRequest' requested_id='<?php echo $received->requested_from_id; ?>' req_id='<?php echo $received->friends_id;?>' req_status='cancel'  value='Remove Connection' /> 
						<?php
							}//if
							if($received->request_status=='pending')
							{
						?>
								<input  type='button' class='small-grn-button-bg01 btnRequest' requested_id='<?php echo $received->requested_from_id; ?>' req_id='<?php echo $received->friends_id;?>' req_status='confirm'  value='accept' /> &nbsp;&nbsp;<input  type='button' class='small-grn-button-bg01 btnRequest' requested_id='<?php echo $received->requested_from_id; ?>' req_id='<?php echo $received->friends_id;?>' req_status='cancel'  value='Not now' />
						<?php
							}
						?>
						<input user_id="<?php echo $received->userid;?>" user="<?php echo ucfirst($received->first_name).' '.ucfirst($received->last_name);?>" type="button" class="small-button-bg-gry fr composemsgpop" value="Message" name="">
						
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
