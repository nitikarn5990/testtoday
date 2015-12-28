<script src="http://malsup.github.io/jquery.blockUI.js"></script>
<?php
//$input = "123456";
//
//$encrypted = encryptIt( $input );
//$decrypted = decryptIt( $encrypted );

//echo $encrypted . '<br />' . $decrypted;

function encryptIt( $q ) {
    $cryptKey  = 'qJB0rGtIn5UB1xG03efyCp';
    $qEncoded      = base64_encode( mcrypt_encrypt( MCRYPT_RIJNDAEL_256, md5( $cryptKey ), $q, MCRYPT_MODE_CBC, md5( md5( $cryptKey ) ) ) );
    return( $qEncoded );
}

function decryptIt( $q ) {
    $cryptKey  = 'qJB0rGtIn5UB1xG03efyCp';
    $qDecoded      = rtrim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, md5( $cryptKey ), base64_decode( $q ), MCRYPT_MODE_CBC, md5( md5( $cryptKey ) ) ), "\0");
    return( $qDecoded );
}

//echo $decrypted = decryptIt("XnCOFXzvzFGHXS/GZ5kVEZ9PAE2N+oCeqydK87yGuwo=");

session_start();

if (count($_SESSION["strProductID"]) == 0) {

    header("location:" . ADDRESS . "product");
}

