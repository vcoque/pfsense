<?php
/* $Id$ */
/*
	status_interfaces.php
	part of pfSense
	Copyright (C) 2009 Scott Ullrich <sullrich@gmail.com>.
	All rights reserved.

	originally part of m0n0wall (http://m0n0.ch/wall)
	Copyright (C) 2003-2005 Manuel Kasper <mk@neon1.net>.
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
	pfSense_MODULE:	interfaces
*/

##|+PRIV
##|*IDENT=page-status-interfaces
##|*NAME=Status: Interfaces page
##|*DESCR=Allow access to the 'Status: Interfaces' page.
##|*MATCH=status_interfaces.php*
##|-PRIV

require_once("guiconfig.inc");

if ($_GET['if']) {
	$interface = $_GET['if'];
	if ($_GET['action'] == "Disconnect" || $_GET['action'] == "Release") {
		interface_bring_down($interface);
	} else if ($_GET['action'] == "Connect" || $_GET['action'] == "Renew") {
		interface_configure($interface); 
	}
	header("Location: status_interfaces.php");
	exit;
}

$pgtitle = array(gettext("Status"),gettext("Interfaces"));
include("head.inc");

?>

<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<?php include("fbegin.inc"); ?>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
<?php 
	$i = 0; 
	$ifdescrs = get_configured_interface_with_descr(false, true);
	foreach ($ifdescrs as $ifdescr => $ifname):
	$ifinfo = get_interface_info($ifdescr);
	// Load MAC-Manufacturer table
	$mac_man = load_mac_manufacturer_table();
?>
<?php if ($i): ?>
	<tr>
		<td colspan="8" class="list" height="12"></td>
	</tr>
