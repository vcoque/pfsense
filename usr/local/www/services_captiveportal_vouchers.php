<?php 
/*
	Copyright (C) 2007 Marcel Wiget <mwiget@mac.com>
	All rights reserved. 
	
	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:
	
	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.
	
	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.
	
	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/
/*
	pfSense_BUILDER_BINARIES:	/usr/local/bin/voucher
	pfSense_MODULE:	captiveportal
*/

##|+PRIV
##|*IDENT=page-services-captiveportal-vouchers
##|*NAME=Services: Captive portal Vouchers page
##|*DESCR=Allow access to the 'Services: Captive portal Vouchers' page.
##|*MATCH=services_captiveportal_vouchers.php*
##|-PRIV

$statusurl = "status_captiveportal_vouchers.php";
$logurl = "diag_logs_auth.php";

if ($_POST['postafterlogin'])
	$nocsrf= true;
require("guiconfig.inc");
require("functions.inc");
require("filter.inc");
require("shaper.inc");
require("captiveportal.inc");
require_once("voucher.inc");

if($_REQUEST['generatekey']) {
	exec("openssl genrsa 64 > /tmp/key64.private");
	exec("openssl rsa -pubout < /tmp/key64.private > /tmp/key64.public");
	$privatekey = str_replace("\n", "\\n", file_get_contents("/tmp/key64.private"));
	$publickey = str_replace("\n", "\\n", file_get_contents("/tmp/key64.public"));
	exec("rm /tmp/key64.private /tmp/key64.public");
	$alertmessage = gettext("You will need to recreate any existing Voucher Rolls due to the public and private key changes. Click cancel if you do not wish to recreate the vouchers.");
	echo <<<EOF
		\$('publickey').value = '{$publickey}';
		\$('privatekey').value = '{$privatekey}';
		alert('{$alertmessage}');
		new Effect.Highlight('publickey');
		new Effect.Highlight('privatekey');
EOF;
	exit;
}

$pgtitle = array(gettext("Services"), gettext("Captive portal"), gettext("Vouchers"));

if (!is_array($config['voucher'])) 
	$config['voucher'] = array();

if (!is_array($config['voucher']['roll'])) 
	$config['voucher']['roll'] = array();
if (!isset($config['voucher']['charset'])) 
	$config['voucher']['charset'] = '2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
if (!isset($config['voucher']['rollbits'])) 
	$config['voucher']['rollbits'] = 16;
if (!isset($config['voucher']['ticketbits'])) 
	$config['voucher']['ticketbits'] = 10;
if (!isset($config['voucher']['checksumbits'])) 
	$config['voucher']['checksumbits'] = 5;
if (!isset($config['voucher']['magic'])) 
	$config['voucher']['magic'] = rand();   // anything slightly random will do

if (!isset($config['voucher']['publickey'])) {
	/* generate a random 64 bit RSA key pair using the voucher binary */
	$fd = popen("/usr/local/bin/voucher -g 64", "r");
	if ($fd !== false) {
		$output = fread($fd, 16384);
		pclose($fd);
		list($privkey, $pubkey) = explode("\0", $output);
		$config['voucher']['publickey'] = base64_encode($pubkey);
		$config['voucher']['privatekey'] = base64_encode($privkey);
	}
}

// Check for invalid or expired vouchers
if (!isset($config['voucher']['msgnoaccess'])) 
	$config['voucher']['msgnoaccess'] = gettext("Voucher invalid");
if (!isset($config['voucher']['msgexpired'])) 
	$config['voucher']['msgexpired'] = gettext("Voucher expired");

$a_roll = &$config['voucher']['roll'];

