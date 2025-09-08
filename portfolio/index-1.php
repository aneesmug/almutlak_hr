<?php
  include("./../includes/db.php");
  $getquery = mysqli_query($conDB, "SELECT * FROM `employees` LEFT JOIN `portfolio` ON `portfolio`.`emp_id` = `employees`.`emp_id` WHERE `employees`.`id`='".$_GET['id']."' AND `employees`.`emp_id`='".$_GET['emp_id']."' ");

  if(mysqli_num_rows($getquery) !== 0){
    while($rec = mysqli_fetch_assoc($getquery)){
      $name_get = $rec["name"];
      $iqama_get = $rec["iqama"];
      $mobile_get = $rec["mobile"];
      $emp_avatar_get = $rec["avatar"];
      $emp_email_get = $rec['email'];
      $dept_get = $rec["dept"];
      $emptype_get = $rec["emptype"];
      $country_get = $rec["country"];
      $dob_get = $rec["dob"];
      $sex_get = $rec["sex"];
      $mar_status_get = $rec["mar_status"];
      $address_get = $rec["address"];
      $title_get = $rec['title'];
      $attachment_get = $rec['attachment'];
      $description_get = $rec['description'];
  }
} else {
    //when the id not equals id show database
    header("Location: ./../");
  }
?>
<!DOCTYPE html>
<html class="no-js bg-11 wf-raleway-n4-active wf-active" lang="en">
<head>
<meta charset="utf-8">
  <meta name="googlebot" content="noindex">
  <meta name="robots" content="noindex,nofollow">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <title><?=$name_get ?>'s Personal Website</title>
  <link data-react-helmet="true" rel="icon" type="image/svg+xml" href="data:image/svg+xml,&lt;svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22&gt;&lt;text y=%22.9em%22 font-size=%2290%22&gt;%F0%9F%91%8B&lt;/text&gt;&lt;/svg&gt;">

  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="./../assets/css/icons.css" rel="stylesheet" type="text/css" />
  <link rel="stylesheet" href="./bootstrap-reboot.css">
  <link rel="stylesheet" href="./bootstrap-grid-2021.css">
  <!-- <link rel="stylesheet" href=".//styles.min.css"> -->
  <link rel="stylesheet" href="./shared-website-css.css">
  
  <script src="./jquery.min.js"></script>

  <style type="text/css" id="less:api-templates-elmiz-css-color-orange">/* cyrillic-ext */
