<?php
 require_once __DIR__ . '/includes/db.php';
 require_once __DIR__ . '/includes/session_check.php';
 $query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
 if(mysqli_num_rows($query) == 1){
 include("./includes/avatar_select.php");
 include("./includes/Hijri_GregorianConvert.php");
 include("./includes/convertNumbersToWords.php");
 $DateConv=new Hijri_GregorianConvert;
 $format="DD/MM/YYYY";
 

if ($user_type == 'administrator') {
 $getquery = mysqli_query($conDB, "
  SELECT `vouchers`.*, 
       `employee_to`.`name` AS `name`, 
       `employee_from`.`name` AS `emp_from`
    FROM `vouchers` 
    LEFT JOIN `employees` AS `employee_to` ON `employee_to`.`emp_id` = `vouchers`.`to_emp`
    LEFT JOIN `employees` AS `employee_from` ON `employee_from`.`emp_id` = `vouchers`.`emp_id`
    WHERE `vouchers`.`id`='".$_GET['id']."' ");
} else {
 $getquery = mysqli_query($conDB, "
    SELECT `vouchers`.*, 
       `employee_to`.`name` AS `name`, 
       `employee_from`.`name` AS `emp_from`
    FROM `vouchers` 
    LEFT JOIN `employees` AS `employee_to` ON `employee_to`.`emp_id` = `vouchers`.`to_emp`
    LEFT JOIN `employees` AS `employee_from` ON `employee_from`.`emp_id` = `vouchers`.`emp_id`
    WHERE `vouchers`.`dept` = '$user_dept' AND `vouchers`.`id`='".$_GET['id']."' ");
}


 if(mysqli_num_rows($getquery) !== 0){
  while($rec = mysqli_fetch_assoc($getquery)){
   $id = $rec["id"];
      $emp_id = $rec["emp_id"];
      $to_emp = $rec["to_emp"];
      $voucher_no = $rec["voucher_no"];
      $voucher_type = $rec["voucher_type"];
      $voucher_amount = $rec["voucher_amount"];
      $acc_no = $rec["acc_no"];
      $chq_no = $rec["chq_no"];
      $details = $rec["details"];
      $name = $rec["name"];
      $emp_from = $rec["emp_from"];
      $created_at = $rec["created_at"];
 } 
} else {
  //when the id not equals id show database
  header("Location: ./vouchers.php");
 }

 $v = strtoupper(str_split($voucher_type)[0]);
 $payarb = ($voucher_type == 'payment')?"ﺳﻨﺪﺻﺮاﻑ":"ﺳﻨﺪﻗﺒﺾ";
 $payeng = ($voucher_type == 'payment')?"Payment":"Receipt";
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Voucher page - <?= $site_title ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="https://netdna.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">
        
        <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Kufi+Arabic:wght@600&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">
        
        <style type="text/css">
            body{
                margin-top:10px;
                background:#eee;    
            }
   .arb{
    font-family: 'Noto Kufi Arabic', sans-serif;
    direction:rtl;
            }
            .paytitle{
             font-size: 38px;
    font-family: 'Amiri', serif;
   }
   .artitl{
    font-family: 'Amiri', serif;
    direction:rtl;
   }
        </style>
    </head>
    <body onload="printDiv()">
    <!-- <body> -->
        <div class="container bootdey">
            <div class="row invoice row-printable" id="dvContents">
                <div class="col-md-10">
                    <div class="panel panel-default plain" id="dash_0">
                        <div class="panel-body p30">
                            <div class="row">
                                <div class="col-lg-12">

                                 <table>
                                  <tr>
                                   <td width="40%">
                                    <h4>
                                           United Company for Coffee & Chocolate Trading Ltd.
                                          </h4>
                                         </td>
                                   <td width="20%">
                                       <div class="invoice-logo text-center" style="margin-bottom: 15px;">
                                           <img width="100" src="assets/images/LogoBrownTxt.jpg" alt="Invoice logo">
                                       </div>
                                   </td>
                                   <td width="40%">
                                    <h4 class="text-right">
                                     <span class="arb">
                                      اﻟﺸﺮﻛﺔ اﻟﻤﺘﺤﺪﺓ ﻟﺘﺠﺎﺭﺓ اﻟﺒﻦ ﻭاﻟﺸﻮﻛﻮﻻﺗﺔ اﻟﻤﺤﺪﻭﺩﺓ
                                     </span>
                                          </h4>
                                   </td>
                                  </tr>
                                 </table>

                                </div>
                                <div class="col-lg-12">
                                    <div class="invoice-details mt25">
                                        <div class="well">
                                         <table width="100%">
                                          <tr>
                                           <td width="40%">
                                            <strong>Voucher No</strong> #<?=$voucher_no;?>
                                           </td>
                                           <td class="text-center" width="20%" rowspan="2">
                                            <strong class="paytitle"><?=$payarb?></strong>
                                            <strong style="font-size: 15px;"><?=$payeng?> Voucher</strong>
                                           </td>
                                           <td class="text-right" width="40%">
                                            <strong>Date:</strong> <?=date('l d, F Y', strtotime($created_at));?>
                                           </td>
                                          </tr>
                                          <tr>
                                           <td width="40%">
                                            <strong>Amount</strong> 
                                            <span class="text-danger">#SR <?=$voucher_amount;?>#</span>
                                           </td>
                                           <td class="text-right" width="40%">
                                            <strong>Hijri Date:</strong> <?=$DateConv->GregorianToHijri(date('d/m/Y', strtotime($created_at)), $format)?>
                                           </td>
                                          </tr>
                                         </table>
                                        </div>
                                    </div>
                                    <div class="invoice-items">
                                        <div class="table-responsive" style="overflow: hidden; outline: none;" tabindex="0">
                                            <table class="table table-bordered">
                                                <tbody>
                                                    <tr>

                                                        <?php if ($voucher_type == 'receipt'): ?>
                                                            <th class="per70 text-left">Receipt From:</th>   
                                                        <?php else: ?>
                                                            <th class="per70 text-left">Pay To:</th>   
                                                        <?php endif ?>

                                                        <td class="per70 text-center"><?=$name ?></td>

                                                        <?php if ($voucher_type == 'receipt'): ?>
                                                            <th class="per70 text-right artitl">الاستلام من:</th>   
                                                        <?php else: ?>
                                                            <th class="per70 text-right artitl">ﺇﺩﻓﻌﻮاﻷﻣﺮ:</th>
                                                        <?php endif ?>


                                                    </tr>
                                                    <tr>
                                                        <th class="per25 text-left">The Sum of:</th>
                                                        <td class="per25 text-center"><?=strtoupper(getSaudiCurrency($voucher_amount))?></td>
                                                        <th class="per25 text-right artitl">ﻣﺒﻠﻎ ﻭﻗﺪﺭﻩ:</th>
                                                    </tr>
                                                    <tr>
                                                        <th class="per70 text-left">Paid for:</th>
                                                        <td class="per70 text-center"><?=$details?></td>
                                                        <th class="per70 text-right artitl">ﻣﻼﺣﻈﺎﺕ:</th>
                                                    </tr>
                                                    <?php if($chq_no !== ""): ?>
                                                    <tr>
                                                        <th class="per70 text-left">Cash / Cheque No.:</th>
                                                        <td class="per70 text-center"><?=$chq_no?></td>
                                                        <th class="per70 text-right artitl">ﻧﻘﺪاً / ﺑﻤﻮﺟﺐ ﺷﻴﻚ ﺭﻗﻢ:</th>
                                                    </tr>
                                                 <?php endif ?>
                                                 <?php if($acc_no !== ""): ?>
                                                    <tr>
                                                        <th class="per70 text-left">Account No:</th>
                                                        <td class="per70 text-center"><?=$acc_no?></td>
                                                        <th class="per70 text-right artitl">ﻋﻠﻰ ﺣﺴﺎﺏ:</th>
                                                    </tr>
                                                 <?php endif ?>

                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="invoice-footer mt25" style="font-size: 10px; text-transform: uppercase !important;">
                                        <p class="text-center" style="font-size: 10px;">Voucher issued from <?=$emp_from?></p>
                                        <p class="text-center">Generated on <?=date('l d, F Y', strtotime($created_at));?> 
                                            <a href="javascript:void(0);" onclick="printDiv()" class="btn btn-default ml15 noprint"><i class="fa fa-print mr5"></i> Print</a>
                                        </p>
                                    </div>
                                    </div>

                            </div>

                        </div>
                    </div>

                </div>

            </div>
        </div>
    <script src="https://code.jquery.com/jquery-1.10.2.min.js"></script>
    <script src="https://netdna.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
    <script type="text/javascript">
            function printDiv() {    
                var divToPrint = document.getElementById('dvContents');
                var popupWin = window.open();
                popupWin.document.write(`
                    <html>
                        <link href="https://netdna.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">
                        <link href="https://fonts.googleapis.com/css2?family=Noto+Kufi+Arabic:wght@600&display=swap" rel="stylesheet">
                        <link href="https://fonts.googleapis.com/css2?family=Amiri&display=swap" rel="stylesheet">
      <style>
       @page {
        size: A4;
        margin: 0.5mm;
       }
       @media print {
        .arb{
         font-family: 'Noto Kufi Arabic';
         direction:rtl;
        }
        body {
         font-size: 12px;
        }
        th { 
         font-weight: bold; 
        }
         td, th { 
         font-size: 12px;
        }
        .noprint{
         display:none;
        }
        .paytitle{
         font-size: 38px;
         font-family: 'Amiri', serif;
        }
        .artitl{
         font-family: 'Amiri', serif;
         direction:rtl;
        }
       }
      </style>
                        <body>${divToPrint.innerHTML}</body>
                    </html>
                    `);
                popupWin.document.close();
                popupWin.focus();
                popupWin.print();
                popupWin.close();
                setTimeout(
                 window.close, 0
                );
            }
        </script>
    </body>
</html>
<?php } ?>