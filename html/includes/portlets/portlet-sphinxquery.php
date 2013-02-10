<?php

/*
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2009 gdd.net
 * All rights reserved.
 *
 * Changelog:
 * 2009-12-13 - created
 *
 */

session_start();
$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
if ((has_portlet_access($_SESSION['username'], 'Messages') == TRUE) || ($_SESSION['AUTHTYPE'] == "none")) {
?>
<table border="0" width="100%">
    <td width="95%">
        <input placeholder="Enter search term or click ? for syntax" type="text" style="width: 95%; text-align: left; position: relative; left: 3%;" class="rounded_textbox ui-widget ui-corner-all" name="msg_mask" id="msg_mask" size=30>
        <?php if (($_SESSION['SPX_ADV'] == "1") && ($_SESSION['SPX_ENABLE'] == "1")) {?>
        <div style="width: 95%; text-align: left; position: relative; left: 3%;">
            <input type="radio" name="q_type" value="any" /> Any
            <input type="radio" name="q_type" value="all" /> All
            <input type="radio" name="q_type" value="phrase" /> Phrase
            <input checked="checked" type="radio" name="q_type" value="boolean" /> Boolean
            <input type="radio" name="q_type" value="extended" /> Extended
        </div>
        <?php } else { ?>
        <input type="hidden" name="q_type" value="boolean"> 
        <?php } ?>
    </td>
</tr>
 </table>
 <?php } else { ?>
     <script type="text/javascript">
         $('#portlet_Messages').remove()
     </script>
     <?php } ?>
