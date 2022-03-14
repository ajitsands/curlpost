
<?php
session_start();

require ('../../model/common/common_functions.php');
class mobileRechargeControlerForRecharge
{
    var $varModelObj;
    public $actionevents,$recharge_amt,$response_msg,$start_tran_number,$provider_tran_number,$ref_id,$recharge_mode,$number,$balance,$log_path,$response_result,$operator_code;
   
    
    function __construct()
	{
        $this->varModelObj = new CommonModel();
         
         
        $this->log_path = 'rechargingsolutuion';   
       
        
        $this->ref_id=$_POST['v_agent_id'];
        $this->recharge_amt = $_POST['v_recharging_amount'];
        $this->number = $_POST['v_mobile_number'];
        $this->operator_code=$_POST['v_operator_code'];
        
        
        $this->curl_post_star();
        
     
    }
    
    function transactions($response_result)
    {
         try
        {
                $json = json_decode($response_result);
             
                $this->response_code=$json->response_code;
                $this->response_msg=$json->response_msg;
               
                
                  switch ($this->response_code)
                    {
                        
                        case 'TXN':
                                $this->start_tran_number=$json->txn_id;
                                $this->provider_tran_number=$json->operator_id;
                                $this->balance =$json->balance;
                                $this->response_code='TXN';
                                file_put_contents("/home/".$this->log_path."/public_html/log/star/star_balance.txt",  $this->balance, LOCK_EX);
                                $this->RequestAccept('OnRechargeSuccess');
                                
                        break;
                        case 'TXF':
                              $this->response_code='TXF';
                              $this->response_msg='Transaction Failed';
                              $this->start_tran_number='STAR-JSON-TXF'.$this->ref_id;
                              $this->provider_tran_number='NA';
                         
                              
                              $this->RequestAccept('OnRechargeFailed');
                                  
                        break;
                         case 'TUP':
                                $this->start_tran_number='NA';
                           
                                $this->RequestAccept('OnRechargePending');
                                 
                               
                        break;
                        default:
                               
                                $this->provider_tran_number='NA';
                              
                                $this->RequestAccept('OnRechargeFailed');
                               
                        break;
                    }
          
           echo '-'.$this->response_msg.'-'.$this->response_code;
          
        }
        catch(JsonException  $e)
        {
             
                $this->response_code='TXF';
                $this->response_msg='Transaction Failed';
                $this->start_tran_number='STAR-JSON-TXF'.$this->ref_id;
                $this->RequestAccept('OnRechargeFailed');
                echo "Transaction Failed";
                
        }
       
        
        
        
    }
    
    
    function curl_post_star()
    {
        
    
        $sCurrDate = date("Y-m"); //Current Date

    	$sDirPath = "/home/".$this->log_path."/public_html/log/rs/".$sCurrDate."/"; //Specified Pathname
    
    	if (!file_exists ($sDirPath))
    
       	{
    
    	    	mkdir($sDirPath,0777,true);  
    
    	}
        $ch = curl_init();
        //console.log('Getting Post Message in Curl Page  : '.$_POST['api_message']);
        file_put_contents("/home/".$this->log_path."/public_html/log/rs/".$sCurrDate."/test_api_curl_star_out_v3_" . date("d-m-Y") . ".txt", "\n" . $date . " : " . $_POST['api_url'].$_POST['api_message'], FILE_APPEND | LOCK_EX);
        
        
        curl_setopt($ch, CURLOPT_URL,$_POST['api_url']);
        
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$_POST['api_message']);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $server_output = curl_exec($ch);
        
        
        
        if(curl_errno($ch)){
                  
                    file_put_contents("/home/".$this->log_path."/public_html/log/rs/".$sCurrDate."/test_api_curl_star_error_" . date("d-m-Y") . ".txt", "\n" . $this->current_date . " : " . ' Request Error:' . curl_error($ch), FILE_APPEND | LOCK_EX);
                    $this->response_code='TXF';
                    $this->response_msg='Transaction Failed';
                    $this->start_tran_number='STAR-CURL-TXF'.$this->ref_id;
                    $this->RequestAccept('OnRechargeFailed');
                    echo "Transaction Failed";
                   
                }
        
        
        curl_close ($ch);
        
        
        
        //$varModelObj = new CommonModel();
        //$varModelObj->AddToTableForLog("insert into transaction_log(result,app_web,status,transaction_id)values('".trim($server_output)."','".$_POST['post_through']."','".$_POST['api']."','".$_POST['v_agent_id']."')");
        file_put_contents("/home/".$this->log_path."/public_html/log/rs/".$sCurrDate."/test_api_curl_star_in_v3_" . date("d-m-Y") . ".txt", "\n" . $date . " : " . trim($server_output), FILE_APPEND | LOCK_EX);
        
        //return trim($server_output);
       