@font-face {
  font-family: 'Merriweather';
  font-style: normal;
  font-weight: 400;
  src: url(https://fonts.gstatic.com/s/merriweather/v30/u-440qyriQwlOrhSvowK_l5-cSZMZ-Y.woff2) format('woff2');
  unicode-range: U+0460-052F, U+1C80-1C88, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
}
/* cyrillic */
@font-face {
  font-family: 'Merriweather';
  font-style: normal;
  font-weight: 400;
  src: url(https://fonts.gstatic.com/s/merriweather/v30/u-440qyriQwlOrhSvowK_l5-eCZMZ-Y.woff2) format('woff2');
  unicode-range: U+0301, U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
}
/* vietnamese */
@font-face {
  font-family: 'Merriweather';
  font-style: normal;
  font-weight: 400;
  src: url(https://fonts.gstatic.com/s/merriweather/v30/u-440qyriQwlOrhSvowK_l5-cyZMZ-Y.woff2) format('woff2');
  unicode-range: U+0102-0103, U+0110-0111, U+0128-0129, U+0168-0169, U+01A0-01A1, U+01AF-01B0, U+0300-0301, U+0303-0304, U+0308-0309, U+0323, U+0329, U+1EA0-1EF9, U+20AB;
}
/* latin-ext */
@font-face {
  font-family: 'Merriweather';
  font-style: normal;
  font-weight: 400;
  src: url(https://fonts.gstatic.com/s/merriweather/v30/u-440qyriQwlOrhSvowK_l5-ciZMZ-Y.woff2) format('woff2');
  unicode-range: U+0100-02AF, U+0304, U+0308, U+0329, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
/* latin */
@font-face {
  font-family: 'Merriweather';
  font-style: normal;
  font-weight: 400;
  src: url(https://fonts.gstatic.com/s/merriweather/v30/u-440qyriQwlOrhSvowK_l5-fCZM.woff2) format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
/* latin-ext */
@font-face {
  font-family: 'Lato';
  font-style: normal;
  font-weight: 300;
  src: url(https://fonts.gstatic.com/s/lato/v24/S6u9w4BMUTPHh7USSwaPGR_p.woff2) format('woff2');
  unicode-range: U+0100-02AF, U+0304, U+0308, U+0329, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
/* latin */
@font-face {
  font-family: 'Lato';
  font-style: normal;
  font-weight: 300;
  src: url(https://fonts.gstatic.com/s/lato/v24/S6u9w4BMUTPHh7USSwiPGQ.woff2) format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
/* latin-ext */
@font-face {
  font-family: 'Lato';
  font-style: normal;
  font-weight: 400;
  src: url(https://fonts.gstatic.com/s/lato/v24/S6uyw4BMUTPHjxAwXjeu.woff2) format('woff2');
  unicode-range: U+0100-02AF, U+0304, U+0308, U+0329, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
/* latin */
@font-face {
  font-family: 'Lato';
  font-style: normal;
  font-weight: 400;
  src: url(https://fonts.gstatic.com/s/lato/v24/S6uyw4BMUTPHjx4wXg.woff2) format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
/* latin-ext */
@font-face {
  font-family: 'Lato';
  font-style: normal;
  font-weight: 700;
  src: url(https://fonts.gstatic.com/s/lato/v24/S6u9w4BMUTPHh6UVSwaPGR_p.woff2) format('woff2');
  unicode-range: U+0100-02AF, U+0304, U+0308, U+0329, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
/* latin */
@font-face {
  font-family: 'Lato';
  font-style: normal;
  font-weight: 700;
  src: url(https://fonts.gstatic.com/s/lato/v24/S6u9w4BMUTPHh6UVSwiPGQ.woff2) format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
/* latin-ext */
@font-face {
  font-family: 'Lato';
  font-style: normal;
  font-weight: 900;
  src: url(https://fonts.gstatic.com/s/lato/v24/S6u9w4BMUTPHh50XSwaPGR_p.woff2) format('woff2');
  unicode-range: U+0100-02AF, U+0304, U+0308, U+0329, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
/* latin */
@font-face {
  font-family: 'Lato';
  font-style: normal;
  font-weight: 900;
  src: url(https://fonts.gstatic.com/s/lato/v24/S6u9w4BMUTPHh50XSwiPGQ.woff2) format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
/* line 7, https://app.stylingcv.com/api/templates/elmiz/css/color-orange.less */
body {
  font-weight: 400;
}
/* line 20, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
body {
  margin: 0 auto !important;
  font-family: 'Lato', sans-serif;
  font-weight: 400;
  text-align: left;
  padding: 0;
  line-height: 1.4;
  color: #000;
  hyphens: auto;
  max-width: 100%;
  min-width: 100%;
}
/* line 37, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.tillNow {
  font-family: 'Lato', sans-serif;
}
/* line 40, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
h1 {
  margin-top: 20px;
  margin-bottom: 10px;
  font-family: 'Merriweather';
  font-size: 2em;
  line-height: 1.4;
  color: #ff6e00 !important;
  text-transform: uppercase;
  font-weight: bold;
}
/* line 51, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
h2 {
  margin-bottom: 7px;
  font-family: 'Merriweather';
  font-size: 1.4em;
  line-height: 1.4;
  font-weight: 400;
}
/* line 60, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
h3 {
  margin-bottom: 5px;
  font-family: 'Merriweather';
  font-size: 150%;
  font-weight: 600;
}
/* line 69, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
h4 {
  font-family: 'Lato', sans-serif;
  margin-bottom: 5px;
  font-size: 115%;
  line-height: 1.5;
  font-weight: 600;
}
/* line 79, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
h5 {
  font-family: 'Lato', sans-serif;
  font-size: 100%;
  margin-bottom: 5px;
  line-height: 1.3;
  font-weight: 500;
}
/* line 86, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
h6 {
  font-family: 'Lato', sans-serif;
  font-size: 100%;
  font-weight: 400;
}
/* line 109, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.topStrip {
  padding-bottom: 8px;
  padding-left: 7px;
  background: #002878;
  color: white;
  padding: 4px 20px;
  font-size: 90%;
}
/* line 120, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.section-profile {
  padding-left: 0px;
}
/* line 124, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.profile {
  margin: 0 0 10px 0;
}
/* line 127, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.contacts {
  margin-bottom: 10px;
  margin-top: 10px;
}
/* line 136, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.section-contacts li i.fa {
  display: inline-block;
  margin: 0 9px;
}
/* line 141, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.section-contacts {
  padding: 0;
  margin: 0;
}
/* line 146, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.section-contacts li span {
  color: #000;
}
/* line 151, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.section-contacts li {
  color: #ff6e00;
  padding: 9px !important;
  margin: 0;
}
/* line 158, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.leftchannel {
  padding: 18px !important;
  background: #d4d5d7;
  border-radius: 10px 0 0 10px;
}
/* line 167, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.rightchannel {
  padding: 16px !important;
  position: relative;
  border-radius: 0 10px 10px 0;
  background: rgba(87, 88, 90, 0.12);
}
/* line 174, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.rightchannel:before {
  border: solid #e2e2e2 1px;
  width: 1px;
  display: inline-block;
  position: absolute;
  left: 20px;
  top: 10px;
  bottom: 0;
}
/* line 185, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.heading-name {
  display: block;
  margin-bottom: 10px;
  margin-top: 0;
}
/* line 190, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.sameheader {
  padding: 0;
}
/* line 193, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.heading-position {
  margin-top: 0px;
  clear: both;
  color: #002878;
  font-weight: 400;
}
/* line 200, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.image {
  display: block;
  margin: 20px auto 20px;
  padding: 0;
  border: 10px solid rgba(255, 255, 255, 0.35);
}
/* line 209, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.ql-align-center {
  text-align: center;
}
/* line 213, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
h5.heading-5 {
  color: #002878;
}
/* line 219, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.article-bodyWarpper {
  margin-left: 20%;
  position: relative;
}
/* line 226, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.dateTop .article-bodyWarpper {
  margin: 0;
  position: relative;
}
/* line 232, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.article-bodyWarpper::before {
  content: "";
  border: solid #e2e2e2 1px;
  width: 1px;
  display: inline-block;
  position: absolute;
  left: -1px;
  top: -9px;
  bottom: -44px;
}
/* line 242, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.dateTop .article-bodyWarpper::before {
  display: none;
}
/* line 245, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.dateTop .date-range {
  padding: 0 !important;
}
/* line 250, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
section.section-wrapper {
  margin-bottom: 1em;
}
/* line 254, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.article-title {
  color: #ff6e00;
}
/* line 258, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
section header {
  width: 100%;
  position: relative;
  overflow: hidden;
  margin-bottom: 1em;
}
/* line 265, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
section header h3 {
  color: #002878;
  position: relative;
  padding: 4px 10px;
  text-transform: uppercase;
  font-weight: 600;
  vertical-align: top;
  /* background: #eaeaea; */
  border-bottom: solid 2px lightgrey;
}
/* line 280, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
section header h3 i.fa {
  float: right;
  color: #002878;
  font-size: 24px;
  text-align: center;
}
/* line 292, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
section header h3 span:before {
  position: absolute;
  left: 110%;
  display: block;
  border-top: solid #e8e8e8 2px;
  top: 48%;
  width: 1200px;
}
/* line 301, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
section header h3 span {
  position: relative;
}
/* line 307, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.rightchannel section header {
  width: 100%;
  position: relative;
  margin-bottom: 1em;
  overflow: hidden;
}
/* line 317, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
section header h3:after {
  content: " ";
  display: block;
  width: 40px;
  height: 2px;
  position: absolute;
  /* bottom: -2px; */
  left: 0px;
  z-index: 1;
  border-top: solid #ff6e00 2px;
  top: 100%;
}
/* line 333, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.article-dateWarpper {
  position: relative;
  float: left;
  width: 20%;
  text-align: right;
}
/* line 343, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.timeline.marked {
  display: inline-block;
  padding-right: 20px;
}
/* line 348, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.timeline.marked::after {
  content: "";
  position: absolute;
  width: 12px;
  height: 12px;
  box-shadow: white 0px 0px 0px 2px;
  top: 15px;
  right: -6px;
  z-index: 1;
  background: #002878;
  border-radius: 100%;
  margin-top: -7px;
}
/* line 362, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.date-range {
  font-size: 0.9em;
  font-family: sans-serif;
  padding-left: 15px;
  position: relative;
  font-weight: normal;
}
/* line 370, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.date-range::before {
  color: #4b4b4b !important;
  content: "\f073";
  width: 10px;
  height: 10px;
  line-height: 1.3;
  left: 1px;
  top: 8px;
  position: absolute;
  margin-right: 3px;
  font: normal normal normal 14px/1 FontAwesome;
  font-size: inherit;
  text-rendering: auto;
  -webkit-font-smoothing: antialiased;
  display: none;
  -moz-osx-font-smoothing: grayscale;
}
/* line 387, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
span[data-bind="start_date"] {
  white-space: nowrap;
  display: block;
}
/* line 391, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
span[data-bind="end_date"] {
  white-space: nowrap;
  display: inline-block;
}
/* line 396, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
[data-bind="end_date"]::before {
  content: "";
}
/* line 400, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.clientsGallery .col {
  margin-bottom: 15px;
}
/* line 411, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.interestsWrapper div {
  margin: 4px 8px;
  padding: 3px 11px;
  color: #fff;
  border: solid 1px #ff6e00;
  background: #002878;
  position: relative;
}
/* line 423, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.interestsWrapper div.col:before,
.interestsWrapper div.col:after {
  content: "";
  width: 24px;
  background: #ff6e00;
  -webkit-transform: skewX(-30deg);
  -ms-transform: skewX(-30deg);
  -moz-transform-origin: skewX(-30deg);
  -o-transform-origin: skewX(-30deg);
  transform: skewX(-30deg);
  display: inline-block;
  position: absolute;
  top: 0;
  right: -9px;
  display: none;
  bottom: 0;
}
/* line 441, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.interestsWrapper div.col:after {
  right: auto;
  left: -9px;
  bottom: 0;
}
/* line 450, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.lang-item {
  width: 50%;
  position: relative;
  padding: 0 10px;
}
/* line 456, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.small-channel .lang-item {
  width: 50%;
  position: relative;
}
/* line 461, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.small-channel .lang-item h6 {
  margin-bottom: 4px;
}
/* line 467, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.lang-item .w-200 {
  width: 120px;
}
/* line 470, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.small-channel .lang-item .w-200 {
  width: 200px;
}
/* line 475, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.lang-item .w-200 {
  width: 116px;
}
/* line 479, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.lang-item ul li {
  width: 15px;
  display: inline-block;
}
/* line 484, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.lang-item ul li:before {
  content: "\ece2";
  font-family: icomoon;
  color: #e6ebf0;
  font-size: 17px;
}
/* line 491, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.lang-item ul li.fillDots:before {
  color: #ff6e00;
}
/* line 497, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.hobbbyItemIcon,
.hobby-title {
  text-align: center;
}
/* line 502, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.hobbbyItemIcon {
  border-radius: 3px;
  color: #ff6e00;
  padding: 1px;
}
/* line 510, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.clientImg {
  border: solid #b5b5b5 1px;
  padding: 3px;
  border-radius: 4px;
  background: white;
  max-width: 175px;
}
/* line 522, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.progress {
  height: 4px;
  background: #002878;
  border-radius: 1px;
  -webkit-box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
  box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
  margin: 4px 0 0;
}
/* line 533, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.progress .progress-bar {
  box-shadow: none;
  height: 13px;
  position: relative;
  float: left;
  width: 0;
  font-size: 12px;
  line-height: 21px;
  color: #fff;
  text-align: center;
  background-color: #ff6e00;
  -webkit-box-shadow: inset 0 -1px 0 rgba(0, 0, 0, 0.15);
  box-shadow: inset 0 -1px 0 rgba(0, 0, 0, 0.15);
  -webkit-transition: width .6s ease;
  -o-transition: width .6s ease;
  transition: width .6s ease;
  margin-top: -5px;
  border-radius: 1px;
}
/* line 553, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.hobbiesWrapper .list-item-flex-100 {
  margin-bottom: 10px;
}
/* line 557, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.progress.pink .progress-bar:after {
  background: #002878;
}
/* line 562, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.progress .progress-bar:after {
  content: "";
  width: 20px;
  height: 20px;
  border: 4px solid #ff6e00;
  position: absolute;
  top: -6px;
  right: -3px;
  border-radius: 50%;
  display: none;
}
/* line 576, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.skillsWrapper ul li {
  margin-top: 7px;
}
/* line 583, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.small-channel .timeline-top h6 {
  display: inline;
}
/* line 587, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.timeline-top.col-auto {
  width: 120px;
}
/* line 592, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.timeline-top.col-auto h6 {
  font-size: 12px;
}
/* line 598, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.wide-channel .timeline-top {
  float: right;
  padding: 0 0 0 10px;
  font-size: 13px;
}
/* line 604, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.small-channel .timeline-top h6 {
  color: #002878;
}
/* line 609, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.skillsWrapper ul li {
  margin: 0 0 10px 0;
}
/* line 613, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.skillsWrapper ul li h5 {
  margin-bottom: 7px;
}
/* line 622, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.small-channel .timeline-top:before,
.location .fa-location {
  color: #656565;
}
/* line 627, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.d-flex .article-title {
  padding: 0;
}
/* line 632, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.sectionbullets ul {
  list-style: none;
  text-align: left;
  padding: 0 10px;
}
/* line 641, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.sectionbullets ul li {
  position: relative;
  padding-left: 20px;
}
/* line 646, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.sectionbullets ul li:before {
  content: "\ee76";
  position: absolute;
  color: #ff6e00;
  left: 0;
  display: inline-block;
  font-size: 16px;
  font-family: icomoon;
  font-size: inherit;
  text-rendering: auto;
  top: -1px;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
/* line 661, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.image {
  max-width: 200px !important;
}
/* line 667, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
[class^="iconHobbies-"],
[class*=" iconHobbies-"] {
  background: #ff6e00;
  color: white;
  border-radius: 50%;
}
/* line 673, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.donut-ring {
  stroke: #ff6e00 !important;
}
/* line 678, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.legend-item-color {
  background: #ff6e00 !important;
}
/* line 683, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
body[data-channel="one"] section header h3 {
  padding: 4px 0;
}
/* line 686, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.bg-primary {
  background-color: #ff6e00 !important;
  color: white;
}
/* line 690, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.bg-primary:hover {
  opacity: 0.8;
}
/* line 694, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.section-contacts a {
  color: #ff6e00;
}
/* line 698, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.section-contacts a i {
  border: solid 1px #ff6e00 !important;
  width: 35px;
  height: 35px;
  background-color: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  text-align: center;
  line-height: 33px;
  color: white;
}
/* line 710, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.section-contacts a:hover i {
  background-color: #ff6e00 !important;
  color: white;
}
/* line 716, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.pageWeb {
  padding: 70px 2.5% ;
  width: 100%;
  min-height: 100%;
  display: -webkit-box;
  display: -webkit-flex;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-pack: center;
  -webkit-justify-content: center;
  -ms-flex-pack: center;
  justify-content: center;
  -webkit-align-items: center;
  -webkit-box-align: center;
  -ms-flex-align: center;
  align-items: center;
  background-position: center;
  background-repeat: no-repeat;
  background-size: cover;
  overflow: auto;
  color: rgba(0, 0, 0, 0.85);
  word-break: normal;
  word-wrap: normal;
  -webkit-hyphens: none;
  -moz-hyphens: none;
  -ms-hyphens: none;
  hyphens: none;
  font-kerning: normal;
}
/* line 753, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.pageWebCont,
footer {
  max-width: 100%;
  margin: 0 auto;
}
/* line 758, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.pageWebCont {
  display: -webkit-box;
  display: -webkit-flex;
  display: -ms-flexbox;
  display: flex;
  margin-top: auto;
  margin-bottom: auto;
  width: 95%;
  -webkit-flex-direction: column;
  -ms-flex-direction: column;
  flex-direction: column;
  border-radius: .8rem;
  -webkit-animation: none;
  animation: none;
  -webkit-transition: width .3s;
  transition: width .3s;
}
/* line 778, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.white,
.section-contacts li span,
.summaryText {
  color: white !important;
}
/* line 785, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.home {
  position: relative;
  z-index: 1;
}
/* line 790, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.cd-headline.clip b {
  opacity: 0;
}
/* line 793, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.cd-headline.clip b.is-visible {
  opacity: 1;
}
/* line 796, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.home .copyright {
  position: absolute;
  bottom: 40px;
  margin: auto;
  width: 100%;
}
/* line 804, https://app.stylingcv.com/api/templates/elmiz/css/styles.less */
.btn {
  border-radius: 20px;
  padding: 8px 35px;
}
</style>
  <link rel="stylesheet" href="./margin-padding-bootstarp-en.css">
<style type="text/css">
  h1,
  h2,
  h3,
  h4 {

    font-family: 'Raleway' !important;

  }
</style></head>
<!-- <body data-lang="English" data-margin="rem" data-titles-icons="show"   data-channel="one" data-font-size="1.0em" data-font="arial" data-color="blue" data-type="WebSite" data-template-pro="false"  data-user-free="false"  class="body"> -->

<body data-background="orange" data-lang="English" data-margin="2rem" data-titles-icons="show" data-channel="one" data-font-size="1.0em" data-font="arial" data-color="blue" data-type="WebSite" data-template-pro="false" data-user-free="false" class="body" data-name-size="m" data-name-style="capitalize" data-hide-image="false" data-image-grayscale="" data-hide-contacticons="false" data-heading-style="uppercase" data-hide-headingicons="" cz-shortcut-listen="true">
  
<link rel="stylesheet" href="./jquery.animatedheadline.css">
<script src="./jquery.animatedheadline.min.js"></script>

<section class="home home-1 d-flex align-items-center">
  <div class="container center mb-5 pb-5" style="margin: 0 auto;">
    <div class="row">

      <div class="col-12 center">

        <div class="hideOnHideImg center">
          <div class="userPictrue center">
            <img src="../<?=$emp_avatar_get?>" class="image rounded" style="max-width:100%" alt="" />
          </div>
        </div>
        
        <div class="home-banner wow customFadeInUp delay-0-2s slow" style="visibility: visible; animation-name: customFadeInUp;">
          <h6 class="white">
            Hey , There!
          </h6>
          <div class="col col-12 nameHolder">
            <h1 class="heading-name cd-headline clip hidden">
              I Am
              <span class="cd-words-wrapper base-color" style="width: 180.45px;">
                <b class="sameheader white is-visible">
                  <?=(explode(" ",$name_get)[0])." ".(explode(" ",$name_get)[1])." ".(explode(" ",$name_get)[2]) ?>
                </b>
                <b class="sameheader white is-hidden"> <?=$dept_get." ".$emptype_get?>.
                </b>
              </span>
            </h1>
          </div>

          
  <section class="contacts w-100">
    <article class="section-contacts">
      <ul data-structure="horizontal" class="row no-gutters flex-row  justify-content-center">
        
        <li class="d-flex align-items-center"> <i class="fa fa-envelope" aria-hidden="true"></i>
          <span class="phone-label">
            <?=$emp_email_get?>
          </span>
        </li>
        <li class="d-flex align-items-center"> <i class="fa fa-phone" aria-hidden="true"></i>
          <span class="phone-label">
            <?=$mobile_get?>
          </span>
        </li> 
        <li class="d-flex align-items-center"><i class="fa fa-flag" aria-hidden="true"></i>
          <span class="Nationality-label">
            <?=$country_get?>
          </span>
        </li>   
        <li class="d-flex align-items-center"><i class="fa fa-vcard" aria-hidden="true"></i>
          <span class="IDnumber-label">
            <?=$iqama_get?>
          </span>
        </li> 
        <li class="d-flex align-items-center"><i class="fa fa-birthday-cake" aria-hidden="true"></i>
          <span class="DOB-label">
            <?=$dob_get?>
          </span>
        </li>
        <li class="d-flex align-items-center"> <i class="fa fa-venus-mars" aria-hidden="true"></i>
          <span class="DOB-label">
            <?=ucfirst($mar_status_get) ?>
          </span>
        </li>
        <li class="d-flex align-items-center"> <i class="fa fa-location" aria-hidden="true"></i>
          <span class="phone-label">
            <?=$address_get?>
          </span>
        </li>               

      </ul>

    </article>
  </section>

<div class="summaryText" style="text-align: justify;">
<h2>Welcome to my <?=$dept_get?>-related web-page!</h2>
<?=$description_get?>
</div>
          

          
          
<section class="contacts">

    <article class="section-contacts">
        <ul class="row no-gutters flex-row justify-content-center">
          <?php
              $socquery = mysqli_query($conDB, "SELECT `social_list`.*, `social`.* FROM `social_list` LEFT JOIN `social` ON `social`.`social_id` = `social_list`.`id` LEFT JOIN `employees` ON `social`.`emp_id` = `employees`.`emp_id` WHERE `employees`.`id`='".$_GET['id']."' ");
              while($rec = mysqli_fetch_assoc($socquery)){
                $mainlink = parse_url($rec['link']);
                $social = explode('//',$mainlink['host'])[0];
                $link = ucfirst(explode('.',$social)[0]);
            ?>
            <li class="d-flex align-items-center">
                <a href="<?=$rec['link']."".$rec['s_link']?>" target="_blank">
                    <i class="<?=$rec['icon']?>" aria-hidden="true"></i>
                </a>
            </li>            
            <?php } ?>
        </ul>

    </article>
</section>
          <div class="d-flex mt-3 justify-content-center">
            <a class="btn btn-sm  bg-primary m-0 my-2 btn-raised white d-flex w-auto" href="mailto:<?=$emp_email_get?>">
              <i class="fa fa-paperplane mr-2 align-self-center"></i>
              <span class="text-white text-left">Send me an email</span>
            </a>

            <?php if ($attachment_get): ?>
            <a class="btn btn-sm bg-primary m-0 my-2 btn-raised white d-flex w-auto ml-3"
              href="./../assets/emp_documents/<?=$attachment_get?>" download="myCV">
              <i class="fa fa-download mr-2 align-self-center"></i>
              <span class="text-white text-left">Download my resume</span>
            </a>
            <?php endif ?>

          </div>

        </div>
      </div>
    </div>
  </div>
  <div class="copyright">
    <div class="container">
<footer class="footer mt-5" id="userFooter">
  <div class="d-flex justify-content-between align-items-center">
    <div>
      <a href="javascript:void(0)" target="_blank">
        <span style="color:grey">SnapS Production House</span>
      </a>
      <!-- copyright -->
    </div>
</footer>
    </div>
  </div>
</section></body></html>