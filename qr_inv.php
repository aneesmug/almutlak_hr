<?php
    include("./includes/qrcode/qrlib.php");

 function getTLV($dataToEncode) {
     $TLVS = '';
     for ($i = 0; $i < count($dataToEncode); $i++) {
         $tag = $dataToEncode[$i][0];
         $value = $dataToEncode[$i][1];
         $length = strlen($value);
         $TLVS .= pack("H*", sprintf("%02X", $tag)).pack("H*", sprintf("%02X", $length)).(string)$value ;
     }
     return $TLVS;
 }


       /*$company_name   = 'ﺐﺗﺎﻜﻤﻟا ﺕاﺰﻴﻬﺠﺘﻟ ﺔﻴﺑﺮﻌﻟا ﺔﻛﺮﺸﻟا';
       $vat_no         = '301289871300003';
       $totalAmnt      = '491';*/

       /*$company_name   = 'ﺔﻳﺭﺎﺠﺘﻟا ﻲﺑﺎﻴﺴﻧﻻا ﻂﺨﻟا ﺔﺴﺳﺆﻣ';
       $vat_no         = '300361766300003';
       $totalAmnt      = '11750';*/

       /*$company_name   = 'New Taef Information Techonology';
       $vat_no         = '310171430800003';
       $totalAmnt      = '1196';*/
 
       /*$company_name   = 'ﺐﺗﺎﻜﻤﻟا ﺕاﺰﻴﻬﺠﺘﻟ ﺔﻴﺑﺮﻌﻟا ﺔﻛﺮﺸﻟا';
       $vat_no         = '301289871300003';
       $totalAmnt      = '1950';*/

       /*$company_name   = 'New Taef Information Techonology';
       $vat_no         = '310171430800003';
       $totalAmnt      = '450';*/

       /*$company_name   = 'ﺷﺮﻛﺔ ﺟﺴﺮ اﻷﻣﺎﻥ اﻻﻣﻨﻴﺔ';
       $vat_no         = '311375321800003';
       $totalAmnt      = '460';*/

       /*$company_name   = 'ﺷﺮﻛﺔ ﺟﺴﺮ اﻷﻣﺎﻥ اﻻﻣﻨﻴﺔ';
       $vat_no         = '311375321800003';
       $date          = '2023-03-20T06:41:24+03:00';
       $totalAmnt      = '2200';*/

       /*$company_name   = 'New Taef Information Techonology';
       $vat_no         = '310171430800003';
       $date        = '2023-03-23T14:26:18+03:00';
       $totalAmnt      = '1760';*/

       /*$company_name   = 'ﺷﺮﻛﺔ ﺟﺴﺮ اﻷﻣﺎﻥ اﻻﻣﻨﻴﺔ';
       $vat_no         = '311375321800003';
       $date           = '2023-04-14T06:41:24+03:00';
       $totalAmnt      = '410';*/

       /*$company_name   = 'ﺷﺮﻛﺔ ﺟﺴﺮ اﻷﻣﺎﻥ اﻻﻣﻨﻴﺔ';
       $vat_no         = '311375321800003';
       $date           = '2023-05-09T15:49:54+03:00';
       $totalAmnt      = '740';*/

       $company_name   = 'New Taef Information Techonology';
       $vat_no         = '310171430800003';
       $date           = '2025-08-16T17:49:14+03:00';
       $totalAmnt      = '2036';
        
        $totalvat      = $totalAmnt / 100 * 15;
        $dataToEncode  = [
            [1, $company_name],
            [2, $vat_no],
            // [3, date('c')],
            [3, $date],
            [4, ($totalAmnt+$totalvat)],
            [5, $totalvat]
        ];
        $data = base64_encode(getTLV($dataToEncode));

 /*******************Saudi VAT E-Invoice QR*********************/
 QRcode::png($data, false, QR_ECLEVEL_L, 6, 1, false);
 /*******************Saudi VAT E-Invoice QR*********************/
?>