if ($_POST['btn_submit'] == 'ดำเนินการต่อ') {

    $cpt = $_POST['capt'];
    if ($cpt != $_SESSION['CAPTCHA']) {
        ?>


        <script>
            $(document).ready(function () {
                alert('Error code');
            });
        </script>

        <?php
    } else {
     
        $arrData = array();



        $arrData = $functions->replaceQuote($_POST);
        $orders->SetValues($arrData);
        if ($orders->GetPrimary() == '') {


            $orders->SetValue('created_at', DATE_TIME);


            $orders->SetValue('updated_at', DATE_TIME);
        } else {


            $orders->SetValue('updated_at', DATE_TIME);
        }
        $orders->SetValue('order_date', DATE_TIME);
        $orders->SetValue('status', 'รอการชำระเงิน');
        $year_bill = substr(intval(date('Y')) + intval(543), 2);

        $orders->SetValue('years', $year_bill);
        $orders->SetValue('months', date('m'));
        if ($orders->Save()) {
            $orders_id = $orders->GetValue('id');
            for ($i = 0; $i <= (int) $_SESSION["intLine"]; $i++) {


                if ($_SESSION["strProductID"][$i] != "") {


                    $strSQL = "SELECT * FROM products WHERE id = " . $_SESSION["strProductID"][$i] . "";
                    $objQuery = mysql_query($strSQL) or die(mysql_error());
                    $objResult = mysql_fetch_array($objQuery, MYSQL_ASSOC);
                    $qty = $_SESSION["strQty"][$i];
                    $Total = $qty * $objResult["product_cost"];
                    $SumTotal = $SumTotal + $Total;
                    $_SESSION["Total"] = $SumTotal;


                    $orders_detail->SetValue('orders_id', $orders_id);
                    $orders_detail->SetValue('product_id', $_SESSION["strProductID"][$i]);
                    $orders_detail->SetValue('qty', $qty);
                    $orders_detail->SetValue('cost', $objResult["product_cost"]);
                    $orders_detail->SetValue('total', $Total);
                    if ($orders_detail->save()) {
                        
                    }
                }
            }
            require_once($_SERVER["DOCUMENT_ROOT"].'/phpmailer/class.phpmailer.php');


            $mail = new PHPMailer();
            $mail->IsHTML(true);
            $mail->IsSMTP();
            $mail->SMTPAuth = true; // enable SMTP authentication
            $mail->SMTPSecure = "ssl"; // sets the prefix to the servier
            $mail->Host = "smtp.gmail.com"; // sets GMAIL as the SMTP server
            $mail->Port = 465; // set the SMTP port for the GMAIL server
            
            $mail->Username = "testmailshop@gmail.com"; // บัญชีสำหรับส่งเมล์ Gmail
            $mail->Password = "testmailshop1234"; //  รหัส GMAIL
            $mail->FromName = "beauty-bykk.com";  // ชื่อผู้ส่ง
            $mail->Subject = "รายละเอียดการสั่งซื้อสินค้า"; //ชื่อเรื่อง
            $mail->AddCC('wdchiangmai@gmail.com', 'ชื่อ'); //สำเนาส่งถึง เมล์เจ้าของร้าน
            $mail->AddReplyTo('wdchiangmai@gmail.com', 'ชื่อ'); //ตอบกลับถึง เมล์เจ้าของร้าน
     
            $mail->CharSet = "utf-8";        
            $logo = ADDRESS . 'images/logo.png';
            $body = "";
            $detail = "";
            $name = $orders->getDataDesc("name", "id = " . $orders_id);
            $address = $orders->getDataDesc("address", "id = " . $orders_id);
            $tel = $orders->getDataDesc("tel", "id = " . $orders_id);
            $email = $orders->getDataDesc("email", "id = " . $orders_id);

            $order_no = $orders->getDataDesc("years", "id = " . $orders_id) . $functions->padLeft($orders->getDataDesc("months", "id = " . $orders_id), 2, '0') . $functions->padLeft($orders_id, 5, '0');
            $SumTotal = 0;
            $amt = 0;
            for ($i = 0; $i <= (int) $_SESSION["intLine"]; $i++) {


                if ($_SESSION["strProductID"][$i] != "") {

                    $strSQL = "SELECT * FROM products WHERE id = " . $_SESSION["strProductID"][$i] . "";
                    $objQuery = mysql_query($strSQL) or die(mysql_error());
                    $objResult = mysql_fetch_array($objQuery, MYSQL_ASSOC);
                    $qty = $_SESSION["strQty"][$i];
                    $Total = $qty * $objResult["product_cost"];
                    $SumTotal = $SumTotal + $Total;
                    $amt = $amt + $Total;
                    $_SESSION["Total"] = $SumTotal;


                    $detail .= "<tr>
                                <td class='pro-id' style='text-align: center;font-size: 14px;'><img src=" . ADDRESS . 'images/' . $objResult["products_file_name_cover"] . " style='width:70px;' /></td>
                                <td class='pro-desc' style='text-align: center;font-size: 14px;'>" . $objResult["product_name"] . "</td>
                                <td class='pro-price' style='text-align: center;font-size: 14px;'>" . $functions->formatcurrency($objResult["product_cost"]) . "</td>
                                <td class='quantity' style='text-align: center;font-size: 14px;'>" . $qty . "</td>
                                <td class='sumprice' style='text-align: center;font-size: 14px;'>" . $functions->formatcurrency(($Total)) . "</td>
                            </tr>";
                }
            }


            $my_body = "<table align='center' border='0' cellpadding='0' cellspacing='5' width='100%'>
	<tbody>
		<tr>
			<td> 
			<p style='text-align: center;'><img alt='' src='" . $logo . "' style='width: 150px; height: 104px;' /></p>
			</td>
		</tr>
		<tr>
			<td>
			<table border='0' cellpadding='0' cellspacing='1' width='100%'>
				<tbody>
					<tr>
						<td>
						<table border='0' cellpadding='0' cellspacing='1' width='100%'>
							<tbody>
							</tbody>
						</table>

						<table border='0' cellpadding='0' cellspacing='0' width='100%'>
							<tbody>
								<tr>
								</tr>
								<tr>
									<td style='width:170px;height:30px;background-color:rgb(0,0,0)'>
									<div style='text-align:center'><font color='#ffffff'>ข้อมูลการสั่งซื้อสินค้า</font></div>
									</td>
									<td style='background-color:#FFB7CE'>&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td bgcolor='#efefef'>
						<table border='0' cellpadding='0' cellspacing='5' width='100%'>
							<tbody>
								<tr>
									<td width='60%'>
									<div>
									<ul>
										<li style='color:#000'>หมายเลขสั่งซื้อ : &nbsp; &nbsp;<strong>" . $order_no . "</strong></li>
										<li style='color:#000'>ชื่อ-สกุล : &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp;&nbsp;&nbsp;<strong> " . $name . "</strong></li>
										<li style='color:#000'>ที่อยู่ในการจัดส่ง &nbsp;:&nbsp;<strong>" . $address . "</strong></li>
										<li style='color:#000'>เบอร์ติดต่อ : &nbsp; &nbsp; &nbsp; &nbsp;<strong> " . $tel . "</strong></li>
										<li style='color:#000'>Email &nbsp;: &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp;&nbsp; " . $email . "</li>
										<li style='color:#000'>รายการสินค้า​ &nbsp;:
										<table border='1' cellpadding='0' cellspacing='0' class='item-list' style='width: 100%;border-collapse: collapse; border: 1px solid #D4D4D4;'>
											<tbody>
												<tr>
													<th class='hidden' style='text-align: center;'>ภาพสินค้า</th>
													<th style='text-align: center;'>ชื่อสินค้า</th>
													<th style='text-align: center;'>ราคา/หน่วย</th>
													<th style='text-align: center;'>จำนวน</th>
													<th style='text-align: center;'>ราคารวม</th>
												</tr>
												" . $detail . "
											</tbody>
										</table>
										</li>
										<li style='color:#000'>จำนวนเงินรวม&nbsp;: <span style='color:#FF0000;'>" . $functions->formatcurrency($amt) . "</span> บาท</li>
									</ul>
									</div>
									</td>
								</tr>
								<tr>
									<td width='60%'>&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
				</tbody>
			</table>
			</td>
		</tr>
		<tr>
			<td>
			<p>ขอแสดงความนับถือ</p>

			<p><a href='http://beauty-bykk.com' target='_blank'>http://beauty-bykk.com</a><br />
			โทร.081-7948894<br />
			&nbsp;</p>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
	</tbody>
</table>";

            $mail->Body = $my_body;
            $mail->AddAddress($email); // to Address
           
            $mail->set('X-Priority', '1'); //Priority 1 = High, 3 = Normal, 5 = low

            if (!$mail->Send()) {
                echo "<script>alert('ส่งไม่สำเร็จสำเร็จ !!!')</script>";
                echo "<script>$.unblockUI()</script>";
            } else {
                echo "<script>alert('ส่งสำเร็จ !!!')</script>";
                echo "<script>$.unblockUI()</script>";
                header("location:" . ADDRESS . "success/" . $orders_id);
            }

//////////
            //  $email .= ','.'wdchiangmai@gmail.com';
//            $strTo = $email;
//            $strSubject = "รายละเอียดการสั่งซื้อสินค้า";
//            $strHeader = "From: beauty-bykk.com";
//            $strMessage = $my_body;
//            $flgSend = mail($strTo, $strSubject, $strMessage, $strHeader);  // @ = No Show Error //
//            if (!$flgSend) {
//                echo "<script>alert('ส่งไม่สำเร็จสำเร็จ !!!')</script>";
//            } else {
//                echo "<script>alert('ส่งสำเร็จ !!!')</script>";
//                header("location:" . ADDRESS . "success/" . $orders_id);
//            }
/////////////
        }
    }
    ?>
<?php } ?>
     
