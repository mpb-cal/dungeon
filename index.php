<?php

namespace WebSight;

error_reporting( E_ALL );

define( 'DEV', true );
define( 'DUNGEON_SERVER', 'localhost' );
define( 'DUNGEON_PORT', 17000 );
define( 'UPDATE_PORT', (DUNGEON_PORT + 1) );
define( 'START_XML', "<?xml version=\"1.0\" standalone=\"yes\"?>" );

require_once __DIR__ . '/vendor/mpb-cal/web-sight/WebPage.php';


session_start();

registerVars( array( 
	'p_page', 
	'p_name', 
	'p_password', 
	'p_submitEnter',
	'p_command', 
	'p_params',
) );

$title = 'Web Based MUD Beta';

/////////////////////////////////////////////////////
// PAGES PART 1 (non-HTML)

if ($p_page == 'command') // called by ajax
{
	// mpb! sanitize params here

	if (!sessionVar( 'username' ))
	{
		print "\nError: missing username\n";
		exit;
	}

	if (!$p_command)
	{
		print "\nError: missing command\n";
		exit;
	}

	$errno = 0;
	$errstr = '';

	error_reporting( 0 );
	$s = stream_socket_client( 'tcp://' . DUNGEON_SERVER . ':' . DUNGEON_PORT, $errno, $errstr, 10 );
	error_reporting( E_STRICT );

	if (!$s) 
	{
		$response = START_XML;
		$response .= "<error>$errstr ($errno)</error>\n";
	} 
	else 
	{
		fwrite( $s, sessionVar( 'username' ) . " $p_command $p_params\n" );

		$response = '';

		while (!feof( $s )) 
		{
			$line = fgets( $s );
			if (preg_match( "/^([^:]*):(.*)$/", $line, $matches ))
			{
				$type = $matches[1];
				$content = $matches[2];

				if ($type == 'done') break;

				if ($type == 'error')
				{
					$response = "<error>$content</error>";
					break;
				}

				$response .= $content;
			}
			else
			{
				$response = "<error>error (unformatted response from server): $line</error>";
				break;
			}
		}

		fclose( $s );
	}

	if (preg_match( "/^<\?xml/", $response ))
		header( 'content-type: text/xml' );

	print $response;

	exit;
}
elseif ($p_page == 'updates')   // called by ajax
{
	function sendXML( $xml )
	{
		print "Content-type: text/xml\n\n";
		print "<?xml version=\"1.0\" standalone=\"yes\"?>\n";
		print $xml;
		print "\n--" . BOUNDARY . "\n";

		flush();
	}

	if (!sessionVar( 'username' ))
	{
		print "\nError: missing username\n";
		exit;
	}

	define( 'BOUNDARY', 'iuh876yfh78' );

	header( 'Content-type: multipart/x-mixed-replace;boundary="' . BOUNDARY . '"' );
	print "--" . BOUNDARY . "\n";

	$errno = 0;
	$errstr = '';

	if (($socket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP )) === FALSE)
	{
		sendXML( "<status>$errstr ($errno), exiting</status>" );
		exit;
	} 

	socket_connect( $socket, 'localhost', UPDATE_PORT );

	$username = sessionVar( 'username' );
  socket_write( $socket, $username, strlen($username) );

	sendXML( '<status>"' . sessionVar( 'username' ) . '", waiting for updates</status>' );

	while (1)
	{
		$xml = "<updates>";

		while (1)
		{
      // mpb! this is not working
      socket_recv( $socket, $input, 1024 );
      if (!$input) break;

			if (preg_match( "/^([^:]*):(.*)$/", $input, $matches ))
			{
				$type = $matches[1];
				$content = $matches[2];

				if ($type == 'done') break;

				if ($type == 'error')
				{
					$xml .= "<error>$content</error>";
					break;
				}

				$xml .= $content;
			}
			else
			{
				$xml .= $input;
				break;
			}
		}

		if (!$input) break;

		$xml .= "</updates>\n";

		sendXML( $xml );
	}

  print "finished.";
	exit;
}


?>
<html>

<head>

<meta http-equiv="Pragma" content="no-cache">

<title><?php print $title?></title>

<script type="text/javascript" src="functions.js"></script>

