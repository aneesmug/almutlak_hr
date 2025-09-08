<?php
function paginate($reload, $page, $tpages) {
    $adjacents = 2;
    $prevlabel = "&lsaquo; Previous";
    $nextlabel = "Next &rsaquo;";
    $out = "";
    // previous
    if ($page == 1) {
        $out.= "<li class=\"page-item\"><a class=\"page-link\">" . $prevlabel . "</a></li>\n";
    } elseif ($page == 2) {
        $out.= "<li class=\"page-item\"><a class=\"page-link\" href=\"" . $reload . "\">" . $prevlabel . "</a>\n</li>";
    } else {
        $out.= "<li class=\"page-item\"><a class=\"page-link\" href=\"" . $reload . "&amp;page=" . ($page - 1) . "\">" . $prevlabel . "</a>\n</li>";
    }
  
    $pmin = ($page > $adjacents) ? ($page - $adjacents) : 1;
    $pmax = ($page < ($tpages - $adjacents)) ? ($page + $adjacents) : $tpages;
    for ($i = $pmin; $i <= $pmax; $i++) {
        if ($i == $page) {
            $out.= "<li class=\"page-item active\"><a class=\"page-link\" href=''>" . $i . "</a></li>\n";
        } elseif ($i == 1) {
            $out.= "<li class=\"page-item\"><a class=\"page-link\" href=\"" . $reload . "\">" . $i . "</a>\n</li>";
        } else {
            $out.= "<li class=\"page-item\"><a class=\"page-link\" href=\"" . $reload . "&amp;page=" . $i . "\">" . $i . "</a>\n</li>";
        }
    }
    
    if ($page < ($tpages - $adjacents)) {
        $out.= "<li class=\"page-item\"><a class=\"page-link\" href=\"" . $reload . "&amp;page=" . $tpages . "\">" . $tpages . "</a></li>\n";
    }
    // next
    if ($page < $tpages) {
        $out.= "<li class=\"page-item\"><a class=\"page-link\" href=\"" . $reload . "&amp;page=" . ($page + 1) . "\">" . $nextlabel . "</a>\n</li>";
    } else {
        $out.= "<li class=\"page-item\"><a class=\"page-link\">" . $nextlabel . "</a></li>\n";
    }
    $out.= "</ul>";
    return $out;
}