<div class="block-middle" style="padding-left: 263px;">
    <form id="shippingForm" name="shippingForm" method="post"  action="<?= ADDRESS ?>shipping">
        <table>
            <tbody><tr>
                    <td colspan="3" style="height:5px"></td>
                </tr>
                <tr>
                    <td colspan="3"><input name="" type="radio" value="" checked="checked">
                        กรอกชื่อที่อยู่ในการจัดส่งสินค้า</td>
                </tr>
                <tr>
                    <th>ชื่อ - นามสกุล  :</th>
                    <td class="c-red">*</td>
                    <td>
                        <input name="name" type="text" class="medium" id="name" value="<?= $_POST['name'] != '' ? $_POST['name'] : '' ?>" required="">
                        <div class="c-red"></div>
                    </td>
                </tr>
                <tr>
                    <th>ที่อยู่ :</th>
                    <td class="c-red">*</td>
                    <td><input name="address" type="text" class="medium" id="address" value="<?= $_POST['address'] != '' ? $_POST['address'] : '' ?>" required="">
                        <div class="c-red"></div>

                </tr>
                <tr>
                    <th>จังหวัด :</th>
                    <td class="c-red">*</td>
                    <td>
                        <input name="province" type="text" class="medium" id="province" value="<?= $_POST['province'] != '' ? $_POST['province'] : '' ?>" required="">
                        <div class="c-red"></div>
                    </td>
                </tr>

                <tr>
                    <th>รหัสไปรษณีย์ :</th>
                    <td class="c-red">*</td>
                    <td>
                        <input name="zipcode" type="text" maxlength="5" class="short onlyint" maxlength="5" id="zipcode" value="<?= $_POST['zipcode'] != '' ? $_POST['zipcode'] : '' ?>" required="">
                        <div class="c-red"></div>
                    </td>
                </tr>
                <tr>
                    <th>เบอร์ติดต่อ :</th>
                    <td class="c-red">*</td>
                    <td>
                        <input name="tel" type="text" class="medium onlyint" id="tel" value="<?= $_POST['tel'] != '' ? $_POST['tel'] : '' ?>" maxlength="10" required="">
                        <div class="c-red"></div>
                    </td>
                </tr>
                <tr>
                    <th>E-mail :</th>
                    <td class="c-red">*</td>
                    <td>
                        <input name="email" type="email" class="medium" id="myemail" value="<?= $_POST['email'] != '' ? $_POST['email'] : '' ?>" required="">
                        <div class="c-red"></div>
                    </td>
                </tr>
                <tr>
                    <th>รายละเอียดอื่นๆ :</th>
                    <td class="c-red">&nbsp;</td>
                    <td><textarea name="other" id="other"><?= $_POST['other'] != '' ? $_POST['other'] : '' ?></textarea></td>
                </tr>
                <tr>
                    <td > Enter Code</td>
                    <td class="c-red">*</td>
                    <td>
                        <p>
                            <input name="capt" id="capt" required="" type="text"> <img src="image_capt.php" id="mycapt" align="absmiddle">
                            <i id="changeCpt" style="cursor: pointer;" class="fa fa-refresh"></i>

                        </p>
                    </td>
                </tr>
                <tr>
                    <th>&nbsp;</th>
                    <td>&nbsp;</td>
                    <td><input type="submit" name="btn_submit" value="ดำเนินการต่อ">
                        <input name="saveContact" type="hidden" id="saveContact" value="0"></td>
                </tr>
            </tbody></table>
    </form>
</div>
<style>
    .c-red{
        color: red;
    }

</style>
<script type="text/javascript">



    $('#changeCpt').click(function (e) {
        var v = Math.random();
        $('#mycapt').attr('src', 'image_capt.php?v=' + v);
    });

            $( "#shippingForm" ).submit(function( event ) {
                $.blockUI({ message: '<h1 style=color:#000><i class="fa fa-spinner fa-pulse"></i> รอสักครู่...</h1>' });
              
              });
</script>