<script type="text/javascript">

	function getAjax()
	{
		var ajax;

		if (typeof XMLHttpRequest != 'undefined')
		{
			try { ajax = new XMLHttpRequest(); } 
			catch (e) { ajax = false; }
		}

		return ajax;
	}

	/*
	function doAjax( url, onreadystatechange )
	{
		var ajax = getAjax();
		ajax.onreadystatechange = onreadystatechange;
		ajax.open( 'GET', url, true );
		ajax.send( null );
	}
	*/

	var statusTime;

	function writeStatus( text )
	{
		var d = new Date();
		statusTime = d.getTime();
		document.getElementById( 'status' ).innerHTML = text;
	}

	// synchronous
	// response is ignored
	function sendCommand( command, params, getState )
	{
		if (getState) writeStatus( 'Sending command: ' + command + ' ' + params );

		var ajax = getAjax();
		ajax.onreadystatechange = null;
		ajax.open( 'GET', '?p_page=command&p_command=' + command + '&p_params=' + params, false );
		ajax.send( null );

		if (getState) getGameState();
	}

	var gameStateAjax;

	// synchronous
	// calls "look" command after most commands
	// response handled by receiveGameState()
	function getGameState()
	{
		writeStatus( 'Getting game state...' );

		gameStateAjax = getAjax();

		gameStateAjax.onreadystatechange = receiveGameState;
		gameStateAjax.open( 'GET', '?p_page=command&p_command=look', true );
		gameStateAjax.send( null );
	}

	// handles server's response to getGameState()
	function receiveGameState()
	{
		if (gameStateAjax.readyState == 4 && gameStateAjax.status == 200)
		{
			rows = document.getElementsByName( 'commandButton' );
			for (i=0; i<rows.length; i++) rows[i].disabled = false;

			var xml = gameStateAjax.responseXML;

			handleResponseOrUpdate( xml );

			writeStatus( 'Received game state.' );
		}
	}

	// sync with php version below
	function makeCommandButton( command, text )
	{
		return "<button name=commandButton id=btn_" + command + " onclick=\"" +
			"rows = document.getElementsByName( 'commandButton' ); " +
			"for (i=0; i<rows.length; i++) rows[i].disabled = true; " +
			"sendCommand( '" + command + "', '', true ); " +
			"return false;" +
			"\" style=\"\">" + text + "</button>";
	}

	function parsePlayer( xml )
	{
		var text = '<center><b><?php print sessionVar( 'username' )?></b></center><br>';

		var user = xml.getElementsByTagName( 'user' ).item(0);
		if (!user) return;

		var health = user.attributes.getNamedItem( 'health' ).nodeValue;
		var illness = 100 - health;
		text += 'Health:&nbsp;<table style="border: 1px solid black; border-collapse: collapse; "><tr>';
		text += '<td style="width: ' + health + 'px; height: 1em; background-color: green; ">';
		if (illness) text += '<td style="width: ' + illness + 'px; height: 1em; background-color: red; ">';
		text += '</tr></table><br>';

		// player inventory
		var inventory = xml.getElementsByTagName( 'inventory' ).item(0);
		text += 'Items you are carrying:<br><table border=0>';
		for (var i=0; i<inventory.childNodes.length; i++)
		{
			var itemName = inventory.childNodes.item(i).firstChild.data;

			text += 
				'<tr><td><span class=player>' + itemName + '</span><td>' + makeCommandButton( 'drop_' + itemName, 'drop' ) + '</tr>';
		}
		text += '</table>';

		output = document.getElementById( 'player' );
		output.innerHTML = text;

		// show/hide movement buttons

		document.getElementById( 'btn_north' ).style.visibility =
			xml.getElementsByTagName( 'northVisibility' ).item(0).firstChild.data;

		document.getElementById( 'btn_south' ).style.visibility =
			xml.getElementsByTagName( 'southVisibility' ).item(0).firstChild.data;

		document.getElementById( 'btn_east' ).style.visibility =
			xml.getElementsByTagName( 'eastVisibility' ).item(0).firstChild.data;

		document.getElementById( 'btn_west' ).style.visibility =
			xml.getElementsByTagName( 'westVisibility' ).item(0).firstChild.data;
	}

	function parseItems( xml )
	{
		var items = xml.getElementsByTagName( 'items' ).item(0);
		if (!items) return;

		var output = document.getElementById( 'items' );
		output.innerHTML = 'Items here:<br><table border=0>';
		for (var i=0; i<items.childNodes.length; i++)
		{
			var itemName = items.childNodes.item(i).firstChild.data;

			output.innerHTML += 
				'<tr><td><span class=item>' + itemName + '</span><td>' + makeCommandButton( 'take_' + itemName, 'take' ) + '</tr>';
		}
		output.innerHTML += '</table>';
	}

	function parseOccupants( xml )
	{
		var occupants = xml.getElementsByTagName( 'occupants' ).item(0);
		if (!occupants) return;

		var output = document.getElementById( 'occupants' );
		output.innerHTML = 'Other people here:<br><table border=0>';
		for (var i=0; i<occupants.childNodes.length; i++)
		{
			name = occupants.childNodes.item(i).firstChild.data;
			health = occupants.childNodes.item(i).attributes.getNamedItem( 'health' ).nodeValue;

			if (name != '<?php print sessionVar( 'username' )?>')
			{
				output.innerHTML += 
					"<tr><td><span class=occupant onMouseOver=\"showPopupMessage( event, '" + name + ": health = " + health + "/100' )\" onMouseOut=\"hidePopupMessage( event )\">" + name + "</span>";

				if (1) output.innerHTML += 
					"<td>" + makeCommandButton( "attack_" + name, "attack" );

				output.innerHTML += "</tr>";
			}
		}
		output.innerHTML += '</table>';
	}

	function parseNPCs( xml )
	{
		var npcs = xml.getElementsByTagName( 'npcs' ).item(0);
		if (!npcs) return;

		output = document.getElementById( 'npcs' );
		output.innerHTML = 'Creatures here:<br><table border=0>';
		for (var i=0; i<npcs.childNodes.length; i++)
		{
			var name = npcs.childNodes.item(i).firstChild.data;
			health = npcs.childNodes.item(i).attributes.getNamedItem( 'health' ).nodeValue;

			output.innerHTML += 
				"<tr><td><span class=npc onMouseOver=\"showPopupMessage( event, '" + name + ": health = " + health + "/10' )\" onMouseOut=\"hidePopupMessage( event )\">" + name + "</span><td>" + makeCommandButton( "attack_" + name, "attack" ) + "</tr>";
		}
		output.innerHTML += '</table>';
	}

	// receives updates
	function handleUpdate( event )
	{
		var xml = event.target.responseXML;

    if (xml) {
      handleResponseOrUpdate( xml );
    }

		writeStatus( 'Received update.' );
	}

	function handleResponseOrUpdate( xml )
	{
		//	xml	// uses DOM Document interface
		//	xml.getElementsByTagName()	// returns DOM NodeList
		//	xml.getElementsByTagName().item(0)	//	DOM Node
		//	xml.getElementsByTagName().length

		var chat = xml.getElementsByTagName( 'chat' );
		for (var i=0; i<chat.length; i++)
		{
			if (chat.item(i)) 
				document.getElementById( 'chatArea' ).value =
					chat.item(i).firstChild.data + "\n" +
					document.getElementById( 'chatArea' ).value;
		}

		if (xml.getElementsByTagName( 'error' ).item(0))
			document.getElementById( 'error' ).innerHTML = 
				xml.getElementsByTagName( 'error' ).item(0).firstChild.data;

		if (xml.getElementsByTagName( 'description' ).item(0))
			document.getElementById( 'description' ).innerHTML = 
				xml.getElementsByTagName( 'description' ).item(0).firstChild.data;

		parsePlayer( xml );
		parseItems( xml );
		parseOccupants( xml );
		parseNPCs( xml );
	}

	function stopUpdates()
	{
		updateAjax.abort();
		sendCommand( 'closeUpdateSocket', '', false );
		writeStatus( 'Updates stopped.' );
	}