if ($_GET['act'] == "del") {
    $id = $_GET['id'];
    if ($a_roll[$id]) {
		$roll = $a_roll[$id]['number']; 
		$voucherlck = lock('voucher');
		unset($a_roll[$id]);
		voucher_unlink_db($roll);
		unlock($voucherlck);
		write_config();
		header("Location: services_captiveportal_vouchers.php");
		exit;
    }
}
/* print all vouchers of the selected roll */
else if ($_GET['act'] == "csv") {
	$privkey = base64_decode($config['voucher']['privatekey']);
	if (strstr($privkey,"BEGIN RSA PRIVATE KEY")) {
		$fd = fopen("{$g['varetc_path']}/voucher.private","w");
		if (!$fd) {
			$input_errors[] = gettext("Cannot write private key file") . ".\n";
		} else {
			chmod("{$g['varetc_path']}/voucher.private", 0600);
			fwrite($fd, $privkey);
			fclose($fd);
			$a_voucher = &$config['voucher']['roll'];
			$id = $_GET['id'];
			if (isset($id) && $a_voucher[$id]) {
				$number = $a_voucher[$id]['number'];
				$count = $a_voucher[$id]['count'];
				header("Content-Type: application/octet-stream");
				header("Content-Disposition: attachment; filename=vouchers_roll$number.csv");
				system("/usr/local/bin/voucher -c {$g['varetc_path']}/voucher.cfg -p {$g['varetc_path']}/voucher.private $number $count");
				unlink("{$g['varetc_path']}/voucher.private");
				exit;
			}
		}
	} else {
		$input_errors[] = gettext("Need private RSA key to print vouchers") . "\n";
	}
}

$pconfig['enable'] = isset($config['voucher']['enable']);
$pconfig['charset'] = $config['voucher']['charset'];
$pconfig['rollbits'] = $config['voucher']['rollbits'];
$pconfig['ticketbits'] = $config['voucher']['ticketbits'];
$pconfig['checksumbits'] = $config['voucher']['checksumbits'];
$pconfig['magic'] = $config['voucher']['magic'];
$pconfig['publickey'] = base64_decode($config['voucher']['publickey']);
$pconfig['privatekey'] = base64_decode($config['voucher']['privatekey']);
$pconfig['msgnoaccess'] = $config['voucher']['msgnoaccess'];
$pconfig['msgexpired'] = $config['voucher']['msgexpired'];
$pconfig['vouchersyncdbip'] = $config['voucher']['vouchersyncdbip'];
$pconfig['vouchersyncport'] = $config['voucher']['vouchersyncport'];
$pconfig['vouchersyncpass'] = $config['voucher']['vouchersyncpass'];
$pconfig['vouchersyncusername'] = $config['voucher']['vouchersyncusername'];

