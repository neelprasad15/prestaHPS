{*
    * 2007-2019 PrestaShop
    *
    * NOTICE OF LICENSE
    *
    * This source file is subject to the Academic Free License (AFL 3.0)
    * that is bundled with this package in the file LICENSE.txt.
    * It is also available through the world-wide-web at this URL:
    * http://opensource.org/licenses/afl-3.0.php
    * If you did not receive a copy of the license and are unable to
    * obtain it through the world-wide-web, please send an email
    * to license@prestashop.com so we can send you a copy immediately.
    *
    * DISCLAIMER
    *
    * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
    * versions in the future. If you wish to customize PrestaShop for your
    * needs please refer to http://www.prestashop.com for more information.
    *
    *  @author    PrestaShop SA <contact@prestashop.com>
    *  @copyright 2007-2020 PrestaShop SA
    *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
    *  International Registered Trademark & Property of PrestaShop SA
    *}
{extends file='page.tpl'}
{block name='page_content'}


<p>Please wait, A popup will appear soon...</p>
<div class="container">
	<div class="table-responsive">
		<table class="table table-sm">
			<thead>
				<tr>      
					<th scope="col">Order ID</th>
					<th scope="col">Amount</th>
					<th scope="col">Currency</th>					
				</tr>
			</thead>
			<tbody>
				<tr>      
					<td>{$merchant_ref_number}</td>
					<td>{$amount}</td>
					<td>{$currencydesc}</td>					
				</tr>
			</tbody>
		</table>
	</div>
</div>

<div id="loader" style="
background-image: url('https://www.latpay.com.au/wp-content/uploads/2019/12/processing.gif')!important; 
background-position: center 30% !important;
background-repeat: no-repeat !important;          
position: fixed;
top: 0px;
bottom: 0;
left: 0;
right: 0;
background: rgba(90, 90, 90, 0.5);
z-index: 9999;
display: none;
"></div>
<form name="mygatewayform" id="latpay_payment_form" method="post" action="{$link->getModuleLink('Latpay', 'validation', array())}" >
	<input type="submit" name="submit" id="submit_latpay_payment_form" style="display: none" value="Submit">
</form>		
<form name="cancelform" id="cancel_order" method="post" action="{$link->getModuleLink('Latpay', 'validation', array())}">
	<input type="submit" id="cancel_submit" value="submit" style="display: none">
</form>	


<script type="text/javascript">
	{literal}

	var $Merchant_User_Id ='{/literal}{$Merchant_User_Id}{literal}';
	var $merchantpwd ='{/literal}{$merchantpwd}{literal}';
	var $currencydesc ='{/literal}{$currencydesc}{literal}';
	var $amount ='{/literal}{$amount}{literal}';
	var $processurl ='{/literal}{$processurl}{literal}';
	LatpayCheckout.open({
		merchantid : $Merchant_User_Id,
		publickey  : $merchantpwd,
		currency   : $currencydesc,
		amount     : $amount,
		reference  : 'Test1234_ecommerce',
		description: 'Test1234_ecommerce',
		token: function (token) {
			if(token == "1"){				
				var $token = 
				$('<input/>', { type: 'hidden', id:' Token', name: "token", value: "1" });
				$("#cancel_order").append($token);
				$("#cancel_submit").trigger("click");				
			}
			else{
				var $Transtokenval = 
				$('<input/>', { type: 'hidden', id:' TransToken', name: "transtokenval", value: token.TransToken });
				var $Status        = 
				$('<input/>', { type: 'hidden', id:' status', name: "status", value: token.Status });
				var $Amount        = 
				$('<input/>', { type: 'hidden', id:' amount', name: "amount", value: token.Amount });
				var $Currency      = 
				$('<input/>', { type: 'hidden', id:' currency', name: "currency", value: token.Currency });
				var $Merchantid    = 
				$('<input/>', { type: 'hidden', id:' merchantid', name: "merchantid", value: token.merchantuserid });
				var $Datakey  =
				$('<input/>', { type: 'hidden', id:' datakey', name: "datakey", value: 'skhjhdashgau' });
				var $Decription    = 
				$('<input/>', { type: 'hidden', id:' description', name: "description", value: token.Description });
				var $Reference     = 
				$('<input/>', { type: 'hidden', id:' reference', name: "reference", value: token.Reference });

				$("#latpay_payment_form").append($Transtokenval, $Status, $Amount, $Currency, $Datakey, $Merchantid,$Decription, $Reference);

				if(token.TransToken){
					$("#submit_latpay_payment_form").trigger("click");
					$("#loader").css("display","block");
				}				

			}
		}
	});
	{/literal}
</script>
{/block}