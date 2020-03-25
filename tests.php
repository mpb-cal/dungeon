<?

function showCmd( $href )
{
	print "<p><a href=\"$href\">$href</a>\n";
}


showCmd( "index.php?p_page=command&p_command=map" );
showCmd( "index.php?p_page=command&p_command=resetGame" );
showCmd( "index.php?p_page=command&p_command=users" );
showCmd( "index.php?p_page=command&p_command=npcs" );
showCmd( "index.php?p_page=command&p_command=rooms" );
showCmd( "index.php?p_page=command&p_command=shutdown" );

print "<br>";
print "<br>";

showCmd( "index.php?p_page=command" );
showCmd( "index.php?p_page=command&p_command=look" );
showCmd( "index.php?p_page=command&p_command=ping" );
showCmd( "index.php?p_page=command&p_command=closeUpdateSocket" );
showCmd( "index.php?p_page=command&p_command=charDetails&p_params=mpb" );
showCmd( "index.php?p_page=command&p_command=setPosition&p_params=101 101" );
showCmd( "index.php?p_page=command&p_command=north" );
showCmd( "index.php?p_page=command&p_command=west" );
showCmd( "index.php?p_page=command&p_command=south" );
showCmd( "index.php?p_page=command&p_command=east" );
showCmd( "index.php?p_page=command&p_command=chat&p_params=hello hello" );

?>