if ($_POST) {

	unset($input_errors);

	if ($_POST['postafterlogin']) {
		voucher_expire($_POST['voucher_expire']);
		exit;
	}

	$pconfig = $_POST;

	/* input validation */
	if ($_POST['enable'] == "yes") {
		if (!$_POST['vouchersyncusername']) { 
			$reqdfields = explode(" ", "charset rollbits ticketbits checksumbits publickey magic");
			$reqdfieldsn = array(gettext("charset"),gettext("rollbits"),gettext("ticketbits"),gettext("checksumbits"),gettext("publickey"),gettext("magic"));
		} else {
			$reqdfields = explode(" ", "vouchersyncdbip vouchersyncport vouchersyncpass vouchersyncusername");
			$reqdfieldsn = array(gettext("Synchronize Voucher Database IP"),gettext("Sync port"),gettext("Sync password"),gettext("Sync username"));
		}
		
		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	}
	
	if (!$_POST['vouchersyncusername']) { 
		// Check for form errors
		if ($_POST['charset'] && (strlen($_POST['charset'] < 2))) 
			$input_errors[] = gettext("Need at least 2 characters to create vouchers.");
		if ($_POST['charset'] && (strpos($_POST['charset'],"\"")>0)) 
			$input_errors[] = gettext("Double quotes aren't allowed.");
		if ($_POST['charset'] && (strpos($_POST['charset'],",")>0)) 
			$input_errors[] = "',' " . gettext("aren't allowed.");
		if ($_POST['rollbits'] && (!is_numeric($_POST['rollbits']) || ($_POST['rollbits'] < 1) || ($_POST['rollbits'] > 31))) 
			$input_errors[] = gettext("# of Bits to store Roll Id needs to be between 1..31.");
		if ($_POST['ticketbits'] && (!is_numeric($_POST['ticketbits']) || ($_POST['ticketbits'] < 1) || ($_POST['ticketbits'] > 16))) 
			$input_errors[] = gettext("# of Bits to store Ticket Id needs to be between 1..16.");
		if ($_POST['checksumbits'] && (!is_numeric($_POST['checksumbits']) || ($_POST['checksumbits'] < 1) || ($_POST['checksumbits'] > 31))) 
			$input_errors[] = gettext("# of Bits to store checksum needs to be between 1..31.");
		if ($_POST['publickey'] && (!strstr($_POST['publickey'],"BEGIN PUBLIC KEY"))) 
			$input_errors[] = gettext("This doesn't look like an RSA Public key.");
		if ($_POST['privatekey'] && (!strstr($_POST['privatekey'],"BEGIN RSA PRIVATE KEY"))) 
			$input_errors[] = gettext("This doesn't look like an RSA Private key.");
		if ($_POST['vouchersyncdbip'] && (is_ipaddr_configured($_POST['vouchersyncdbip']))) 
			$input_errors[] = gettext("You cannot sync the voucher database to this host (itself).");
	}

	if (!$input_errors) {
		if ($_POST['enable'] == "yes")
			$config['voucher']['enable'] = true;
		else
			unset($config['voucher']['enable']);
		if (empty($_POST['vouchersyncusername'])) {
			$config['voucher']['charset'] = $_POST['charset'];
			$config['voucher']['rollbits'] = $_POST['rollbits'];
			$config['voucher']['ticketbits'] = $_POST['ticketbits'];
			$config['voucher']['checksumbits'] = $_POST['checksumbits'];
			$config['voucher']['magic'] = $_POST['magic'];
			$config['voucher']['publickey'] = base64_encode($_POST['publickey']);
			$config['voucher']['privatekey'] = base64_encode($_POST['privatekey']);
			$config['voucher']['msgnoaccess'] = $_POST['msgnoaccess'];
			$config['voucher']['msgexpired'] = $_POST['msgexpired'];
			write_config();
			voucher_configure();
		} else {
			$config['voucher']['vouchersyncdbip'] = $_POST['vouchersyncdbip'];
			$config['voucher']['vouchersyncport'] = $_POST['vouchersyncport'];
			$config['voucher']['vouchersyncusername'] = $_POST['vouchersyncusername'];
			$config['voucher']['vouchersyncpass'] = $_POST['vouchersyncpass'];
			if($config['voucher']['vouchersyncpass'] && $config['voucher']['vouchersyncusername'] && 
			   $config['voucher']['vouchersyncport'] && $config['voucher']['vouchersyncdbip']) {
				// Synchronize the voucher DB from the master node
				require_once("xmlrpc.inc");
				if($config['voucher']['vouchersyncport'] == "443") 
					$url = "https://{$config['voucher']['vouchersyncdbip']}";
				else 
					$url = "http://{$config['voucher']['vouchersyncdbip']}";

				$execcmd  = <<<EOF
				\$toreturn['voucher'] = \$config['voucher'];
				unset(\$toreturn['vouchersyncport'], \$toreturn['vouchersyncpass'], \$toreturn['vouchersyncusername'], \$toreturn['vouchersyncdbip']);

EOF;

				/* assemble xmlrpc payload */
				$params = array(
					XML_RPC_encode($config['voucher']['vouchersyncpass']),
					XML_RPC_encode($execcmd)
				);
				$port = $config['voucher']['vouchersyncport'];
				log_error("voucher XMLRPC sync data {$url}:{$port}.");
				$msg = new XML_RPC_Message('pfsense.exec_php', $params);
				$cli = new XML_RPC_Client('/xmlrpc.php', $url, $port);
				$cli->setCredentials($config['voucher']['vouchersyncusername'], $config['voucher']['vouchersyncpass']);
				$resp = $cli->send($msg, "250");
				if(!is_object($resp)) {
					$error = "A communications error occurred while attempting CaptivePortalVoucherSync XMLRPC sync with {$url}:{$port} (pfsense.exec_php).";
					log_error($error);
					file_notice("CaptivePortalVoucherSync", $error, "Communications error occurred", "");
					$input_errors[] = $error;
				} elseif($resp->faultCode()) {
					$cli->setDebug(1);
					$resp = $cli->send($msg, "250");
					$error = "An error code was received while attempting CaptivePortalVoucherSync XMLRPC sync with {$url}:{$port} - Code " . $resp->faultCode() . ": " . $resp->faultString();
					log_error($error);
					file_notice("CaptivePortalVoucherSync", $error, "Error code received", "");
					$input_errors[] = $error;
				} else {
					log_error("The Captive Portal voucher database has been synchronized with {$url}:{$port} (pfsense.exec_php).");
				}
				if (!$input_errors) {
					$toreturn =  XML_RPC_Decode($resp->value());
					if(!is_array($toreturn)) {
						if($toreturn == "Authentication failed") 
							$input_errors[] = "Could not synchronize the voucher database: Authentication Failed.";
					} else {				
						// If we received back the voucher roll and other information then store it.
						if($toreturn['voucher']['roll'])
							$config['voucher']['roll'] = $toreturn['voucher']['roll'];
						if($toreturn['voucher']['rollbits'])
							$config['voucher']['rollbits'] = $toreturn['voucher']['rollbits'];
						if($toreturn['voucher']['ticketbits'])
							$config['voucher']['ticketbits'] = $toreturn['voucher']['ticketbits'];
						if($toreturn['voucher']['checksumbits'])
							$config['voucher']['checksumbits'] = $toreturn['voucher']['checksumbits'];
						if($toreturn['voucher']['magic'])
							$config['voucher']['magic'] = $toreturn['voucher']['magic'];
						if($toreturn['voucher']['publickey'])
							$config['voucher']['publickey'] = $toreturn['voucher']['publickey'];
						if($toreturn['voucher']['privatekey'])
							$config['voucher']['privatekey'] = $toreturn['voucher']['privatekey'];
						if($toreturn['voucher']['msgnoaccess'])
							$config['voucher']['msgnoaccess'] = $toreturn['voucher']['msgnoaccess'];
						if($toreturn['voucher']['msgexpired'])
							$config['voucher']['msgexpired'] = $toreturn['voucher']['msgexpired'];
						if($toreturn['voucher']['msgnoaccess'])
							$config['voucher']['msgnoaccess'] = $toreturn['voucher']['msgnoaccess'];
						$savemsg = gettext("Voucher database has been synchronized from {$url}:{$port}");

						write_config();
						voucher_configure(true);
					}
				}
			}
		}
		if($savemsg && isset($config['voucher']['enable']) && !isset($config['captiveportal']['enable'])) 
			$savemsg .= "<br/>";
		if (isset($config['voucher']['enable']) && !isset($config['captiveportal']['enable'])) 
			$savemsg .= gettext("Don't forget to configure and enable Captive Portal.");
	}
}
include("head.inc");
?>
<?php include("fbegin.inc"); ?>
<script type="text/javascript">
<!--
function generatenewkey() {
	$('publickey').value = 'One moment please...';
	$('privatekey').value = 'One moment please...';
	new Ajax.Request("services_captiveportal_vouchers.php?generatekey=true", {
		method: 'get',
		onSuccess: function(transport) {	
			eval(transport.responseText);
		}
	});		
}
function before_save() {
	document.iform.charset.disabled = false;
	document.iform.rollbits.disabled = false;
	document.iform.ticketbits.disabled = false;
	document.iform.checksumbits.disabled = false;
	document.iform.magic.disabled = false;
	document.iform.publickey.disabled = false;
	document.iform.privatekey.disabled = false;
	document.iform.msgnoaccess.disabled = false;
	document.iform.msgexpired.disabled = false;
	for(var x=0; x < <?php echo count($a_roll); ?>; x++)
		$('addeditdelete' + x).show();
	$('addnewroll').show();
}
function enable_change(enable_change) {
	var endis;
	endis = !(document.iform.enable.checked || enable_change);	
	document.iform.charset.disabled = endis;
	document.iform.rollbits.disabled = endis;
	document.iform.ticketbits.disabled = endis;
	document.iform.checksumbits.disabled = endis;
	document.iform.magic.disabled = endis;
	document.iform.publickey.disabled = endis;
	document.iform.privatekey.disabled = endis;
	document.iform.msgnoaccess.disabled = endis;
	document.iform.msgexpired.disabled = endis;
	document.iform.vouchersyncdbip.disabled = endis;
	document.iform.vouchersyncport.disabled = endis;
	document.iform.vouchersyncpass.disabled = endis;
	document.iform.vouchersyncusername.disabled = endis;
	if(document.iform.vouchersyncusername.value != "") {
		document.iform.charset.disabled = true;
		document.iform.rollbits.disabled = true;
		document.iform.ticketbits.disabled = true;
		document.iform.checksumbits.disabled = true;
		document.iform.magic.disabled = true;
		document.iform.publickey.disabled = true;
		document.iform.privatekey.disabled = true;
		document.iform.msgnoaccess.disabled = true;
		document.iform.msgexpired.disabled = true;
		for(var x=0; x < <?php echo count($a_roll); ?>; x++)
			$('addeditdelete' + x).hide();
		$('addnewroll').hide();
	} else {
		for(var x=0; x < <?php echo count($a_roll); ?>; x++)
			$('addeditdelete' + x).show();
		$('addnewroll').show();
	}
}
//-->
</script>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<form action="services_captiveportal_vouchers.php" method="post" enctype="multipart/form-data" name="iform" id="iform">
<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="tab pane">
	<tr>
		<td class="tabnavtbl">
			<ul id="tabnav">