</script>

<?php require "style1.inc"; ?>

</head>

<body>


<div id="popDivArray" style="position: absolute; visibility: visible; z-index: 100; color: white; background-color: #666; text-align: left; "></div>

<div id="popDiv" style="position: absolute; visibility: hidden; z-index: 100; color: white; background-color: #666; text-align: left; ">text</div>

<center>
<h3><?php print $title?></h3>

<?php

if (!preg_match( "/firefox/i", $_SERVER['HTTP_USER_AGENT'] ))
{
	print "<h2>Browser not supported.<br>At this time, $title is only known to work in <a href=\"http://www.mozilla.com/firefox/\" target=\"_blank\">Firefox</a>!</h2>";
	exit;
}


/////////////////////////////////////////////////////
// PAGES PART 2

// login page
if ($p_page == '')
{
	if ($p_submitEnter) // login submitted
	{
		// these users must exist in the game
		$passwords = array(
			'mpb' => '123',
			'charlie' => '123'
		);

		if (isset( $passwords[$p_name] ) and $passwords[$p_name] == $p_password)
		{
			$_SESSION['username'] = $p_name;

			redirect2( '?p_page=play' );
		}
		else
		{
			print "<h4>Login failed.</h4>";
		}
	}

	$_SESSION['username'] = '';

	?>
	<form method=post>
		Name: <input name=p_name>
		Password: <input name=p_password type=password>
		<input name=p_submitEnter type=submit value="Enter!">
	</form>

	<br><br><img src="WebMUD1.jpg"><br>
	<?php
}
elseif ($p_page == 'play')  // game page
{
	?>

	<h4><a href="?" onclick="stopUpdates(); ">Quit</a></h4>

	<table border=1 style="border-collapse: collapse; ">

		<tr>
			<td id=coords>
			<td align=center><?php print  makeCommandButton( 'north', 'Go North' ) ?>
			<td>
		</tr>

		<tr>
			<td align=center><?php print  makeCommandButton( 'west', 'Go West' ) ?>
			<td valign=top style="width: 200px; height: 300px; ">
				<table border=1 width=100% height=100% style="border-collapse: collapse; ">
					<tr> <td valign=top id=description> </tr>
					<tr> <td valign=top id=items> </tr>
					<tr> <td valign=top id=occupants> </tr>
					<tr> <td valign=top id=npcs></tr>
				</table>
			<td align=center><?php print  makeCommandButton( 'east', 'Go East' ) ?>
			<td id=player valign=top>
		</tr>

		<tr>
			<td>
			<td align=center><?php print  makeCommandButton( 'south', 'Go South' ) ?>
			<td>
		</tr>

		<tr>
			<td colspan=1>Chat: 
			<td colspan=3>
				<input name=p_chat id=chat style="width: 35em; ">
				<button name=commandButton onclick="sendCommand( 'chat', document.getElementById( 'chat' ).value, false ); document.getElementById( 'chat' ).value = ''; document.getElementById( 'chat' ).focus(); return false;">Submit</button>
		</tr>

		<tr>
			<td colspan=4>
				<textarea id=chatArea rows=10 style="width:100%; "></textarea>
		</tr>

	</table>

	<br><br>
	<table border=1 style="border-collapse: collapse; ">

		<tr>
			<td colspan=4 align=center>Debug
		</tr>

		<tr>
			<td colspan=1>Errors: 
			<td colspan=3 id=error>
		</tr>

		<tr>
			<td colspan=1>Status: 
			<td colspan=1 id=status>
			<td colspan=1 id=statusTime>
			<td colspan=1><button onclick="stopUpdates()">Stop Updates</button>
		</tr>

		<tr>
			<td colspan=4 align=center><button onclick="getGameState()">Refresh Game State</button>
		</tr>

	</table>



	<script type="text/javascript">

		getGameState();

		// start updates

		var updateAjax = getAjax();

		updateAjax.multipart = true;
		updateAjax.open( 'GET', '?p_page=updates', true );
		updateAjax.onload = handleUpdate;
		updateAjax.send();

		// start time keeping thread

		var intervalID = window.setInterval( showTime, 1000 );  

		function showTime()
		{
			var d = new Date();
			var elapsed = (d.getTime() - statusTime) / 1000;
			elapsed = Math.round( elapsed );
			document.getElementById( 'statusTime' ).innerHTML = elapsed + ' second(s) ago';
		}

		showTime();

	</script>
	<?php
}
?>

</body>

</html>


<?php

/////////////////////////////////////////////////////
// FUNCTIONS

// sync with javascript version above
function makeCommandButton( $command, $text )
{
	return "<button name=commandButton id=btn_$command onclick=\"" .
		"rows = document.getElementsByName( 'commandButton' ); " .
		"for (i=0; i<rows.length; i++) rows[i].disabled = true; " .
		"sendCommand( '" . urlencode( $command ) . "', '', true ); " .
		"return false;" .
		"\" style=\"\">$text</button>";
}


?>
