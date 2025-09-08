<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml">

<head>

    <title></title>

    <script type="text/javascript">

        function CallPrint(strid) {
            var prtContent = document.getElementById(strid);
            var WinPrint = window.open('', '', 'left=0,top=0,width=600,height=900,toolbar=1,scrollbars=1,status=0');
            WinPrint.document.write('<html><head><title></title></head>');
            WinPrint.document.write('<body style="font-family:verdana; font-size:14px;width:423px;height:826px:" >');
            WinPrint.document.write(prtContent.innerHTML);
            WinPrint.document.write('</body></html>');
            WinPrint.document.close();
            WinPrint.focus();
            WinPrint.print();
            WinPrint.close();
            prtContent.innerHTML = "";
        }

    </script>
<style type="text/css">
@media print {
   @page{
    size: 4.4in 8.6in ;
    size: portrait;
  }
}
@media print {
  div#strid {
    width: 4.4in;/*width of index card*/
    height: 8.6in;/*height of index card*/
  }
  /* etc */
}
</style>
</head>
<!-- <body data-keep-enlarged="true" onLoad="javascript:window.print()"> -->
<body data-keep-enlarged="true" onLoad="CallPrint('strid')">
    <div id="strid" style="border: 1px solid ; width: 423px; height: 826px; background-color: #ccc">test info to print</div>
</body>
</html>