<?php 
	$tab_array = array();
	$tab_array[] = array(gettext("Captive portal"), false, "services_captiveportal.php");
	$tab_array[] = array(gettext("Pass-through MAC"), false, "services_captiveportal_mac.php");
	$tab_array[] = array(gettext("Allowed IP addresses"), false, "services_captiveportal_ip.php");
	$tab_array[] = array(gettext("Allowed Hostnames"), false, "services_captiveportal_hostname.php");
	$tab_array[] = array(gettext("Vouchers"), true, "services_captiveportal_vouchers.php");
	$tab_array[] = array(gettext("File Manager"), false, "services_captiveportal_filemanager.php");
	display_top_tabs($tab_array, true);
?> 
			</ul>
		</td>
	</tr>
	<tr>
		<td class="tabcont">
			<table width="100%" border="0" cellpadding="6" cellspacing="0" summary="checkbox pane">
				<tr> 
					<td width="22%" valign="top" class="vtable">&nbsp;</td>
					<td width="78%" class="vtable">
						<input name="enable" type="checkbox" value="yes" <?php if ($pconfig['enable']) echo "checked"; ?> onClick="enable_change(false)">
						<strong><?=gettext("Enable Vouchers"); ?></strong>
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">
						<?=gettext("Voucher Rolls"); ?>
						<?php 
							if($pconfig['vouchersyncdbip']) 
								echo "<br/>(Synchronized from {$pconfig['vouchersyncdbip']})";
						?>
					</td>
					<td class="vtable">
						<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="content pane">
							<tr>
								<td width="10%" class="listhdrr"><?=gettext("Roll"); ?> #</td>
								<td width="20%" class="listhdrr"><?=gettext("Minutes/Ticket"); ?></td>
								<td width="20%" class="listhdrr"># <?=gettext("of Tickets"); ?></td>
								<td width="35%" class="listhdr"><?=gettext("Comment"); ?></td>
								<td width="15%" class="list"></td>
							</tr>
							<?php $i = 0; foreach($a_roll as $rollent): ?>
								<tr>
									<td class="listlr">
									<?=htmlspecialchars($rollent['number']); ?>&nbsp;
								</td>
								<td class="listr">
									<?=htmlspecialchars($rollent['minutes']);?>&nbsp;
								</td>
								<td class="listr">
									<?=htmlspecialchars($rollent['count']);?>&nbsp;
								</td>
								<td class="listr">
									<?=htmlspecialchars($rollent['comment']); ?>&nbsp;
								</td>
								<td valign="middle" nowrap class="list"> 
									<div id='addeditdelete<?=$i?>'>
										<?php if ($pconfig['enable']): ?> 
											<a href="services_captiveportal_vouchers_edit.php?id=<?=$i; ?>"><img src="/themes/<?=$g['theme']; ?>/images/icons/icon_e.gif" title="<?=gettext("edit voucher"); ?>" width="17" height="17" border="0" alt="<?=gettext("edit voucher"); ?>"></a>
											<a href="services_captiveportal_vouchers.php?act=del&amp;id=<?=$i; ?>" onclick="return confirm('<?=gettext("Do you really want to delete this voucher? This makes all vouchers from this roll invalid"); ?>')"><img src="/themes/<?=$g['theme']; ?>/images/icons/icon_x.gif" title="<?=gettext("delete vouchers"); ?>" width="17" height="17" border="0" alt="<?=gettext("delete vouchers"); ?>"></a>
											<a href="services_captiveportal_vouchers.php?act=csv&amp;id=<?=$i; ?>"><img src="/themes/<?=$g['theme']; ?>/images/icons/icon_log_s.gif" title="<?=gettext("generate vouchers for this roll to CSV file"); ?>" width="11" height="15" border="0" alt="<?=gettext("generate vouchers for this roll to CSV file"); ?>"></a>
                    					<?php endif;?>
									</div>
								</td>
							</tr>
							<?php $i++; endforeach; ?>
							<tr> 
								<td class="list" colspan="4"></td>
								<?php
								if ($pconfig['enable']) 
									echo "<td class=\"list\"><div id='addnewroll'> <a href=\"services_captiveportal_vouchers_edit.php\"><img src=\"/themes/{$g['theme']}/images/icons/icon_plus.gif\" title=\"" . gettext("add voucher") . "\" width=\"17\" height=\"17\" border=\"0\" alt=\"" . gettext("add voucher") . "\"></a></div></td>";
								?>
							</tr>
						</table>     
						<?php if ($pconfig['enable']): ?> 
							<?=gettext("Create, generate and activate Rolls with Vouchers that allow access through the " .
							"captive portal for the configured time. Once a voucher is activated, " .
								"its clock is started and runs uninterrupted until it expires. During that " .
								"time, the voucher can be re-used from the same or a different computer. If the voucher " .
								"is used again from another computer, the previous session is stopped."); ?>
						<?php else: ?>
							<?=gettext("Enable Voucher support first using the checkbox above and hit Save at the bottom."); ?></td>
						<?php endif;?>
					</tr>
					<tr>
						<td valign="top" class="vncellreq">
							<?=gettext("Voucher public key"); ?>
						</td>
						<td class="vtable">
							<textarea name="publickey" cols="65" rows="4" id="publickey" class="formpre"><?=htmlspecialchars($pconfig['publickey']);?></textarea>
							<br>
								<?=gettext("Paste an RSA public key (64 Bit or smaller) in PEM format here. This key is used to decrypt vouchers."); ?> <a href='#' onClick='generatenewkey();'><?=gettext('Generate');?></a> <?=gettext('new key');?>.</td>
						</tr>
						<tr>
							<td valign="top" class="vncell"><?=gettext("Voucher private key"); ?></td>
							<td class="vtable">
								<textarea name="privatekey" cols="65" rows="5" id="privatekey" class="formpre"><?=htmlspecialchars($pconfig['privatekey']);?></textarea>
								<br>
								<?=gettext("Paste an RSA private key (64 Bit or smaller) in PEM format here. This key is only used to generate encrypted vouchers and doesn't need to be available if the vouchers have been generated offline."); ?> <a href='#' onClick='generatenewkey();'> <?=gettext('Generate');?></a> <?=gettext('new key');?>.</td>
							</td>
						</tr>
						<tr> 
							<td width="22%" valign="top" class="vncellreq"><?=gettext("Character set"); ?></td>
							<td width="78%" class="vtable">
								<input name="charset" type="text" class="formfld" id="charset" size="80" value="<?=htmlspecialchars($pconfig['charset']);?>">
								<br>
								<?=gettext("Tickets are generated with the specified character set. It should contain printable characters (numbers, lower case and upper case letters) that are hard to confuse with others. Avoid e.g. 0/O and l/1."); ?>
							</td>
						</tr>
						<tr> 
							<td width="22%" valign="top" class="vncellreq"># <?=gettext("of Roll Bits"); ?></td>
							<td width="78%" class="vtable">
								<input name="rollbits" type="text" class="formfld" id="rollbits" size="2" value="<?=htmlspecialchars($pconfig['rollbits']);?>">
								<br>
								<?=gettext("Reserves a range in each voucher to store the Roll # it belongs to. Allowed range: 1..31. Sum of Roll+Ticket+Checksum bits must be one Bit less than the RSA key size."); ?>
							</td>
						</tr>
						<tr> 
							<td width="22%" valign="top" class="vncellreq"># <?=gettext("of Ticket Bits"); ?></td>
							<td width="78%" class="vtable">
								<input name="ticketbits" type="text" class="formfld" id="ticketbits" size="2" value="<?=htmlspecialchars($pconfig['ticketbits']);?>">
								<br>
								<?=gettext("Reserves a range in each voucher to store the Ticket# it belongs to. Allowed range: 1..16. Using 16 bits allows a roll to have up to 65535 vouchers. A bit array, stored in RAM and in the config, is used to mark if a voucher has been used. A bit array for 65535 vouchers requires 8 KB of storage."); ?>
							</td>
						</tr>
						<tr> 
							<td width="22%" valign="top" class="vncellreq"># <?=gettext("of Checksum Bits"); ?></td>
							<td width="78%" class="vtable">
								<input name="checksumbits" type="text" class="formfld" id="checksumbits" size="2" value="<?=htmlspecialchars($pconfig['checksumbits']);?>">
								<br>
								<?=gettext("Reserves a range in each voucher to store a simple checksum over Roll # and Ticket#. Allowed range is 0..31."); ?>
							</td>
						</tr>
						<tr> 
							<td width="22%" valign="top" class="vncellreq"><?=gettext("Magic Number"); ?></td>
							<td width="78%" class="vtable">
								<input name="magic" type="text" class="formfld" id="magic" size="20" value="<?=htmlspecialchars($pconfig['magic']);?>">
								<br>
								<?=gettext("Magic number stored in every voucher. Verified during voucher check. Size depends on how many bits are left by Roll+Ticket+Checksum bits. If all bits are used, no magic number will be used and checked."); ?>
							</td>
						</tr>
						<tr> 
							<td width="22%" valign="top" class="vncellreq"><?=gettext("Invalid Voucher Message"); ?></td>
							<td width="78%" class="vtable">
								<input name="msgnoaccess" type="text" class="formfld" id="msgnoaccess" size="80" value="<?=htmlspecialchars($pconfig['msgnoaccess']);?>">
								<br><?=gettext("Error message displayed for invalid vouchers on captive portal error page"); ?> ($PORTAL_MESSAGE$).
							</td>
						</tr>
						<tr> 
							<td width="22%" valign="top" class="vncellreq"><?=gettext("Expired Voucher Message"); ?></td>
							<td width="78%" class="vtable">
								<input name="msgexpired" type="text" class="formfld" id="msgexpired" size="80" value="<?=htmlspecialchars($pconfig['msgexpired']);?>">
								<br><?=gettext("Error message displayed for expired vouchers on captive portal error page"); ?> ($PORTAL_MESSAGE$).
							</td>
						</tr>
						<tr>
							<td width="22%" valign="top">&nbsp;</td>
							<td width="78%">
								&nbsp;
							</td>
						</tr>
						<tr>
							<td colspan="2" valign="top" class="listtopic"><?=gettext("Voucher database synchronization"); ?></td>
						</tr>
						<tr> 
							<td width="22%" valign="top" class="vncellreq"><?=gettext("Synchronize Voucher Database IP"); ?></td>
							<td width="78%" class="vtable">
								<input name="vouchersyncdbip" type="text" class="formfld" id="vouchersyncdbip" size="17" value="<?=htmlspecialchars($pconfig['vouchersyncdbip']);?>">
								<br/><?=gettext("IP address of master nodes webConfigurator to synchronize voucher database and used vouchers from."); ?>
								<br/><?=gettext("NOTE: this should be setup on the slave nodes and not the primary node!"); ?>
							</td>
						</tr>
						<tr> 
							<td width="22%" valign="top" class="vncellreq"><?=gettext("Voucher sync port"); ?></td>
							<td width="78%" class="vtable">
								<input name="vouchersyncport" type="text" class="formfld" id="vouchersyncport" size="7" value="<?=htmlspecialchars($pconfig['vouchersyncport']);?>">
								<br><?=gettext("This is the port of the master voucher nodes webConfigurator.  Example: 443"); ?>
							</td>
						</tr>
						<tr> 
							<td width="22%" valign="top" class="vncellreq"><?=gettext("Voucher sync username"); ?></td>
							<td width="78%" class="vtable">
								<input name="vouchersyncusername" type="text" class="formfld" id="vouchersyncusername" size="25" value="<?=htmlspecialchars($pconfig['vouchersyncusername']);?>">
								<br><?=gettext("This is the username of the master voucher nodes webConfigurator."); ?>
							</td>
						</tr>
						<tr> 
							<td width="22%" valign="top" class="vncellreq"><?=gettext("Voucher sync password"); ?></td>
							<td width="78%" class="vtable">
								<input name="vouchersyncpass" type="password" class="formfld" id="vouchersyncpass" size="25" value="<?=htmlspecialchars($pconfig['vouchersyncpass']);?>">
								<br><?=gettext("This is the password of the master voucher nodes webConfigurator."); ?>
							</td>
						</tr>
						<tr>
							<td width="22%" valign="top">&nbsp;</td>
							<td width="78%">
								<input name="Submit" type="submit" class="formbtn" value="<?=gettext("Save"); ?>" onClick="enable_change(true); before_save();"> 
								<input type="button" class="formbtn" value="<?=gettext("Cancel"); ?>" onclick="history.back()">
							</td>
						</tr>
						<tr>
							<td colspan="2" class="list"><p class="vexpl">
								<span class="red"><strong> <?=gettext("Note:"); ?><br>   </strong></span>
							<?=gettext("Changing any Voucher parameter (apart from managing the list of Rolls) on this page will render existing vouchers useless if they were generated with different settings."); ?>
							<br/>
							<?=gettext("Specifying the Voucher Database Synchronization options will not record any other value from the other options. They will be retrieved/synced from the master."); ?>
						</p>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</form>
<script type="text/javascript">
<!--
enable_change(false);
//-->
</script>
<?php include("fend.inc"); ?>
