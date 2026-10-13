<?php
use JeroenDesloovere\VCard\VCard;
class VcardExport
{
    public function contactVcardExportService($contactResult)
    {
        require_once 'vendor/SnapS-Transliterator/Transliterator.php';
        require_once 'vendor/jeroendesloovere-vcard/VCard.php';
        // define vcard
        $vcardObj = new VCard();
        // add personal data
        $vcardObj->addName((explode(" ",$contactResult[0]["name"])[0])." ".(explode(" ",$contactResult[0]["name"])[1]));
        // $vcardObj->addBirthday($contactResult[0]["dob"]);
        $vcardObj->addEmail($contactResult[0]["c_email"]);
        $vcardObj->addPhoneNumber($contactResult[0]["mobile"]);
        $vcardObj->addJobtitle($contactResult[0]["emptype"]);
        $vcardObj->addURL("https://www.almutlak.com/");
        // $vcardObj->addAddress($contactResult[0]["address"]);
        $vcardObj->addCompany("Al Mutlak Co.", $contactResult[0]["dept"]);
        return $vcardObj->download();
    }
}