        $this->transactions(trim($server_output));
    }

    function SQLArray()
    {
        $array =  array();
       
        $array[4]="update recharge_request set api_response_code='".$this->response_code."', recharge_status='".$this->response_msg."', start_tran_number='".$this->start_tran_number."',provider_tran_number='".$this->provider_tran_number."', amount=".$this->recharge_amt.",recharge_access_mode='app' where request_id =".$this->ref_id;
   
      //On Recharge Failure fund entries ie revert fund

        $array[20]="Insert into transactions_".$_SESSION["customer_id"]."(expense,income,from_to,from_to_type,from_to_name,description,status,login_username,login_user_id,commission_for_fund_transfer,transaction_ref_no_own_table,recharge_ref_id) values(0.00,".$this->recharge_amt.",".$_SESSION["customer_id"].",'Dealer','".$_SESSION["customer_name"]."','For Recharge','Recharge Refund','".$_SESSION["user_name"]."',".$_SESSION["user_id"].",0.00,0,".$this->ref_id.")";
        
        $array[21]="Insert into transactions_".$_SESSION["customer_dlr_id_of_user"]."(expense,income,from_to,from_to_type,from_to_name,description,status,login_username,login_user_id,commission_for_fund_transfer,transaction_ref_no_own_table,recharge_ref_id) values(0.00,".$this->recharge_amt.",".$_SESSION["customer_dlr_id_of_user"].",'Dealer','".$_SESSION["customer_dlr_name_of_user"]."','For Recharge','Recharge Refund','".$_SESSION["user_name"]."',".$_SESSION["user_id"].",0.00,0,".$this->ref_id.")";// for dealer user


        //commission entries..
        $array[22]="Insert into transactions_".$_SESSION["customer_id"]."(income,from_to,from_to_type,from_to_name,description,status,login_username,login_user_id,commission_for_fund_transfer,recharge_ref_id,transaction_ref_no_own_table,expense) values(0.00,".$_SESSION["customer_id"].",'Dealer','".$_SESSION["customer_name"]."','For Recharge','Commission Refund','".$_SESSION["user_name"]."',".$_SESSION["user_id"].",0.00,".$this->ref_id.",";
        
        $array[23]="Insert into transactions_".$_SESSION["customer_dlr_id_of_user"]."(income,from_to,from_to_type,from_to_name,description,status,login_username,login_user_id,commission_for_fund_transfer,recharge_ref_id,transaction_ref_no_own_table,expense) values(0.00,".$_SESSION["customer_dlr_id_of_user"].",'Dealer','".$_SESSION["customer_dlr_name_of_user"]."','For Recharge','Commission Refund','".$_SESSION["user_name"]."',".$_SESSION["user_id"].",0.00,".$this->ref_id.",";// for dealer user

        // $array[24] ='CALL proc_failure_on_recharge('.$_SESSION['fund_transfer_commission'].','.$_SESSION['plan_id'].',"'.$this->operator_code.'",'.$this->recharge_amt.',"'.$array[20].'","'.$array[22].'",@msg)';

        // $array[25] ='CALL proc_failure_on_recharge('.$_SESSION['fund_transfer_commission'].','.$_SESSION['plan_id'].',"'.$this->operator_code.'",'.$this->recharge_amt.',"'.$array[21].'","'.$array[23].'",@msg)';//For Dealer User



         $array[24] ='CALL proc_failure_on_recharge('.$_SESSION['fund_transfer_commission'].','.$_SESSION['plan_id'].',"'.$this->operator_code.'",'.$this->recharge_amt.',"'.$array[20].'","'.$array[22].'",'.$this->ref_id.','.$_SESSION["customer_id"].',@msg)';

         $array[25] ='CALL proc_failure_on_recharge('.$_SESSION['fund_transfer_commission'].','.$_SESSION['plan_id'].',"'.$this->operator_code.'",'.$this->recharge_amt.',"'.$array[21].'","'.$array[23].'",'.$this->ref_id.','.$_SESSION["customer_id"].',@msg)';//For Dealer User

        

        $array[6] = "select count(*) as counts from recharge_request where api_response_code='TXF' and  update_date_time is not null and  recharging_number='".$this->number."' and request_id =".$this->ref_id;
        return $array;
    }



    function RequestAccept($FunctionEvents)
    {
        $var =  $this->SQLArray();

        switch ($FunctionEvents)
        {
            
            case 'OnRechargeSuccess':
               
               
                 $this->varModelObj->UpdateTable($var[4]);
                 
            break;
            case 'OnRechargePending':
            $this->varModelObj->UpdateTable($var[4]);
            break;
            case 'OnRechargeFailed':
                
             $this->response_check=$this->varModelObj->ReturnRowValue($var[6],'counts');
            
             if($this->response_check=='0')
             {  
                    $this->varModelObj->UpdateTable($var[4]);
                    if($_SESSION["user_type"]=='Users')
                    {
                        $this->varModelObj->ExecuteProcedure($var[25]);
                    }
                    else
                    {
                      
                        $this->varModelObj->ExecuteProcedure($var[24]);
                    }
             }
            break;

            
            
        }

    }

}

$obj = new mobileRechargeControlerForRecharge();






?>






