<?php endif; ?>
	<tr>
		<td colspan="2" class="listtopic">
			<?=htmlspecialchars($ifname);?> <?=gettext("interface"); ?> (<?=htmlspecialchars($ifinfo['hwif']);?>)
		</td>
	</tr>
	<tr>
		<td width="22%" class="vncellt"><?=gettext("Status"); ?></td>
		<td width="78%" class="listr">
			<?=htmlspecialchars($ifinfo['status']);?>
		</td>
	</tr>
	<?php if ($ifinfo['dhcplink']): ?>
	<tr>
		<td width="22%" class="vncellt">
			DHCP
		</td>
		<td width="78%" class="listr">
			<?=htmlspecialchars($ifinfo['dhcplink']);?>&nbsp;&nbsp;
			<?php if ($ifinfo['dhcplink'] == "up"): ?>
				<a href="status_interfaces.php?action=Release&if=<?php echo $ifdescr; ?>">
				<input type="button" name="<?php echo $ifdescr; ?>" value="<?=gettext("Release");?>" class="formbtns">
			<?php else: ?>
				<a href="status_interfaces.php?action=Renew&if=<?php echo $ifdescr; ?>">
				<input type="button" name="<?php echo $ifdescr; ?>" value="<?=gettext("Renew");?>" class="formbtns">
			<?php endif; ?>
			</a>
		</td>
	</tr>
	<?php endif; if ($ifinfo['pppoelink']): ?>
	<tr>
		<td width="22%" class="vncellt"><?=gettext("PPPoE"); ?></td>
		<td width="78%" class="listr">
			<?=htmlspecialchars($ifinfo['pppoelink']);?>&nbsp;&nbsp;
			<?php if ($ifinfo['pppoelink'] == "up"): ?>
				<a href="status_interfaces.php?action=Disconnect&if=<?php echo $ifdescr; ?>">
				<input type="button" name="<?php echo $ifdescr; ?>" value="<?=gettext("Disconnect");?>" class="formbtns">
			<?php else: ?>
				<a href="status_interfaces.php?action=Connect&if=<?php echo $ifdescr; ?>">
				<input type="button" name="<?php echo $ifdescr; ?>" value="<?=gettext("Connect");?>" class="formbtns">
				<?php endif; ?>
			</a>
		</td>
	</tr>
	<?php  endif; if ($ifinfo['pptplink']): ?>
	<tr>
		<td width="22%" class="vncellt"><?=gettext("PPTP"); ?></td>
		<td width="78%" class="listr">
			<?=htmlspecialchars($ifinfo['pptplink']);?>&nbsp;&nbsp;
			<?php if ($ifinfo['pptplink'] == "up"): ?>
				<a href="status_interfaces.php?action=Disconnect&if=<?php echo $ifdescr; ?>">
				<input type="button" name="<?php echo $ifdescr; ?>" value="<?=gettext("Disconnect");?>" class="formbtns">
			<?php else: ?>
				<a href="status_interfaces.php?action=Connect&if=<?php echo $ifdescr; ?>">
				<input type="button" name="<?php echo $ifdescr; ?>" value="<?=gettext("Connect");?>" class="formbtns">
			<?php endif; ?>
			</a>
		</td>
	</tr>
	<?php  endif; if ($ifinfo['l2tplink']): ?>
	<tr>
		<td width="22%" class="vncellt"><?=gettext("L2TP"); ?></td>
		<td width="78%" class="listr">
			<?=htmlspecialchars($ifinfo['l2tplink']);?>&nbsp;&nbsp;
			<?php if ($ifinfo['l2tplink'] == "up"): ?>
				<a href="status_interfaces.php?action=Disconnect&if=<?php echo $ifdescr; ?>">
				<input type="button" name="<?php echo $ifdescr; ?>" value="<?=gettext("Disconnect");?>" class="formbtns">
			<?php else: ?>
				<a href="status_interfaces.php?action=Connect&if=<?php echo $ifdescr; ?>">
				<input type="button" name="<?php echo $ifdescr; ?>" value="<?=gettext("Connect");?>" class="formbtns">
			<?php endif; ?>
			</a>
		</td>
	</tr>
	<?php  endif; if ($ifinfo['ppplink']): ?>
	<tr>
		<td width="22%" class="vncellt"><?=gettext("PPP"); ?></td>
		<td width="78%" class="listr">
			<?=htmlspecialchars($ifinfo['pppinfo']);?>
			<?php if ($ifinfo['ppplink'] == "up"): ?>
				<a href="status_interfaces.php?action=Disconnect&if=<?php echo $ifdescr; ?>">
				<input type="button" name="<?php echo $ifdescr; ?>" value="<?=gettext("Disconnect");?>" class="formbtns">
			<?php else: ?>
				<?php if (!$ifinfo['nodevice']): ?>
					<a href="status_interfaces.php?action=Connect&if=<?php echo $ifdescr; ?>">
					<input type="button" name="<?php echo $ifdescr; ?>" value="<?=gettext("Connect");?>" class="formbtns">
				<?php endif; ?>
			<?php endif; ?>
			</a>
		</td>
	</tr>
	<?php  endif; if ($ifinfo['ppp_uptime'] || $ifinfo['ppp_uptime_accumulated']): ?>
	<tr>
		<td width="22%" class="vncellt"><?=gettext("Uptime ");?><?php if ($ifinfo['ppp_uptime_accumulated']) echo "(historical)"; ?></td>
		<td width="78%" class="listr">
			<?=htmlspecialchars($ifinfo['ppp_uptime']);?> <?=htmlspecialchars($ifinfo['ppp_uptime_accumulated']);?>
		</td>
        </tr>
	<?php  endif; if ($ifinfo['macaddr']): ?>
	<tr>
		<td width="22%" class="vncellt"><?=gettext("MAC address");?></td>
		<td width="78%" class="listr">
			<?php 
			$mac=$ifinfo['macaddr']; 
			$mac_hi = strtoupper($mac[0] . $mac[1] . $mac[3] . $mac[4] . $mac[6] . $mac[7]);
			if(isset($mac_man[$mac_hi])){ print "<span>" . $mac . " - " . htmlspecialchars($mac_man[$mac_hi]); print "</span>"; }
			      else {print htmlspecialchars($mac);}
			?>
		</td>
	</tr>
	<?php endif; if ($ifinfo['status'] != "down"): ?>
	<?php if ($ifinfo['dhcplink'] != "down" && $ifinfo['pppoelink'] != "down" && $ifinfo['pptplink'] != "down"): ?>
	<?php if ($ifinfo['ipaddr']): ?>
	<tr>
		<td width="22%" class="vncellt"><?=gettext("IP address");?></td>
		<td width="78%" class="listr">
			<?=htmlspecialchars($ifinfo['ipaddr']);?>
			&nbsp; 
		</td>
	</tr>
	<?php endif; ?><?php if ($ifinfo['subnet']): ?>
	<tr>
		<td width="22%" class="vncellt"><?=gettext("Subnet mask");?></td>
		<td width="78%" class="listr">
			<?=htmlspecialchars($ifinfo['subnet']);?>
		</td>
	</tr>
	<?php endif; ?><?php if ($ifinfo['gateway']): ?>
	<tr>
		<td width="22%" class="vncellt"><?=gettext("Gateway");?></td>
		<td width="78%" class="listr">
			<?=htmlspecialchars($config['interfaces'][$ifdescr]['gateway']);?>
			<?=htmlspecialchars($ifinfo['gateway']);?>
		</td>
	</tr>
	<?php endif; if ($ifdescr == "wan" && file_exists("{$g['varetc_path']}/resolv.conf")): ?>
	<tr>
	<td width="22%" class="vncellt"><?=gettext("ISP DNS servers");?></td>
	<td width="78%" class="listr">
		<?php
			$dns_servers = get_dns_servers();
			foreach($dns_servers as $dns) {
				echo "{$dns}<br>";
			}
		?>
		</td>
	</tr>
	<?php endif; endif; if ($ifinfo['media']): ?>
	<tr>
		<td width="22%" class="vncellt"><?=gettext("Media");?></td>
		<td width="78%" class="listr">
			<?=htmlspecialchars($ifinfo['media']);?>
		</td>
	</tr>
