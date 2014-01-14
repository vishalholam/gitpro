<?php ob_start();
class Payment extends CI_Controller{
   #----------------------------------------
   // Intialise constructor
   #----------------------------------------
   public function __construct(){
      parent::__construct();
      $this->load->helper(array('form', 'url'));
      $this->load->library('phpsession');
	  $this->load->library('message');
   }//end admin
   
   
   public function paypalsuccess()
   {
	   //print_r($_POST['payment_gross']);DIE;
		if (!empty($_POST))
		{
			
			if($_POST['payment_gross'] > 0)
				{
					 $this->phpsession->save('payment_gross', $_POST['payment_gross']);
					 $this->phpsession->save('payment_sid', $_POST['item_number']);
					 if(isset($_POST['txn_id']))
						 $this->phpsession->save('txn_id', $_POST['txn_id']);
					 if(isset($_POST['payer_email']))
					  	$this->phpsession->save('payer_email', $_POST['payer_email']);
					 redirect('screenplay/paymentsuccess');
				}
				else
				{
					$this->message->setMessage("Your payment is cancelled.","ERROR");
					redirect('myaccount/');
				}
		}
		else
				{
					$this->message->setMessage("Your payment is cancelled.","ERROR");
					redirect('myaccount/');
				}
	}
   
   public function paypalcancel()
   {
	   $this->message->setMessage("Your payment is cancelled.","ERROR");
	   redirect('myaccount/');
   }
   
   public function paypalnotify()
   {
	   print_r($_POST);DIE;
   }
}