<?php endif; ?><?php if ($ifinfo['channel']): ?>
	<tr>
		<td width="22%" class="vncellt"><?=gettext("Channel");?></td>
		<td width="78%" class="listr">
			<?=htmlspecialchars($ifinfo['channel']);?>
		</td>
	</tr>
<?php endif; ?><?php if ($ifinfo['ssid']): ?>
	<tr>
		<td width="22%" class="vncellt"><?=gettext("SSID");?></td>
		<td width="78%" class="listr">
			<?=htmlspecialchars($ifinfo['ssid']);?>
		</td>
	</tr>
<?php endif; ?><?php if ($ifinfo['bssid']): ?>
	<tr>
		<td width="22%" class="vncellt"><?=gettext("BSSID");?></td>
		<td width="78%" class="listr">
			<?=htmlspecialchars($ifinfo['bssid']);?>
		</td>
	</tr>
<?php endif; ?><?php if ($ifinfo['rate']): ?>
	<tr>
		<td width="22%" class="vncellt"><?=gettext("Rate");?></td>
		<td width="78%" class="listr">
			<?=htmlspecialchars($ifinfo['rate']);?>
		</td>
	</tr>
<?php endif; ?><?php if ($ifinfo['rssi']): ?>
	<tr>
		<td width="22%" class="vncellt"><?=gettext("RSSI");?></td>
		<td width="78%" class="listr">
			<?=htmlspecialchars($ifinfo['rssi']);?>
		</td>
	</tr>
<?php endif; ?>
	<tr>
		<td width="22%" class="vncellt"><?=gettext("In/out packets");?></td>
		<td width="78%" class="listr">
		<?php
			echo htmlspecialchars($ifinfo['inpkts'] . "/" . $ifinfo['outpkts'] . " (");
			echo htmlspecialchars(format_bytes($ifinfo['inbytes']) . "/" . format_bytes($ifinfo['outbytes']) . ")");
		?>
		</td>
	</tr>
	<tr>
		<td width="22%" class="vncellt"><?=gettext("In/out packets (pass)");?></td>
		<td width="78%" class="listr">
			<?php
				echo htmlspecialchars($ifinfo['inpktspass'] . "/" . $ifinfo['outpktspass'] . " (");
				echo htmlspecialchars(format_bytes($ifinfo['inbytespass']) . "/" . format_bytes($ifinfo['outbytespass']) . ")");
			?>
		</td>
	</tr>
	<tr>
		<td width="22%" class="vncellt"><?=gettext("In/out packets (block)");?></td>
		<td width="78%" class="listr">
			<?php
				echo htmlspecialchars($ifinfo['inpktsblock'] . "/" . $ifinfo['outpktsblock'] . " (");
				echo htmlspecialchars(format_bytes($ifinfo['inbytesblock']) . "/" . format_bytes($ifinfo['outbytesblock']) . ")");
			?>
		</td>
	</tr>
<?php if (isset($ifinfo['inerrs'])): ?>
	<tr>
		<td width="22%" class="vncellt"><?=gettext("In/out errors");?></td>
		<td width="78%" class="listr">
			<?=htmlspecialchars($ifinfo['inerrs'] . "/" . $ifinfo['outerrs']);?>
		</td>
	</tr>
<?php endif; ?>
<?php if (isset($ifinfo['collisions'])): ?>
	<tr>
		<td width="22%" class="vncellt"><?=gettext("Collisions");?></td>
		<td width="78%" class="listr">
			<?=htmlspecialchars($ifinfo['collisions']);?>
		</td>
	</tr>
<?php endif; ?>
<?php endif; ?>
<?php if ($ifinfo['bridge']): ?>
	<tr>
		<td width="22%" class="vncellt"><?php printf(gettext("Bridge (%s)"),$ifinfo['bridgeint']);?></td>
		<td width="78%" class="listr">
			<?=$ifinfo['bridge'];?>
		</td>
	</tr>
<?php endif; ?>
<?php if(file_exists("/usr/bin/vmstat")): ?>
<?php
	$real_interface = "";
	$interrupt_total = "";
	$interrupt_sec = "";
	$real_interface = $ifinfo['hwif'];
	$interrupt_total = `vmstat -i | grep $real_interface | awk '{ print $3 }'`;
	$interrupt_sec = `vmstat -i | grep $real_interface | awk '{ print $4 }'`;
	if(strstr($interrupt_total, "hci")) {
		$interrupt_total = `vmstat -i | grep $real_interface | awk '{ print $4 }'`;
		$interrupt_sec = `vmstat -i | grep $real_interface | awk '{ print $5 }'`;          	
	}
	unset($interrupt_total); // XXX: FIX ME!  Need a regex and parse correct data 100% of the time.
?>
<?php if($interrupt_total): ?>
	<tr>
		<td width="22%" class="vncellt"><?=gettext("Interrupts/Second");?></td>
		<td width="78%" class="listr">
			<?php
				echo $interrupt_total . " " . gettext("total");
				echo "<br/>";
				echo $interrupt_sec . " " . gettext("rate");
			?>
		</td>
	</tr>
<?php endif; ?>
<?php endif; ?>
<?php $i++; endforeach; ?>
</table>

<br/>

</strong><?php printf(gettext("Using dial-on-demand will bring the connection up again if any packet ".
"triggers it. To substantiate this point: disconnecting manually ".
"will %snot%s prevent dial-on-demand from making connections ".
"to the outside! Don't use dial-on-demand if you want to make sure that the line ".
"is kept disconnected."),'<strong>','</strong>')?>

<?php include("fend.inc"); ?>
